<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'notifications', description: 'User notifications management')]
class NotificationController extends BaseController
{
    #[OA\Get(
        path: '/v1/app/notifications',
        operationId: 'getUserNotifications',
        summary: 'Get user notifications',
        security: [['sanctum' => []]],
        tags: ['notifications'],
        parameters: [
            new OA\Parameter(name: 'unread_only', in: 'query', schema: new OA\Schema(type: 'boolean', example: false)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', example: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Notifications retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $q = $request->user()->notifications()->latest();

        if ($request->boolean('unread_only')) {
            $q->whereNull('read_at');
        }

        $page = $q->paginate($request->integer('per_page', 15));

        return $this->sendResponse([
            'notifications' => $page->items(),
            'unread_count'  => $request->user()->unreadNotifications()->count(),
            'pagination'    => [
                'current_page' => $page->currentPage(),
                'total'        => $page->total(),
                'per_page'     => $page->perPage(),
            ],
        ], 'notifications.retrieved');
    }

    #[OA\Post(
        path: '/v1/app/notifications/{id}/read',
        operationId: 'markNotificationAsRead',
        summary: 'Mark notification as read',
        security: [['sanctum' => []]],
        tags: ['notifications'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Marked as read'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $n = $request->user()->notifications()->where('id', $id)->first();
        if (!$n) return $this->sendError('notifications.not_found', [], 404);

        $n->markAsRead();
        return $this->sendResponse(null, 'notifications.marked_read');
    }

    #[OA\Post(
        path: '/v1/app/notifications/read-all',
        operationId: 'markAllNotificationsAsRead',
        summary: 'Mark all notifications as read',
        security: [['sanctum' => []]],
        tags: ['notifications'],
        responses: [new OA\Response(response: 200, description: 'All marked as read')]
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->chunkById(100, fn($chunk) => $chunk->each->markAsRead());
        return $this->sendResponse(null, 'notifications.all_marked_read');
    }

    #[OA\Delete(
        path: '/v1/app/notifications/{id}',
        operationId: 'deleteNotification',
        summary: 'Delete notification',
        security: [['sanctum' => []]],
        tags: ['notifications'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        $n = $request->user()->notifications()->where('id', $id)->first();
        if (!$n) return $this->sendError('notifications.not_found', [], 404);

        $n->delete();
        return $this->sendResponse(null, 'notifications.deleted');
    }
}
