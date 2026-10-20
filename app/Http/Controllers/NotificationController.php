<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()
                ->notifications()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20),
            'unreadNotificationCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
