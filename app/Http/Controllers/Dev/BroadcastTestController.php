<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'dev', description: 'Development-only utilities')]
class BroadcastTestController extends BaseController
{
    #[OA\Post(
        path: '/v1/dev/notifications/test',
        operationId: 'devSendTestNotification',
        summary: 'DEV: Send a test notification to a user (broadcast + database)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id','title','message'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                    new OA\Property(property: 'title', type: 'string', example: 'Test Notification'),
                    new OA\Property(property: 'message', type: 'string', example: 'This is a test message'),
                    new OA\Property(property: 'data', type: 'object', example: ['key' => 'value']),
                ]
            )
        ),
        tags: ['dev'],
        responses: [new OA\Response(response: 200, description: 'Test notification sent')]
    )]
    public function send(Request $request): JsonResponse
    {
        $v = $request->validate([
            'user_id' => ['required','integer', Rule::exists('users','id')],
            'title'   => ['required','string','max:255'],
            'message' => ['required','string'],
            'data'    => ['sometimes','array'],
        ]);

        $user = User::findOrFail($v['user_id']);

        $user->notify(new GeneralNotification(
            title:   $v['title'],
            message: $v['message'],
            data:    $v['data'] ?? [],
            userId:  $user->id
        ));

        return $this->sendResponse(['user_id' => $user->id], 'dev.notifications.test_sent');
    }
}
