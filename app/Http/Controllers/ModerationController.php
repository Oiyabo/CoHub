<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\Report;
use App\Support\SafeSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(Request $request, Community $community): View
    {
        Gate::authorize('moderate', $community);

        $allowedStatuses = ['queue', 'all', 'new', 'reviewed', 'resolved', 'dismissed'];
        $allowedTypes = ['all', 'post', 'comment'];
        $allowedSorts = ['latest', 'most_reported', 'priority'];

        $statusFilter = in_array($request->get('status', 'queue'), $allowedStatuses, true)
            ? $request->get('status', 'queue')
            : 'queue';
        $typeFilter = in_array($request->get('type', 'all'), $allowedTypes, true)
            ? $request->get('type', 'all')
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

        $community->loadCount(['members', 'posts']);
        $rules = $community->rules ?? [];

        if (! SafeSchema::hasTable('reports')) {
            $reports = new LengthAwarePaginator([], 0, 15);

            return view('communities.moderation', compact(
                'community',
                'rules',
                'reports',
                'statusFilter',
                'typeFilter',
                'sort',
                'search',
                'statusCounts',
                'typeCounts'
            ));
        }

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
            'reports',
            'statusFilter',
            'typeFilter',
            'sort',
            'search',
            'statusCounts',
            'typeCounts'
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
}
