<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunityRequest;
use App\Models\Community;
use App\Models\User;
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
            $bannerPath = $request->file('banner')->store('community-banners', 'public');
        }
        $community = Community::create([
            'name' => strtolower($validated['name']),
            'description' => $validated['description'] ?? null,
            'banner_path' => $bannerPath,
            'created_by' => Auth::id(),
        ]);
        $community->moderators()->attach(Auth::id());

        return redirect()->route('communities.show', $community->name)
            ->with('success', "Komunitas c/{$community->name} berhasil dibuat!");
    }

    public function show(Community $community): View
    {
        $posts = $community->posts()
            ->with(['user', 'comments.user', 'votes'])
            ->latest()
            ->paginate(10);
        $moderators = $community->moderators()->get();

        return view('communities.show', compact('community', 'posts', 'moderators'));
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
            if ($community->banner_path && Storage::disk('public')->exists($community->banner_path)) {
                Storage::disk('public')->delete($community->banner_path);
            }
            $community->banner_path = $request->file('banner')->store('community-banners', 'public');
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
        if ($community->banner_path && Storage::disk('public')->exists($community->banner_path)) {
            Storage::disk('public')->delete($community->banner_path);
        }
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
        if (! preg_match('/^[a-z0-9_-]+$/', $name)) {
            return response()->json([
                'available' => false,
                'message' => 'Hanya huruf, angka, (-), dan (_).',
            ]);
        }
        $currentId = $request->query('current_id');
        $exists = Community::where('name', $name)
            ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
            ->exists();

        return response()->json([
            'available' => ! $exists,
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
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'User ini sudah menjadi moderator.'], 422);
            }

            return back()->with('error', 'User ini sudah menjadi moderator.');
        }
        $community->moderators()->attach($user->id);
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
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Hak moderator {$user->name} berhasil dicopot.",
                'user_id' => $user->id,
            ]);
        }

        return back()->with('success', "Hak moderator {$user->name} berhasil dicopot.");
    }
}
