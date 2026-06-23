<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    // =========================================================================
    // INDEX — List the authenticated user's notifications
    // GET /api/notifications
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($perPage);

        $notifications->getCollection()->transform(function (AppNotification $notification) {
            $notification->title = $notification->payload['title'] ?? null;
            $notification->body = $notification->payload['body'] ?? null;
            return $notification;
        });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    // =========================================================================
    // UNREAD COUNT — Count of unread notifications for the authenticated user
    // GET /api/notifications/unread-count
    // =========================================================================
    public function unreadCount(Request $request): JsonResponse
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    // =========================================================================
    // MARK AS READ — Mark a single notification as read
    // PATCH /api/notifications/{notification}/read
    // =========================================================================
    public function markAsRead(Request $request, AppNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only manage your own notifications.',
            ], 403);
        }

        $notification->is_read = true;
        $notification->save();

        $notification->title = $notification->payload['title'] ?? null;
        $notification->body = $notification->payload['body'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => $notification,
        ]);
    }

    // =========================================================================
    // MARK ALL AS READ — Mark all of the authenticated user's notifications as read
    // POST /api/notifications/read-all
    // =========================================================================
    public function markAllAsRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
