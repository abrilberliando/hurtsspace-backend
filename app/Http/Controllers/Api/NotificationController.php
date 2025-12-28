<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\SystemBroadcast;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    // 1. GET USER NOTIFICATIONS (Buat Lonceng di Frontend)
    public function index(Request $request)
    {
        $user = $request->user();
        // Ambil notifikasi database
        return response()->json([
            'unread_count' => $user->unreadNotifications->count(),
            'notifications' => $user->notifications()->take(10)->get()
        ]);
    }

    // 2. MARK AS READ
    public function markAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All marked as read']);
    }

    // 3. ADMIN: SEND BROADCAST
    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'url' => 'nullable|url',
            'target' => 'required|in:all,members', // Bisa nambah filter lain nanti
        ]);

        // Ambil target user
        $query = User::query();
        if ($request->target === 'members') {
            $query->where('role', 'member');
        }
        // Chunking biar server gak meledak kalau usernya ribuan
        $users = $query->get();

        // Kirim Notifikasi (Queue otomatis jalan karena class implement ShouldQueue)
        Notification::send($users, new SystemBroadcast(
            $request->title,
            $request->message,
            $request->url
        ));

        return response()->json([
            'message' => 'Broadcast sedang dikirim ke ' . $users->count() . ' pengguna.'
        ]);
    }
}
