<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ModerationNotification;
use App\Support\SafeSchema;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    private function sidebarSummary(): array
    {
        $hasClosedCol = SafeSchema::hasColumn('communities', 'is_closed');
        $hasReportsTable = SafeSchema::hasTable('reports');

        return [
            'total_users'        => User::count(),
            'active_communities' => $hasClosedCol ? Community::where('is_closed', false)->count() : Community::count(),
            'total_posts'        => Post::count(),
            'pending_reports'    => $hasReportsTable ? Report::whereIn('status', ['new', 'reviewed'])->count() : 0,
        ];
    }

    private function formatShortRelative($date): string
    {
        if (! $date) {
            return '-';
        }

        $minutes = (int) $date->diffInMinutes(now());
        if ($minutes < 1) {
            return 'baru saja';
        }
        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = (int) $date->diffInHours(now());
        if ($hours < 24) {
            return $hours . 'j';
        }

        return (int) $date->diffInDays(now()) . 'h';
    }

    public function dashboard()
    {
        $sidebarStats = $this->sidebarSummary();
        $hasClosedCol = SafeSchema::hasColumn('communities', 'is_closed');
        $hasBlockedCol = SafeSchema::hasColumn('users', 'is_blocked');

        $stats = [
            'total_users'        => $sidebarStats['total_users'],
            'users_this_week'    => User::where('created_at', '>=', now()->subDays(7))->count(),
            'total_communities'  => Community::count(),
            'active_communities' => $sidebarStats['active_communities'],
            'communities_new'    => Community::where('created_at', '>=', now()->subDays(7))->count(),
            'total_posts'        => $sidebarStats['total_posts'],
            'posts_today'        => Post::whereDate('created_at', today())->count(),
            'pending_reports'    => $sidebarStats['pending_reports'],
            'total_comments'     => Comment::count(),
            'closed_communities' => $hasClosedCol ? Community::where('is_closed', true)->count() : 0,
            'admin_count'        => User::where('role', 'admin')->count(),
            'blocked_count'      => $hasBlockedCol ? User::where('is_blocked', true)->count() : 0,
        ];

        $activityData = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $date = $dateObj->toDateString();
            $posts = Post::whereDate('created_at', $date)->count();
            $comments = Comment::whereDate('created_at', $date)->count();
            $activityData[] = [
                'date'     => $dateObj->format('d M'),
                'posts'    => $posts,
                'comments' => $comments,
                'total'    => $posts + $comments,
            ];
        }

        $maxTotal = collect($activityData)->max('total');
        $peakIndex = null;
        if ($maxTotal > 0) {
            foreach ($activityData as $idx => $item) {
                if ($item['total'] === $maxTotal) {
                    $peakIndex = $idx;
                }
            }
        } else {
            $peakIndex = 5;
        }

        $recentUsers = User::latest()->take(5)->get()->map(function ($u) {
            return [
                'type'       => 'user',
                'actor'      => $u->name,
                'action'     => 'mendaftar akun baru',
                'time_short' => $this->formatShortRelative($u->created_at),
                'created_at' => $u->created_at,
            ];
        });

        $recentCommunities = Community::with('creator')->latest()->take(5)->get()->map(function ($c) {
            return [
                'type'       => 'community',
                'actor'      => $c->creator?->name ?? 'Administrator',
                'action'     => 'membuat komunitas c/' . $c->name,
                'time_short' => $this->formatShortRelative($c->created_at),
                'created_at' => $c->created_at,
            ];
        });

        $recentPosts = Post::with(['user', 'community'])->latest()->take(5)->get()->map(function ($p) {
            $target = $p->community ? ' di c/' . $p->community->name : '';

            return [
                'type'       => 'post',
                'actor'      => $p->user?->name ?? 'Pengguna',
                'action'     => 'membuat postingan "' . str($p->title)->limit(45) . '"' . $target,
                'time_short' => $this->formatShortRelative($p->created_at),
                'created_at' => $p->created_at,
            ];
        });

        $recentActivities = $recentUsers
            ->concat($recentCommunities)
            ->concat($recentPosts)
            ->sortByDesc('created_at')
            ->take(6)
            ->values();

        return view('admin.dashboard', compact('stats', 'sidebarStats', 'activityData', 'peakIndex', 'recentActivities'));
    }

    public function manageUsers(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $filter = $request->get('filter', 'all');
        $hasBlockedCol = SafeSchema::hasColumn('users', 'is_blocked');

        $query = User::withCount(['posts', 'comments']);

        if ($search !== '') {
            $term = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                  ->orWhereRaw("LOWER(COALESCE(username, '')) LIKE ?", [$term])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
            });
        }

        if ($filter === 'admin') {
            $query->where('role', 'admin');
        } elseif ($filter === 'blocked' && $hasBlockedCol) {
            $query->where('is_blocked', true);
        } elseif ($filter === 'user') {
            $query->where('role', 'user');
        }

        $users = $query
            ->orderByRaw("case when role = 'admin' then 0 else 1 end asc")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $sidebarStats = $this->sidebarSummary();

        return view('admin.users', compact('users', 'search', 'filter', 'sidebarStats'));
    }

    public function toggleBlockUser(Request $request, User $user)
    {
        if (! SafeSchema::hasColumn('users', 'is_blocked')) {
            return response()->json([
                'success' => false,
                'message' => 'Kolom blokir belum tersedia di database. Jalankan php artisan migrate terlebih dahulu.',
            ], 503);
        }

        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat memblokir akun sendiri.',
            ], 403);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Admin tidak dapat diblokir.',
            ], 403);
        }

        $willBlock = ! $user->isBlocked();
        $hasReasonCol = SafeSchema::hasColumn('users', 'blocked_reason');
        $hasHidePostsCol = SafeSchema::hasColumn('users', 'posts_hidden');

        if ($willBlock) {
            $validated = $request->validate([
                'reason'     => ['nullable', 'string', 'max:255'],
                'hide_posts' => ['nullable', 'boolean'],
            ]);

            $payload = ['is_blocked' => true];
            if ($hasReasonCol) {
                $payload['blocked_reason'] = $validated['reason'] ?? null;
            }
            if ($hasHidePostsCol) {
                $payload['posts_hidden'] = $request->boolean('hide_posts', false);
            }

            $user->update($payload);
        } else {
            $payload = ['is_blocked' => false];
            if ($hasReasonCol) {
                $payload['blocked_reason'] = null;
            }
            if ($hasHidePostsCol) {
                $payload['posts_hidden'] = false;
            }

            $user->update($payload);
        }

        $action = $user->isBlocked() ? 'block_user' : 'unblock_user';
        ModerationNotification::send(
            actor: Auth::user(),
            actionType: $action,
            targetUser: $user
        );

        return response()->json([
            'success'      => true,
            'is_blocked'   => $user->isBlocked(),
            'posts_hidden' => (bool) ($user->posts_hidden ?? false),
            'message'      => $user->isBlocked()
                ? "Akun {$user->name} berhasil diblokir."
                : "Akun {$user->name} berhasil diaktifkan kembali.",
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        if (! Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Kewenangan tidak mencukupi untuk mengubah peran.',
            ], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:user,admin'],
        ]);

        $newRole = $validated['role'];

        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Demi keamanan, Anda tidak dapat mengubah peran akun Anda sendiri.',
            ], 403);
        }

        if ($user->isBlocked() && $newRole === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna yang sedang diblokir tidak dapat diberi peran Admin.',
            ], 422);
        }

        if ($user->isAdmin() && $newRole !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Sistem harus memiliki minimal satu Admin aktif.',
            ], 422);
        }

        if ($user->role === $newRole) {
            return response()->json([
                'success' => true,
                'role'    => $user->role,
                'message' => "Peran {$user->name} sudah berstatus {$newRole}.",
            ]);
        }

        $user->update(['role' => $newRole]);

        ModerationNotification::send(
            actor: Auth::user(),
            actionType: 'update_role',
            targetUser: $user,
            extra: ['new_role' => $newRole]
        );

        return response()->json([
            'success' => true,
            'role'    => $user->role,
            'message' => "Peran {$user->name} berhasil diubah menjadi " . ($newRole === 'admin' ? 'Admin' : 'User') . '.',
        ]);
    }

    public function manageCommunities(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $status = $request->get('status', 'all');
        $hasClosedCol = SafeSchema::hasColumn('communities', 'is_closed');

        $openReportsByCommunity = SafeSchema::hasTable('reports')
            ? Report::where('reportable_type', 'community')
                ->whereIn('status', ['new', 'reviewed'])
                ->selectRaw('reportable_id, count(*) as total')
                ->groupBy('reportable_id')
                ->pluck('total', 'reportable_id')
            : collect();

        $communities = Community::with('creator')
            ->withCount(['posts', 'members'])
            ->when($search !== '', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($search) . '%']))
            ->when($hasClosedCol && $status === 'active', fn ($q) => $q->where('is_closed', false))
            ->when($hasClosedCol && $status === 'closed', fn ($q) => $q->where('is_closed', true))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $sidebarStats = $this->sidebarSummary();

        return view('admin.communities', compact('communities', 'search', 'status', 'openReportsByCommunity', 'sidebarStats'));
    }

    public function toggleCloseCommunity(Community $community)
    {
        if (! SafeSchema::hasColumn('communities', 'is_closed')) {
            return response()->json([
                'success' => false,
                'message' => 'Kolom status tutup komunitas belum tersedia di database. Jalankan php artisan migrate terlebih dahulu.',
            ], 503);
        }

        $community->update(['is_closed' => ! $community->isClosed()]);

        $action = $community->isClosed() ? 'close_community' : 'reopen_community';
        ModerationNotification::send(
            actor: Auth::user(),
            actionType: $action,
            community: $community
        );

        return response()->json([
            'success'   => true,
            'is_closed' => $community->isClosed(),
            'message'   => $community->isClosed()
                ? "Komunitas c/{$community->name} berhasil ditutup."
                : "Komunitas c/{$community->name} berhasil dibuka kembali.",
        ]);
    }

    public function deleteCommunity(Community $community)
    {
        $name = $community->name;
        $communityId = $community->id;
        $community->delete();

        if (SafeSchema::hasTable('reports')) {
            Report::where('reportable_type', 'community')
                ->where('reportable_id', $communityId)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "Komunitas c/{$name} berhasil dihapus.",
        ]);
    }

    public function manageReports(Request $request)
    {
        $sidebarStats = $this->sidebarSummary();

        $allowedStatuses = ['all', 'new', 'reviewed', 'resolved', 'dismissed'];
        $allowedTypes = ['all', 'post', 'comment', 'user', 'community'];
        $allowedSorts = ['latest', 'most_reported', 'priority'];

        $statusFilter = in_array($request->get('status', 'all'), $allowedStatuses, true)
            ? $request->get('status', 'all')
            : 'all';
        $typeFilter = in_array($request->get('type', 'all'), $allowedTypes, true)
            ? $request->get('type', 'all')
            : 'all';
        $sort = in_array($request->get('sort', 'latest'), $allowedSorts, true)
            ? $request->get('sort', 'latest')
            : 'latest';
        $search = trim((string) $request->get('search', ''));

        $statusCounts = [
            'all' => 0,
            'new' => 0,
            'reviewed' => 0,
            'resolved' => 0,
            'dismissed' => 0,
        ];
        $typeCounts = [
            'all' => 0,
            'post' => 0,
            'comment' => 0,
            'user' => 0,
            'community' => 0,
        ];

        if (! SafeSchema::hasTable('reports')) {
            $reports = new LengthAwarePaginator([], 0, 15);

            return view('admin.reports', compact(
                'reports',
                'sidebarStats',
                'statusFilter',
                'typeFilter',
                'sort',
                'search',
                'statusCounts',
                'typeCounts'
            ));
        }

        $statusRaw = Report::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusCounts = [
            'all' => (int) $statusRaw->sum(),
            'new' => (int) ($statusRaw['new'] ?? 0),
            'reviewed' => (int) ($statusRaw['reviewed'] ?? 0),
            'resolved' => (int) ($statusRaw['resolved'] ?? 0),
            'dismissed' => (int) ($statusRaw['dismissed'] ?? 0),
        ];

        $typeRaw = Report::when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->selectRaw('reportable_type, count(*) as total')
            ->groupBy('reportable_type')
            ->pluck('total', 'reportable_type');

        $typeCounts = [
            'all' => (int) $typeRaw->sum(),
            'post' => (int) ($typeRaw['post'] ?? 0),
            'comment' => (int) ($typeRaw['comment'] ?? 0),
            'user' => (int) ($typeRaw['user'] ?? 0),
            'community' => (int) ($typeRaw['community'] ?? 0),
        ];

        $matchingReports = Report::with(['reporter', 'reviewer'])
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->when($typeFilter !== 'all', fn ($q) => $q->where('reportable_type', $typeFilter))
            ->latest()
            ->get();

        $resolvedTargets = [];
        $grouped = $matchingReports->groupBy(fn ($r) => $r->reportable_type . ':' . $r->reportable_id);

        $groups = $grouped->map(function ($items) use (&$resolvedTargets) {
            $representative = $items->first();
            $cacheKey = $representative->reportable_type . ':' . $representative->reportable_id;

            if (! array_key_exists($cacheKey, $resolvedTargets)) {
                $resolvedTargets[$cacheKey] = $representative->resolveTarget();
            }

            $targetMeta = $this->formatReportTargetMeta($representative, $resolvedTargets[$cacheKey]);

            $mappedItems = $items->map(function ($item) {
                $reporterDisplay = $item->reporter
                    ? $item->reporter->name . ($item->reporter->username ? ' (@' . $item->reporter->username . ')' : '')
                    : 'Pengguna tidak dikenal';

                return [
                    'id' => $item->id,
                    'reporter_id' => $item->reporter_id,
                    'reporter' => $reporterDisplay,
                    'reason' => $item->reason,
                    'status' => $item->status,
                    'status_label' => $item->status_label,
                    'reported_at_human' => $item->created_at?->locale('id')->diffForHumans() ?? '-',
                    'reported_at_formatted' => $item->created_at?->format('d M Y, H:i') ?? '-',
                ];
            })->values();

            $groupStatus = 'dismissed';
            if ($items->contains('status', 'new')) {
                $groupStatus = 'new';
            } elseif ($items->contains('status', 'reviewed')) {
                $groupStatus = 'reviewed';
            } elseif ($items->contains('status', 'resolved')) {
                $groupStatus = 'resolved';
            }

            $groupStatusLabel = match ($groupStatus) {
                'new' => 'Baru',
                'reviewed' => 'Ditinjau',
                'resolved' => 'Selesai',
                'dismissed' => 'Diabaikan',
                default => ucfirst($groupStatus),
            };

            $firstItem = $mappedItems->first();
            $uniqueReporters = $items->pluck('reporter_id')->filter()->unique()->count();
            $distinctReasons = $items->pluck('reason')->map(fn ($r) => trim((string) $r))->filter()->unique()->count();

            $representative->display_title = $targetMeta['display_title'];
            $representative->display_meta = $targetMeta['display_meta'];
            $representative->target_url = $targetMeta['target_url'];
            $representative->can_delete_target = $targetMeta['can_delete_target'];
            $representative->reporter_display = $firstItem['reporter'];
            $representative->reported_at_human = $firstItem['reported_at_human'];
            $representative->reported_at_formatted = $firstItem['reported_at_formatted'];
            $representative->report_count = $items->count();
            $representative->active_count = $items->whereIn('status', ['new', 'reviewed'])->count();
            $representative->unique_reporters_count = $uniqueReporters > 0 ? $uniqueReporters : $items->count();
            $representative->distinct_reasons_count = $distinctReasons;
            $representative->group_status = $groupStatus;
            $representative->group_status_label = $groupStatusLabel;
            $representative->group_items = $mappedItems->all();
            $representative->latest_timestamp = $representative->created_at?->timestamp ?? 0;

            return $representative;
        })->values();

        if ($search !== '') {
            $term = mb_strtolower($search);
            $groups = $groups->filter(function ($group) use ($term) {
                if (
                    str_contains(mb_strtolower($group->display_title), $term)
                    || str_contains(mb_strtolower($group->display_meta), $term)
                    || str_contains(mb_strtolower($group->type_label), $term)
                    || str_contains(mb_strtolower($group->group_status_label), $term)
                ) {
                    return true;
                }

                foreach ($group->group_items as $item) {
                    if (
                        str_contains(mb_strtolower($item['reporter']), $term)
                        || str_contains(mb_strtolower($item['reason']), $term)
                    ) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $statusPriority = ['new' => 0, 'reviewed' => 1, 'resolved' => 2, 'dismissed' => 3];

        $groups = $groups->sort(function ($a, $b) use ($sort, $statusPriority) {
            if ($sort === 'most_reported') {
                return ($b->report_count <=> $a->report_count)
                    ?: ($b->active_count <=> $a->active_count)
                    ?: ($b->latest_timestamp <=> $a->latest_timestamp);
            }

            if ($sort === 'priority') {
                $pa = $statusPriority[$a->group_status] ?? 4;
                $pb = $statusPriority[$b->group_status] ?? 4;

                return ($pa <=> $pb)
                    ?: ($b->report_count <=> $a->report_count)
                    ?: ($b->latest_timestamp <=> $a->latest_timestamp);
            }

            return $b->latest_timestamp <=> $a->latest_timestamp;
        })->values();

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pagedItems = $groups->slice(($page - 1) * $perPage, $perPage)->values();

        $reports = (new LengthAwarePaginator(
            $pagedItems,
            $groups->count(),
            $perPage,
            $page,
            ['path' => route('admin.reports.index')]
        ))->withQueryString();

        return view('admin.reports', compact(
            'reports',
            'sidebarStats',
            'statusFilter',
            'typeFilter',
            'sort',
            'search',
            'statusCounts',
            'typeCounts'
        ));
    }

    private function formatReportTargetMeta(Report $report, $target): array
    {
        $title = 'Konten tidak tersedia';
        $meta = 'Target sudah tidak tersedia';
        $targetUrl = null;
        $canDeleteTarget = $target !== null;

        if ($report->reportable_type === 'community' && $target) {
            $title = 'c/' . $target->name;
            $creator = $target->creator?->name ?? 'Administrator';
            $meta = 'dibuat oleh ' . $creator;
            $targetUrl = route('communities.show', $target->name);
        } elseif ($report->reportable_type === 'post' && $target) {
            $title = '"' . $target->title . '"';
            $comm = $target->community ? 'c/' . $target->community->name : 'Postingan umum';
            $authorName = $target->user?->name ?? 'Pengguna';
            $meta = $comm . ' · oleh ' . $authorName;
            $targetUrl = route('posts.show', $target);
        } elseif ($report->reportable_type === 'comment' && $target) {
            $postTitle = $target->post?->title ?? 'Postingan';
            $title = 'Komentar pada "' . str($postTitle)->limit(38) . '"';
            $comm = $target->post?->community ? 'c/' . $target->post->community->name : 'Postingan umum';
            $snippet = $target->deleted_at ? '[Komentar telah dihapus]' : '"' . str($target->content)->limit(55) . '"';
            $meta = $comm . ' · ' . $snippet;
            $targetUrl = $target->post ? route('posts.show', $target->post) : null;
            $canDeleteTarget = $target->deleted_at === null;
        } elseif ($report->reportable_type === 'user' && $target) {
            $title = $target->username ? '@' . $target->username : $target->name;
            $meta = $target->name;
            $targetUrl = $target->username ? route('profile.show', ['user' => $target->username]) : null;
            $canDeleteTarget = ! $target->isAdmin() && $target->id !== Auth::id();
        }

        return [
            'display_title' => $title,
            'display_meta' => $meta,
            'target_url' => $targetUrl,
            'can_delete_target' => $canDeleteTarget,
        ];
    }

    public function reviewReport(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status'         => ['required', 'in:reviewed,resolved,dismissed'],
            'apply_to_group' => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'status'      => $validated['status'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ];

        $updatedCount = 1;

        if ($request->boolean('apply_to_group', false)) {
            $groupQuery = Report::where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id);

            $activeQuery = (clone $groupQuery)->whereIn('status', ['new', 'reviewed']);
            $updatedCount = $activeQuery->count() > 0
                ? $activeQuery->update($payload)
                : $groupQuery->update($payload);

            $report->refresh();
        } else {
            $report->update($payload);
        }

        $msg = $updatedCount > 1
            ? "{$updatedCount} laporan untuk target ini diperbarui menjadi {$report->status_label}."
            : "Status laporan #{$report->id} diperbarui menjadi {$report->status_label}.";

        return response()->json([
            'success'       => true,
            'status'        => $report->status,
            'status_label'  => $report->status_label,
            'updated_count' => $updatedCount,
            'message'       => $msg,
        ]);
    }

    public function deleteReport(Request $request, Report $report)
    {
        $deleteTarget = $request->boolean('delete_target', false);
        $applyToGroup = $request->boolean('apply_to_group', false);

        if ($deleteTarget) {
            $target = $report->resolveTarget();
            if ($target instanceof Community) {
                $target->delete();
            } elseif ($target instanceof Post) {
                $target->delete();
            } elseif ($target instanceof Comment) {
                $target->content = '';
                $target->deleted_at = now();
                $target->save();
            } elseif ($target instanceof User && ! $target->isAdmin() && $target->id !== Auth::id() && SafeSchema::hasColumn('users', 'is_blocked')) {
                $payload = ['is_blocked' => true];
                if (SafeSchema::hasColumn('users', 'blocked_reason')) {
                    $payload['blocked_reason'] = $report->reason;
                }
                $target->update($payload);
                ModerationNotification::send(
                    actor: Auth::user(),
                    actionType: 'block_user',
                    targetUser: $target
                );
            }
        }

        if ($applyToGroup) {
            $deletedCount = Report::where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id)
                ->delete();
        } else {
            $report->delete();
            $deletedCount = 1;
        }

        $message = $deleteTarget
            ? 'Target pelanggaran dan laporan berhasil ditindak.'
            : ($deletedCount > 1
                ? "{$deletedCount} laporan untuk target ini berhasil dihapus."
                : 'Laporan berhasil dihapus.');

        return response()->json([
            'success'       => true,
            'deleted_count' => $deletedCount,
            'message'       => $message,
        ]);
    }

    public function storeReport(Request $request)
    {
        if (! SafeSchema::hasTable('reports')) {
            return $this->reportErrorResponse(
                $request,
                'Fitur laporan belum aktif di database. Jalankan php artisan migrate terlebih dahulu.',
                503
            );
        }

        $validated = $request->validate([
            'reportable_type' => ['required', 'in:community,post,comment,user'],
            'reportable_id'   => ['required', 'integer', 'min:1'],
            'reason'          => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $target = match ($validated['reportable_type']) {
            'community' => Community::find($validated['reportable_id']),
            'post'      => Post::find($validated['reportable_id']),
            'comment'   => Comment::find($validated['reportable_id']),
            'user'      => User::find($validated['reportable_id']),
            default     => null,
        };

        if (! $target || ($target instanceof Comment && $target->deleted_at !== null)) {
            return $this->reportErrorResponse(
                $request,
                'Target yang dilaporkan tidak ditemukan atau sudah dihapus.',
                $target ? 422 : 404
            );
        }

        $ownerId = match (true) {
            $target instanceof Community => $target->created_by,
            $target instanceof Post      => $target->user_id,
            $target instanceof Comment   => $target->user_id,
            $target instanceof User      => $target->id,
            default                      => null,
        };

        if ((int) $ownerId === (int) Auth::id()) {
            return $this->reportErrorResponse(
                $request,
                'Anda tidak dapat melaporkan konten atau akun Anda sendiri.',
                422
            );
        }

        $alreadyReported = Report::where('reporter_id', Auth::id())
            ->where('reportable_type', $validated['reportable_type'])
            ->where('reportable_id', $validated['reportable_id'])
            ->whereIn('status', ['new', 'reviewed'])
            ->exists();

        if ($alreadyReported) {
            return $this->reportErrorResponse(
                $request,
                'Anda sudah melaporkan target ini dan laporan sedang dalam proses tinjauan.',
                422
            );
        }

        $report = Report::create([
            'reporter_id'     => Auth::id(),
            'reportable_type' => $validated['reportable_type'],
            'reportable_id'   => $validated['reportable_id'],
            'reason'          => trim($validated['reason']),
            'status'          => 'new',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan Anda berhasil dikirim untuk ditinjau Admin.',
                'report'  => $report,
            ], 201);
        }

        return back()->with('status', 'Laporan berhasil dikirim untuk ditinjau Admin.');
    }

    private function reportErrorResponse(Request $request, string $message, int $status)
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], $status)
            : back()->with('error', $message);
    }

    public function manageRoles(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $users = User::with(['createdCommunities', 'moderatedCommunities'])
            ->when($search !== '', function ($q) use ($search) {
                $term = '%' . mb_strtolower($search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw("LOWER(COALESCE(username, '')) LIKE ?", [$term])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
                });
            })
            ->orderByRaw("case when role = 'admin' then 0 else 1 end asc")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $sidebarStats = $this->sidebarSummary();

        return view('admin.roles', compact('users', 'search', 'sidebarStats'));
    }
}
