<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Community;
use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\Report;
use App\Services\ImageService;
use App\Support\SafeSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(Request $request, Community $community): View
    {
        Gate::authorize('moderate', $community);

        $tab = in_array($request->get('tab', 'queue'), ['queue', 'logs'], true)
            ? $request->get('tab', 'queue')
            : 'queue';

        $allowedStatuses = ['queue', 'all', 'new', 'reviewed', 'resolved', 'dismissed'];
        $allowedTypes = ['all', 'post', 'comment'];
        $allowedActions = ['all', 'approve', 'hide', 'delete', 'dismiss'];
        $allowedSorts = ['latest', 'most_reported', 'priority'];

        $statusFilter = in_array($request->get('status', 'queue'), $allowedStatuses, true)
            ? $request->get('status', 'queue')
            : 'queue';
        $typeFilter = in_array($request->get('type', 'all'), $allowedTypes, true)
            ? $request->get('type', 'all')
            : 'all';
        $actionFilter = in_array($request->get('action', 'all'), $allowedActions, true)
            ? $request->get('action', 'all')
            : 'all';
        $sort = in_array($request->get('sort', 'latest'), $allowedSorts, true)
            ? $request->get('sort', 'latest')
            : 'latest';
        $search = trim((string) $request->get('search', ''));

        $statusCounts = [
            'queue' => 0,
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
        ];
        $actionCounts = [
            'all' => 0,
            'approve' => 0,
            'hide' => 0,
            'delete' => 0,
            'dismiss' => 0,
        ];

        $community->loadCount(['members', 'posts']);
        $rules = $community->rules ?? [];

        $hasReportsTable = SafeSchema::hasTable('reports');
        $hasLogsTable = SafeSchema::hasTable('moderation_logs');

        if ($hasLogsTable) {
            $logRaw = ModerationLog::where('community_id', $community->id)
                ->selectRaw('action, count(*) as total')
                ->groupBy('action')
                ->pluck('total', 'action');

            $actionCounts = [
                'all' => (int) $logRaw->sum(),
                'approve' => (int) ($logRaw['approve'] ?? 0),
                'hide' => (int) ($logRaw['hide'] ?? 0),
                'delete' => (int) ($logRaw['delete'] ?? 0),
                'dismiss' => (int) ($logRaw['dismiss'] ?? 0),
            ];
        }

        $logs = new LengthAwarePaginator([], 0, 15);
        $reports = new LengthAwarePaginator([], 0, 15);

        if ($hasReportsTable) {
            $baseQuery = $this->communityReportsQuery($community);

            $statusRaw = (clone $baseQuery)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $newCount = (int) ($statusRaw['new'] ?? 0);
            $reviewedCount = (int) ($statusRaw['reviewed'] ?? 0);

            $statusCounts = [
                'queue' => $newCount + $reviewedCount,
                'all' => (int) $statusRaw->sum(),
                'new' => $newCount,
                'reviewed' => $reviewedCount,
                'resolved' => (int) ($statusRaw['resolved'] ?? 0),
                'dismissed' => (int) ($statusRaw['dismissed'] ?? 0),
            ];
        }

        if ($tab === 'logs') {
            if ($hasLogsTable) {
                $logTypeRaw = ModerationLog::where('community_id', $community->id)
                    ->when($actionFilter !== 'all', fn ($q) => $q->where('action', $actionFilter))
                    ->selectRaw('target_type, count(*) as total')
                    ->groupBy('target_type')
                    ->pluck('total', 'target_type');

                $typeCounts = [
                    'all' => (int) $logTypeRaw->sum(),
                    'post' => (int) ($logTypeRaw['post'] ?? 0),
                    'comment' => (int) ($logTypeRaw['comment'] ?? 0),
                ];

                $logsQuery = ModerationLog::where('community_id', $community->id)
                    ->with(['moderator', 'targetAuthor'])
                    ->when($actionFilter !== 'all', fn ($q) => $q->where('action', $actionFilter))
                    ->when($typeFilter !== 'all', fn ($q) => $q->where('target_type', $typeFilter))
                    ->when($search !== '', function (Builder $q) use ($search) {
                        $term = '%' . mb_strtolower($search) . '%';
                        $q->where(function (Builder $sub) use ($term) {
                            $sub->whereRaw('LOWER(target_snapshot) LIKE ?', [$term])
                                ->orWhereRaw("LOWER(COALESCE(note, '')) LIKE ?", [$term])
                                ->orWhereHas('moderator', fn ($m) => $m->whereRaw('LOWER(name) LIKE ?', [$term]))
                                ->orWhereHas('targetAuthor', fn ($a) => $a->whereRaw('LOWER(name) LIKE ?', [$term]));
                        });
                    })
                    ->latest();

                $logs = $logsQuery->paginate(15)->withQueryString();
            }

            return view('communities.moderation', compact(
                'community',
                'rules',
                'tab',
                'reports',
                'logs',
                'statusFilter',
                'typeFilter',
                'actionFilter',
                'sort',
                'search',
                'statusCounts',
                'typeCounts',
                'actionCounts'
            ));
        }

        if (! $hasReportsTable) {
            return view('communities.moderation', compact(
                'community',
                'rules',
                'tab',
                'reports',
                'logs',
                'statusFilter',
                'typeFilter',
                'actionFilter',
                'sort',
                'search',
                'statusCounts',
                'typeCounts',
                'actionCounts'
            ));
        }

        $typeQuery = clone $baseQuery;
        if ($statusFilter === 'queue') {
            $typeQuery->whereIn('status', ['new', 'reviewed']);
        } elseif ($statusFilter !== 'all') {
            $typeQuery->where('status', $statusFilter);
        }

        $typeRaw = $typeQuery
            ->selectRaw('reportable_type, count(*) as total')
            ->groupBy('reportable_type')
            ->pluck('total', 'reportable_type');

        $typeCounts = [
            'all' => (int) $typeRaw->sum(),
            'post' => (int) ($typeRaw['post'] ?? 0),
            'comment' => (int) ($typeRaw['comment'] ?? 0),
        ];

        $reportsQuery = (clone $baseQuery)
            ->with(['reporter', 'reviewer'])
            ->when($statusFilter === 'queue', fn ($q) => $q->whereIn('status', ['new', 'reviewed']))
            ->when(! in_array($statusFilter, ['queue', 'all'], true), fn ($q) => $q->where('status', $statusFilter))
            ->when($typeFilter !== 'all', fn ($q) => $q->where('reportable_type', $typeFilter))
            ->latest();

        $matchingReports = $reportsQuery->get();

        $resolvedTargets = [];
        $grouped = $matchingReports->groupBy(fn ($r) => $r->reportable_type . ':' . $r->reportable_id);

        $groups = $grouped->map(function ($items) use (&$resolvedTargets, $community) {
            $representative = $items->first();
            $cacheKey = $representative->reportable_type . ':' . $representative->reportable_id;

            if (! array_key_exists($cacheKey, $resolvedTargets)) {
                $resolvedTargets[$cacheKey] = $representative->resolveTarget();
            }

            $targetMeta = $this->formatCommunityTargetMeta($representative, $resolvedTargets[$cacheKey], $community);

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
            $representative->display_excerpt = $targetMeta['display_excerpt'];
            $representative->display_meta = $targetMeta['display_meta'];
            $representative->author_display = $targetMeta['author_display'];
            $representative->target_url = $targetMeta['target_url'];
            $representative->target_state = $targetMeta['target_state'];
            $representative->target_state_label = $targetMeta['target_state_label'];
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
                    || str_contains(mb_strtolower($group->display_excerpt), $term)
                    || str_contains(mb_strtolower($group->display_meta), $term)
                    || str_contains(mb_strtolower($group->author_display), $term)
                    || str_contains(mb_strtolower($group->type_label), $term)
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
            ['path' => route('communities.moderation.index', $community->name)]
        ))->withQueryString();

        return view('communities.moderation', compact(
            'community',
            'rules',
            'tab',
            'reports',
            'logs',
            'statusFilter',
            'typeFilter',
            'actionFilter',
            'sort',
            'search',
            'statusCounts',
            'typeCounts',
            'actionCounts'
        ));
    }

    private function communityReportsQuery(Community $community): Builder
    {
        return Report::query()->where(function (Builder $q) use ($community) {
            $q->where(function (Builder $sub) use ($community) {
                $sub->where('reports.reportable_type', 'post')
                    ->whereExists(function ($exists) use ($community) {
                        $exists->selectRaw('1')
                            ->from('posts')
                            ->whereColumn('posts.id', 'reports.reportable_id')
                            ->where('posts.community_id', $community->id);
                    });
            })->orWhere(function (Builder $sub) use ($community) {
                $sub->where('reports.reportable_type', 'comment')
                    ->whereExists(function ($exists) use ($community) {
                        $exists->selectRaw('1')
                            ->from('comments')
                            ->join('posts', 'posts.id', '=', 'comments.post_id')
                            ->whereColumn('comments.id', 'reports.reportable_id')
                            ->where('posts.community_id', $community->id);
                    });
            });
        });
    }

    private function formatCommunityTargetMeta(Report $report, $target, Community $community): array
    {
        $title = 'Konten tidak tersedia';
        $excerpt = '';
        $meta = 'Target sudah dihapus';
        $authorDisplay = 'Pengguna tidak dikenal';
        $targetUrl = null;
        $targetState = 'deleted';
        $targetStateLabel = 'Terhapus';

        if ($report->reportable_type === 'post' && $target instanceof Post && (int) $target->community_id === (int) $community->id) {
            $author = $target->user;
            $authorDisplay = $author
                ? $author->name . ($author->username ? ' (@' . $author->username . ')' : '')
                : 'Pengguna tidak dikenal';
            $title = '"' . $target->title . '"';
            $excerpt = str((string) $target->content)->limit(120)->toString();
            $meta = 'Penulis: ' . $authorDisplay;
            $targetUrl = route('posts.show', $target);
            $isHidden = ($target->status ?? 'published') === 'hidden';
            $targetState = $isHidden ? 'hidden' : 'published';
            $targetStateLabel = $isHidden ? 'Disembunyikan' : 'Publik';
        } elseif ($report->reportable_type === 'comment' && $target instanceof Comment && (int) ($target->post?->community_id ?? 0) === (int) $community->id) {
            $author = $target->user;
            $authorDisplay = $author
                ? $author->name . ($author->username ? ' (@' . $author->username . ')' : '')
                : 'Pengguna tidak dikenal';
            $postTitle = $target->post?->title ?? 'Postingan';
            $title = 'Komentar pada "' . str($postTitle)->limit(45) . '"';
            $excerpt = $target->deleted_at !== null
                ? '[Komentar telah dihapus]'
                : str((string) $target->content)->limit(120)->toString();
            $meta = 'Penulis: ' . $authorDisplay;
            $targetUrl = $target->post ? route('posts.show', $target->post) . '#comment-' . $target->id : null;

            if ($target->deleted_at !== null) {
                $targetState = 'deleted';
                $targetStateLabel = 'Terhapus';
            } elseif ((bool) ($target->is_hidden ?? false)) {
                $targetState = 'hidden';
                $targetStateLabel = 'Disembunyikan';
            } else {
                $targetState = 'published';
                $targetStateLabel = 'Publik';
            }
        }

        return [
            'display_title' => $title,
            'display_excerpt' => $excerpt,
            'display_meta' => $meta,
            'author_display' => $authorDisplay,
            'target_url' => $targetUrl,
            'target_state' => $targetState,
            'target_state_label' => $targetStateLabel,
        ];
    }

    public function handleAction(Request $request, Community $community, Report $report): JsonResponse
    {
        Gate::authorize('moderate', $community);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,hide,delete,dismiss'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        $action = $validated['action'];
        $target = $report->resolveTarget();

        if (! $target) {
            return response()->json([
                'success' => false,
                'message' => 'Konten target tidak ditemukan atau sudah dihapus.',
            ], 404);
        }

        $belongsToCommunity = ($report->reportable_type === 'post' && $target instanceof Post && (int) $target->community_id === (int) $community->id)
            || ($report->reportable_type === 'comment' && $target instanceof Comment && (int) ($target->post?->community_id ?? 0) === (int) $community->id);

        if (! $belongsToCommunity) {
            return response()->json([
                'success' => false,
                'message' => "Konten ini bukan milik komunitas c/{$community->name} dan tidak dapat dimoderasi dari ruang ini.",
            ], 403);
        }

        if ($target instanceof Comment && $target->deleted_at !== null && $action !== 'dismiss') {
            return response()->json([
                'success' => false,
                'message' => 'Komentar ini sudah dihapus permanen.',
            ], 422);
        }

        $targetAuthorId = $target->user_id;
        if ($target instanceof Post) {
            $targetSnapshot = str((string) $target->title)->limit(120)->toString();
        } else {
            $postTitle = $target->post?->title ?? 'Postingan';
            $snippet = $target->deleted_at !== null ? '[komentar dihapus]' : str((string) $target->content)->limit(60)->toString();
            $targetSnapshot = 'Komentar pada "' . str($postTitle)->limit(45) . '": "' . $snippet . '"';
        }

        $targetState = 'published';
        $targetStateLabel = 'Publik';

        if ($action === 'approve') {
            if ($target instanceof Post) {
                $target->status = 'published';
                $target->save();
            } elseif ($target instanceof Comment && SafeSchema::hasColumn('comments', 'is_hidden') && $target->isHidden()) {
                $target->is_hidden = false;
                $target->save();
            }
            $reportOutcome = 'resolved';
        } elseif ($action === 'hide') {
            if ($target instanceof Post) {
                $target->status = 'hidden';
                $target->save();
            } elseif ($target instanceof Comment) {
                if (! SafeSchema::hasColumn('comments', 'is_hidden')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kolom status sembunyi komentar belum aktif di database. Jalankan php artisan migrate terlebih dahulu.',
                    ], 503);
                }
                $target->is_hidden = true;
                $target->save();
            }
            $targetState = 'hidden';
            $targetStateLabel = 'Disembunyikan';
            $reportOutcome = 'resolved';
        } elseif ($action === 'delete') {
            if ($target instanceof Post) {
                ImageService::delete($target->image_path);
                $target->delete();
            } elseif ($target instanceof Comment) {
                $target->content = '';
                $target->deleted_at = now();
                if (SafeSchema::hasColumn('comments', 'is_hidden')) {
                    $target->is_hidden = false;
                }
                $target->save();
            }
            $targetState = 'deleted';
            $targetStateLabel = 'Terhapus';
            $reportOutcome = 'resolved';
        } else {
            if ($target instanceof Post && ($target->status ?? 'published') === 'hidden') {
                $targetState = 'hidden';
                $targetStateLabel = 'Disembunyikan';
            } elseif ($target instanceof Comment && $target->deleted_at !== null) {
                $targetState = 'deleted';
                $targetStateLabel = 'Terhapus';
            } elseif ($target instanceof Comment && $target->isHidden()) {
                $targetState = 'hidden';
                $targetStateLabel = 'Disembunyikan';
            }
            $reportOutcome = 'dismissed';
        }

        $reportPayload = [
            'status'      => $reportOutcome,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ];

        $groupQuery = Report::where('reportable_type', $report->reportable_type)
            ->where('reportable_id', $report->reportable_id);
        $activeQuery = (clone $groupQuery)->whereIn('status', ['new', 'reviewed']);
        $updatedCount = $activeQuery->count() > 0
            ? $activeQuery->update($reportPayload)
            : (int) Report::where('id', $report->id)->update($reportPayload);

        $report->refresh();

        if (SafeSchema::hasTable('moderation_logs')) {
            $noteText = isset($validated['note']) ? trim((string) $validated['note']) : '';
            ModerationLog::create([
                'community_id'     => $community->id,
                'moderator_id'     => Auth::id(),
                'report_id'        => $report->id,
                'target_type'      => $report->reportable_type,
                'target_id'        => $report->reportable_id,
                'target_snapshot'  => $targetSnapshot,
                'target_author_id' => $targetAuthorId,
                'action'           => $action,
                'note'             => $noteText !== '' ? $noteText : null,
                'reports_affected' => max(1, (int) $updatedCount),
            ]);
        }

        $actionMsg = match ($action) {
            'approve' => 'Konten disetujui dan laporan ditandai selesai.',
            'hide'    => 'Konten disembunyikan dari publik dan laporan diselesaikan.',
            'delete'  => 'Konten berhasil dihapus dan laporan diselesaikan.',
            'dismiss' => 'Laporan diabaikan tanpa mengubah konten.',
        };

        return response()->json([
            'success'             => true,
            'action'              => $action,
            'report_status'       => $report->status,
            'report_status_label' => $report->status_label,
            'target_state'        => $targetState,
            'target_state_label'  => $targetStateLabel,
            'updated_count'       => $updatedCount,
            'message'             => $actionMsg,
        ]);
    }
}
