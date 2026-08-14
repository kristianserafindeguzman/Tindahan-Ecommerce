<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = \App\Models\Notification::where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        return response()->json($notifications);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = \App\Models\Notification::where('user_id', $request->user()->user_id)
            ->where('notification_id', $id)
            ->firstOrFail();
        
        $notification->is_read = true;
        $notification->save();
        
        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        \App\Models\Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return response()->json(['message' => 'All marked as read']);
    }
}
