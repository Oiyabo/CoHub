<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunityRequest;
use App\Models\Community;
use App\Models\User;
use App\Models\CommunityMember;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $communities = Community::with('creator')
            ->withCount(['posts', 'moderators'])
            ->when($search, function ($query, $search) {
                return $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('communities.index', compact('communities', 'search'));
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
            'banner_path' => $bannerPath,
            'avatar_path' => $avatarPath,
            'created_by' => Auth::id(),
        ]);
        $community->moderators()->attach(Auth::id());
        $community->members()->attach(Auth::id(), ['role' => 'admin']);

        return redirect()->route('communities.show', $community->name)
            ->with('success', "Komunitas c/{$community->name} berhasil dibuat!");
    }

    public function show(Community $community): View
    {
        $community->loadCount(['members', 'posts']);

        $posts = $community->posts()
            ->with(['user', 'comments.user', 'votes'])
            ->latest()
            ->paginate(10);

        $moderators = $community->moderators()->get();
        $isMember = $community->isMember(Auth::user());
        $members = $community->members()->withPivot('role')->latest('community_members.created_at')->get();

        $rules = $community->rules ?? [];
        $about = $community->about ?? $community->description;

        return view('communities.show', compact(
            'community',
            'posts',
            'moderators',
            'isMember',
            'members',
            'rules',
            'about'
        ));
    }

    public function edit(Community $community): View
    {
        Gate::authorize('update', $community);

        return view('communities.edit', compact('community'));
    }

    public function update(CommunityRequest $request, Community $community): RedirectResponse
    {
        Gate::authorize('update', $community);
        $validated = $request->validated();
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
        $community->save();

        return redirect()->route('communities.show', $community->name)
            ->with('success', 'Informasi komunitas berhasil diperbarui.');
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

    // AJAX

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

    public function addModerator(Request $request, Community $community): JsonResponse|RedirectResponse
    {
        Gate::authorize('assignModerator', $community);
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Akun dengan email tersebut tidak ditemukan di sistem.',
        ]);
        $user = User::where('email', $request->email)->first();
        if ($community->isModerator($user)) {
            $msg = 'User ini sudah menjadi moderator.';
            return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        // Syarat: Pengguna harus sudah bergabung sebagai member komunitas terlebih dahulu
        if (!$community->isMember($user)) {
            $msg = 'Pengguna harus sudah bergabung sebagai anggota komunitas terlebih dahulu sebelum diangkat menjadi moderator.';
            return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        $community->moderators()->attach($user->id);
        $community->members()->updateExistingPivot($user->id, ['role' => 'moderator']);

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
        $community->moderators()->detach($user->id);
        $community->members()->updateExistingPivot($user->id, ['role' => 'member']);

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

    public function updateAbout(Request $request, Community $community): RedirectResponse
    {
        Gate::authorize('update', $community);

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'guidelines' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'discord' => ['nullable', 'url', 'max:255'],
            'github' => ['nullable', 'url', 'max:255'],
        ], [
            'website.url' => 'Format tautan website tidak valid (contoh: https://domain.com).',
            'discord.url' => 'Format tautan Discord tidak valid (contoh: https://discord.gg/xyz).',
            'github.url' => 'Format tautan GitHub/Medsos tidak valid (contoh: https://github.com/xyz).',
        ]);

        $community->about = [
            'content' => $validated['content'] ?? null,
            'guidelines' => $validated['guidelines'] ?? null,
            'website' => $validated['website'] ?? null,
            'discord' => $validated['discord'] ?? null,
            'github' => $validated['github'] ?? null,
        ];
        $community->save();

        return redirect()->route('communities.edit', ['community' => $community->name, 'tab' => 'tentang'])
            ->with('success', 'Informasi Tentang Komunitas berhasil diperbarui!');
    }

    public function addRule(Request $request, Community $community): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $community);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'title.required' => 'Judul aturan wajib diisi.',
            'title.max' => 'Judul aturan maksimal 120 karakter.',
            'description.max' => 'Deskripsi aturan maksimal 500 karakter.',
        ]);

        $rules = $community->rules ?? [];
        $newRule = [
            'id' => (string) (time() . rand(100, 999)),
            'title' => trim($validated['title']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
        ];
        $rules[] = $newRule;
        $community->rules = $rules;
        $community->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan berhasil ditambahkan!',
                'rule' => $newRule,
                'rules' => $rules,
            ]);
        }

        return redirect()->route('communities.edit', ['community' => $community->name, 'tab' => 'aturan'])
            ->with('success', 'Aturan berhasil ditambahkan.');
    }

    public function updateRule(Request $request, Community $community, $ruleId): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $community);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'title.required' => 'Judul aturan wajib diisi.',
            'title.max' => 'Judul aturan maksimal 120 karakter.',
            'description.max' => 'Deskripsi aturan maksimal 500 karakter.',
        ]);

        $rules = $community->rules ?? [];
        $updated = false;

        foreach ($rules as &$rule) {
            if (isset($rule['id']) && (string) $rule['id'] === (string) $ruleId) {
                $rule['title'] = trim($validated['title']);
                $rule['description'] = !empty($validated['description']) ? trim($validated['description']) : null;
                $updated = true;
                break;
            }
        }
        unset($rule);

        // Fallback jika id adalah index numerik array
        if (!$updated && isset($rules[$ruleId])) {
            $rules[$ruleId]['title'] = trim($validated['title']);
            $rules[$ruleId]['description'] = !empty($validated['description']) ? trim($validated['description']) : null;
            $updated = true;
        }

        $community->rules = array_values($rules);
        $community->save();

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
        Gate::authorize('update', $community);

        $rules = $community->rules ?? [];
        $newRules = [];

        foreach ($rules as $idx => $rule) {
            $matched = false;
            if (isset($rule['id']) && (string) $rule['id'] === (string) $ruleId) {
                $matched = true;
            } elseif ((string) $idx === (string) $ruleId) {
                $matched = true;
            }
            if (!$matched) {
                $newRules[] = $rule;
            }
        }

        $community->rules = array_values($newRules);
        $community->save();

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
        Gate::authorize('update', $community);

        $defaultRules = [
            [
                'id' => (string) (time() . '1'),
                'title' => 'Tetap sesuai topik',
                'description' => 'Diskusi harus berkaitan dengan topik dan ekosistem terkait.',
            ],
            [
                'id' => (string) (time() . '2'),
                'title' => 'Hormati sesama anggota',
                'description' => 'Gunakan bahasa yang santun, dilarang menyerang pribadi atau melecehkan.',
            ],
            [
                'id' => (string) (time() . '3'),
                'title' => 'Dilarang spam & iklan',
                'description' => 'Dilarang mempromosikan produk/jasa komersial tanpa izin moderator.',
            ],
        ];

        $community->rules = $defaultRules;
        $community->save();

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
