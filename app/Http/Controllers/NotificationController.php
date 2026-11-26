<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // Menampilkan daftar notifikasi pengguna
    public function index(Request $request): View
    {
       $filter = $request->query('filter', 'all');

        $canSeeModeration = $request->user()->isAdmin()
            || $request->user()->moderatedCommunities()->exists()
            || $request->user()->createdCommunities()->exists();

        $allowedFilters = ['all', 'comment', 'reply', 'vote', 'moderation'];

        if (!in_array($filter, $allowedFilters, true)) {
            $filter = 'all';
        }

        if ($filter === 'moderation' && !$canSeeModeration) {
            $filter = 'all';
        }

        $query = $request->user()->notifications()->latest();
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';

        if (!$canSeeModeration) {
            if ($isPgsql) {
                $query->whereRaw(
                    "(data::jsonb ->> 'category') IS DISTINCT FROM 'moderation'"
                );
            } else {
                $query->where(function ($q) {
                    $q->whereNull('data->category')
                        ->orWhere('data->category', '!=', 'moderation');
                });
            }
        }

        if ($filter !== 'all') {
            if ($isPgsql) {
                $query->whereRaw(
                    "(data::jsonb ->> 'category') = ?",
                    [$filter]
                );
            } else {
                $query->where('data->category', $filter);
            }
        }

        $notifications = $query
            ->paginate(10)
            ->withQueryString();

        // Menghitung notifikasi yang belum dibaca
        $unreadQuery = $request->user()->unreadNotifications();
        if (!$canSeeModeration) {
            if ($isPgsql) {
                $unreadQuery->whereRaw(
                    "(data::jsonb ->> 'category') IS DISTINCT FROM 'moderation'"
                );
            } else {
                $unreadQuery->where(function ($q) {
                    $q->whereNull('data->category')
                        ->orWhere('data->category', '!=', 'moderation');
                });
            }
        }
        $unreadCount = $unreadQuery->count();

        return view('notifications.index', compact(
            'notifications',
            'unreadCount',
            'filter',
            'canSeeModeration'
        ));
    }

    // Menandai satu notifikasi sebagai sudah dibaca
    public function markAsRead(
        Request $request,
        string $notification
    ): RedirectResponse {

        $item = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        $item->markAsRead();

        // Mengarahkan pengguna ke postingan terkait
        $targetUrl = data_get($item->data, 'url');

        if (is_string($targetUrl) && $targetUrl !== '') {
            $targetHost = parse_url($targetUrl, PHP_URL_HOST);

            if ($targetHost === null || $targetHost === $request->getHost()) {
                return redirect()->to($targetUrl);
            }
        }

        return redirect()->route('notifications.index');
    }

    // Menandai seluruh notifikasi sebagai sudah dibaca
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return back()->with(
            'success',
            'Semua notifikasi berhasil ditandai sebagai dibaca.'
        );
    }
}