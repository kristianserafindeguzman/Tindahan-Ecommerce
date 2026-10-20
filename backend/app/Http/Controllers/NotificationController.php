<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\RealtimePublisher;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->orderBy('notification_id', 'desc')
            ->limit(50)
            ->get();
        if ($request->boolean('sync')) {
            $query = Notification::where('user_id', $request->user()->user_id);

            return response()->json([
                'notifications' => $notifications,
                'unread_count' => (clone $query)->where('is_read', false)->count(),
                'total' => $query->count(),
            ])->header('Cache-Control', 'no-store');
        }

        return response()->json($notifications)->header('Cache-Control', 'no-store');
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->user_id)
            ->where('notification_id', $id)
            ->firstOrFail();

        $notification->is_read = true;
        $notification->save();

        RealtimePublisher::users([$request->user()->user_id], 'notifications');

        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Bulk updates do not fire Eloquent observers.
        RealtimePublisher::users([$request->user()->user_id], 'notifications');

        return response()->json(['message' => 'All marked as read']);
    }
}
