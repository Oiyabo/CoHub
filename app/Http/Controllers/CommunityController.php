<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunityRequest;
use App\Http\Requests\UpdateCommunityAboutRequest;
use App\Http\Requests\CommunityRuleRequest;
use App\Http\Requests\AddModeratorRequest;
use App\Models\Community;
use App\Models\User;
use App\Models\CommunityMember;
use App\Notifications\ModerationNotification;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CommunityController extends Controller
{
   public function index(Request $request): View
{
    $search = $request->query('search');
    $category = $request->query('category');

    $allowedCategories = [
        'Teknologi',
        'Akademik',
        'Kampus',
        'Data',
        'Karier',
    ];

    if (!in_array($category, $allowedCategories, true)) {
        $category = null;
    }

    $communities = Community::with('creator')
        ->withCount(['posts', 'moderators'])
        ->when($category, function ($query, $category) {
            $query->where('category', $category);
        })
        ->when($search, function ($query, $search) {
            $search = trim(preg_replace('/\s+/', ' ', $search));
            $search = str_replace('-', ' ', $search);
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw(
                        "REPLACE(name, '-', ' ') ILIKE ?",
                        ["%{$search}%"]
                    )
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        })
        ->latest()
        ->paginate(9)
        ->withQueryString();

    return view('communities.index', compact('communities', 'search', 'category'));
}
    public function create(): View
    {
        return view('communities.create');
    }

    public function store(CommunityRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $bannerPath = null;
        if ($request->hasFile('banner')) {
            $bannerPath = ImageService::compressAndStore($request->file('banner'), 'community-banners', maxWidth: 1600);
        }
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = ImageService::compressAndStore($request->file('avatar'), 'community-avatars', maxWidth: 400);
        }
        $community = Community::create([
            'name' => strtolower($validated['name']),
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'is_private' => $request->boolean('is_private'),
            'banner_path' => $bannerPath,
            'avatar_path' => $avatarPath,
            'created_by' => Auth::id(),
        ]);
        $community->members()->attach(Auth::id(), ['role' => 'admin']);

        return redirect()->route('communities.show', $community->name)
            ->with('success', "Komunitas c/{$community->name} berhasil dibuat!");
    }

    public function show(Community $community): View
    {
        $community->loadCount(['members', 'posts']);

        $user = Auth::user();
        $isMember = $community->isMember($user);
        $canViewContent = !$community->is_private || ($user && ($user->isAdmin() || $isMember));

        $posts = $canViewContent
            ? $community->posts()->where('status', 'published')->with(['user', 'comments.user', 'comments.votes', 'votes'])->latest()->paginate(10)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);

        $moderators = $community->moderators()->get();
        $members = $community->members()->withPivot('role')->latest('community_members.created_at')->get();

        $rules = $community->rules ?? [];
        $about = $community->about ?? $community->description;

        return view('communities.show', compact(
            'community',
            'posts',
            'isMember',
            'canViewContent',
            'moderators',
            'members',
            'rules',
            'about'
        ));
    }

    public function edit(Request $request, Community $community): View
    {
        Gate::authorize('manageRules', $community);
        $community->loadMissing(['moderators', 'members']);

        return view('communities.edit', compact('community'));
    }

    public function lookupModerator(Request $request, Community $community): JsonResponse
    {
        Gate::authorize('assignModerator', $community);

        $email = trim((string) $request->input('email', ''));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Masukkan alamat email yang valid.',
            ], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna dengan email tersebut tidak ditemukan di sistem.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'initial' => strtoupper(substr($user->name, 0, 2)),
                'is_owner' => $user->id === $community->created_by,
            ],
        ]);
    }

    public function update(CommunityRequest $request, Community $community): RedirectResponse
    {
        Gate::authorize('update', $community);
        $validated = $request->validated();

        DB::transaction(function () use ($request, $community, $validated) {
            if ($request->hasFile('banner')) {
                ImageService::delete($community->banner_path);
                $community->banner_path = ImageService::compressAndStore($request->file('banner'), 'community-banners', maxWidth: 1600);
            }
            if ($request->hasFile('avatar')) {
                ImageService::delete($community->avatar_path);
                $community->avatar_path = ImageService::compressAndStore($request->file('avatar'), 'community-avatars', maxWidth: 400);
            }
            $community->name = strtolower($validated['name']);
            $community->description = $validated['description'] ?? null;
            $community->category = $validated['category'] ?? null;
            $community->is_private = $request->boolean('is_private');

            $currentAbout = is_array($community->about) ? $community->about : [];
            $community->about = array_merge($currentAbout, [
                'content' => $validated['content'] ?? ($currentAbout['content'] ?? null),
                'guidelines' => $validated['guidelines'] ?? ($currentAbout['guidelines'] ?? null),
                'website' => $validated['website'] ?? ($currentAbout['website'] ?? null),
                'discord' => $validated['discord'] ?? ($currentAbout['discord'] ?? null),
                'github' => $validated['github'] ?? ($currentAbout['github'] ?? null),
            ]);

            // Update Aturan (Rules) berbarengan
            if ($request->has('rules')) {
                $rawRules = $request->input('rules');
                if (is_string($rawRules)) {
                    $rawRules = json_decode($rawRules, true) ?? [];
                }
                if (is_array($rawRules)) {
                    $cleanedRules = [];
                    foreach ($rawRules as $idx => $r) {
                        $text = is_array($r) ? trim($r['text'] ?? $r['title'] ?? '') : trim((string) $r);
                        if (!empty($text)) {
                            $cleanedRules[] = [
                                'id' => is_array($r) ? (string) ($r['id'] ?? (time() . $idx)) : (string) (time() . $idx),
                                'title' => $text,
                                'text' => $text,
                                'description' => null,
                            ];
                        }
                    }
                    $community->rules = $cleanedRules;
                }
            }

            $community->save();

            // Update Moderator (berbarengan)
            if ($request->has('moderators')) {
                $submittedModIds = array_map('intval', (array) $request->input('moderators', []));
                $ownerId = (int) $community->created_by;

                // Pastikan owner selalu ada dalam daftar moderator/admin
                if (!in_array($ownerId, $submittedModIds, true)) {
                    $submittedModIds[] = $ownerId;
                }

                // Ambil semua pengguna yang saat ini memegang role moderator/admin
                $currentMods = $community->members()
                    ->wherePivotIn('role', ['admin', 'moderator'])
                    ->get();

                // 1. Yang sebelumnya moderator tapi tidak ada di submittedModIds (dan bukan owner): turunkan role jadi member
                foreach ($currentMods as $mod) {
                    if ($mod->id !== $ownerId && !in_array($mod->id, $submittedModIds, true)) {
                        $community->members()->updateExistingPivot($mod->id, ['role' => 'member']);
                        ModerationNotification::send(
                            actor: Auth::user(),
                            actionType: 'remove_moderator',
                            targetUser: $mod,
                            community: $community
                        );
                    }
                }

                // 2. Beri hak moderator untuk yang terdaftar di submittedModIds
                foreach ($submittedModIds as $modId) {
                    if ($modId === $ownerId) {
                        if ($community->isMember(User::find($ownerId))) {
                            $community->members()->updateExistingPivot($ownerId, ['role' => 'admin']);
                        } else {
                            $community->members()->attach($ownerId, ['role' => 'admin']);
                        }
                        continue;
                    }

                    $user = User::find($modId);
                    if ($user) {
                        $wasMod = $currentMods->contains('id', $user->id);
                        if ($community->isMember($user)) {
                            $community->members()->updateExistingPivot($user->id, ['role' => 'moderator']);
                        } else {
                            $community->members()->attach($user->id, ['role' => 'moderator']);
                        }

                        if (!$wasMod) {
                            ModerationNotification::send(
                                actor: Auth::user(),
                                actionType: 'add_moderator',
                                targetUser: $user,
                                community: $community
                            );
                        }
                    }
                }
            }
        });

        return redirect()->route('communities.show', $community->name)
            ->with('success', 'Informasi komunitas, aturan, dan moderator berhasil diperbarui.');
    }

    public function destroy(Community $community): RedirectResponse
    {
        Gate::authorize('delete', $community);
        ImageService::delete($community->banner_path);
        ImageService::delete($community->avatar_path);
        $name = $community->name;
        $community->delete();

        return redirect()->route('communities.index')
            ->with('success', "Komunitas c/{$name} berhasil dihapus oleh Administrator.");
    }

    public function checkName(Request $request): JsonResponse
    {
        $name = strtolower(trim($request->query('name', '')));
        if (strlen($name) < 3) {
            return response()->json([
                'available' => false,
                'message' => 'Minimal 3 karakter.',
            ]);
        }
        if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
            return response()->json([
                'available' => false,
                'message' => 'Hanya huruf, angka, (-), dan (_).',
            ]);
        }
        $currentId = $request->query('current_id');
        $exists = Community::where('name', $name)
            ->when($currentId, fn($q) => $q->where('id', '!=', $currentId))
            ->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Nama sudah terpakai!' : 'Nama tersedia ✓',
        ]);
    }

    public function moderatorsIndex(Community $community): View
    {
        Gate::authorize('assignModerator', $community);
        $moderators = $community->moderators()->get();

        return view('communities.moderators', compact('community', 'moderators'));
    }

    public function addModerator(AddModeratorRequest $request, Community $community): JsonResponse|RedirectResponse
    {
        Gate::authorize('assignModerator', $community);

        $user = User::where('email', $request->email)->first();

        $community->members()->updateExistingPivot($user->id, ['role' => 'moderator']);

        ModerationNotification::send(
            actor: Auth::user(),
            actionType: 'add_moderator',
            targetUser: $user,
            community: $community
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$user->name} berhasil ditambahkan sebagai moderator.",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'initial' => strtoupper(substr($user->name, 0, 2)),
                ],
            ]);
        }

        return back()->with('success', "{$user->name} berhasil ditambahkan sebagai moderator.");
    }

    public function removeModerator(Request $request, Community $community, User $user): JsonResponse|RedirectResponse
    {
        Gate::authorize('assignModerator', $community);
        if ($user->id === $community->created_by) {
            $msg = 'Pembuat komunitas (Owner) tidak dapat dicopot dari peran moderator.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $isMod = $community->members()
            ->where('users.id', $user->id)
            ->wherePivotIn('role', ['admin', 'moderator'])
            ->exists();

        if ($isMod) {
            $community->members()->updateExistingPivot($user->id, ['role' => 'member']);

            ModerationNotification::send(
                actor: Auth::user(),
                actionType: 'remove_moderator',
                targetUser: $user,
                community: $community
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Hak moderator {$user->name} berhasil dicopot.",
                'user_id' => $user->id,
            ]);
        }

        return back()->with('success', "Hak moderator {$user->name} berhasil dicopot.");
    }

    public function join(Community $community): RedirectResponse
    {
        if ($community->isClosed()) {
            return back()->with('error', "Komunitas c/{$community->name} sedang ditutup oleh Administrator.");
        }

        if ($community->is_private && !Auth::user()->isAdmin() && $community->created_by !== Auth::id()) {
            return back()->with('error', 'Komunitas ini bersifat privat. Akses masuk hanya melalui undangan atau persetujuan pengelola.');
        }

        if (!$community->isMember(Auth::user())) {
            $community->members()->attach(Auth::id(), ['role' => 'member']);
        }

        return back()->with('success', "Kamu berhasil bergabung dengan c/{$community->name}!");
    }

    public function leave(Community $community): RedirectResponse
    {
        if ($community->created_by === Auth::id()) {
            return back()->with('error', 'Pembuat komunitas tidak dapat keluar dari komunitasnya sendiri.');
        }

        $community->members()->detach(Auth::id());

        return back()->with('success', "Kamu telah keluar dari c/{$community->name}.");
    }

    public function updateAbout(UpdateCommunityAboutRequest $request, Community $community): RedirectResponse
    {
        Gate::authorize('manageRules', $community);

        $community->update(['about' => $request->validated()]);

        return redirect()->route('communities.edit', ['community' => $community->name, 'tab' => 'tentang'])
            ->with('success', 'Informasi Tentang Komunitas berhasil diperbarui!');
    }

    public function addRule(CommunityRuleRequest $request, Community $community): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageRules', $community);
        $validated = $request->validated();

        $newRule = $community->addRule($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan berhasil ditambahkan!',
                'rule' => $newRule,
                'rules' => $community->rules,
            ]);
        }

        return redirect()->route('communities.edit', ['community' => $community->name, 'tab' => 'aturan'])
            ->with('success', 'Aturan berhasil ditambahkan.');
    }

    public function updateRule(CommunityRuleRequest $request, Community $community, $ruleId): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageRules', $community);
        $validated = $request->validated();

        $community->updateRule($ruleId, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan berhasil diperbarui!',
                'rules' => $community->rules,
            ]);
        }

        return redirect()->route('communities.edit', ['community' => $community->name, 'tab' => 'aturan'])
            ->with('success', 'Aturan berhasil diperbarui.');
    }

    public function deleteRule(Request $request, Community $community, $ruleId): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageRules', $community);

        $community->deleteRule($ruleId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan berhasil dihapus.',
                'rules' => $community->rules,
            ]);
        }

        return redirect()->route('communities.edit', ['community' => $community->name, 'tab' => 'aturan'])
            ->with('success', 'Aturan berhasil dihapus.');
    }

    public function resetDefaultRules(Request $request, Community $community): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageRules', $community);

        $defaultRules = $community->resetDefaultRules();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '3 aturan standar rekomendasi berhasil dipasang!',
                'rules' => $defaultRules,
            ]);
        }

        return redirect()->route('communities.edit', ['community' => $community->name, 'tab' => 'aturan'])
            ->with('success', '3 aturan standar rekomendasi berhasil dipasang!');
    }
}
