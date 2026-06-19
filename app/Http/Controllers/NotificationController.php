<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->markAsRead();

        if ($notification->link) {
            return redirect()->to($notification->link);
        }

        return redirect()->route('notifications.index');
    }

    public function markAllRead(): RedirectResponse
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return redirect()->route('notifications.index')->with('success', 'ทำเครื่องหมายอ่านทั้งหมดแล้ว');
    }
}
