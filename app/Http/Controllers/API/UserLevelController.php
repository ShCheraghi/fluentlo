<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\UserLevelResource;
use App\Models\Level;
use App\Services\UserLevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'user-levels', description: 'User level management')]
class UserLevelController extends BaseController
{
    public function __construct(
        private readonly UserLevelService $userLevelService
    )
    {
    }

    // ---------------------------
    // GET /v1/app/user_levels/current-level
    // ---------------------------
    #[OA\Get(
        path: '/v1/app/user_levels/current-level',
        operationId: 'getCurrentLevel',
        summary: "Get user's current level",
        security: [['sanctum' => []]],
        tags: ['user-levels'],
        responses: [
            new OA\Response(
                response: 200,
                description: "User's current level",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'user_id', type: 'integer'),
                        new OA\Property(property: 'level_id', type: 'integer'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        new OA\Property(
                            property: 'level',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'code', type: 'string'),
                                new OA\Property(property: 'name_fa', type: 'string'),
                                new OA\Property(property: 'name_en', type: 'string'),
                                new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                                new OA\Property(property: 'description_en', type: 'string', nullable: true),
                                new OA\Property(property: 'order', type: 'integer'),
                                new OA\Property(property: 'icon', type: 'string', nullable: true),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'No current level')
        ]
    )]
    public function current(Request $request): JsonResponse
    {
        $currentLevel = $this->userLevelService->getCurrentLevel($request->user());

        if (!$currentLevel) {
            return $this->sendError('No current level', [], 404);
        }

        return $this->sendResponse(
            new UserLevelResource($currentLevel),
            'user-level.current'
        );
    }

    // ---------------------------
    // POST /v1/app/user_levels/start/{id}
    // ---------------------------
    #[OA\Post(
        path: '/v1/app/user_levels/start/{id}',
        operationId: 'startLevel',
        summary: 'Start a level',
        security: [['sanctum' => []]],
        tags: ['user-levels'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Level ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Level started',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'user_id', type: 'integer'),
                        new OA\Property(property: 'level_id', type: 'integer'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        new OA\Property(
                            property: 'level',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'code', type: 'string'),
                                new OA\Property(property: 'name_fa', type: 'string'),
                                new OA\Property(property: 'name_en', type: 'string'),
                                new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                                new OA\Property(property: 'description_en', type: 'string', nullable: true),
                                new OA\Property(property: 'order', type: 'integer'),
                                new OA\Property(property: 'icon', type: 'string', nullable: true),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Level not found')
        ]
    )]
    public function start(Request $request, Level $level): JsonResponse
    {
        $userLevel = $this->userLevelService->startLevel($request->user(), $level);

        return $this->sendResponse(
            new UserLevelResource($userLevel),
            'user-level.started',
            201
        );
    }

    // ---------------------------
    // POST /v1/user/next-level
    // ---------------------------
    #[OA\Post(
        path: '/v1/app/user_levels/next-level',
        operationId: 'moveToNextLevel',
        summary: 'Move to next level',
        security: [['sanctum' => []]],
        tags: ['user-levels'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Moved to next level',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'user_id', type: 'integer'),
                        new OA\Property(property: 'level_id', type: 'integer'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        new OA\Property(
                            property: 'level',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'code', type: 'string'),
                                new OA\Property(property: 'name_fa', type: 'string'),
                                new OA\Property(property: 'name_en', type: 'string'),
                                new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                                new OA\Property(property: 'description_en', type: 'string', nullable: true),
                                new OA\Property(property: 'order', type: 'integer'),
                                new OA\Property(property: 'icon', type: 'string', nullable: true),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'No current level or no more levels')
        ]
    )]
    public function nextLevel(Request $request): JsonResponse
    {
        $currentLevel = $this->userLevelService->getCurrentLevel($request->user());

        if (!$currentLevel) {
            return $this->sendError('No current level', [], 404);
        }

        $nextLevel = $this->userLevelService->moveToNextLevel($request->user(), $currentLevel->level);

        if (!$nextLevel) {
            return $this->sendError('No more levels', [], 404);
        }

        return $this->sendResponse(
            new UserLevelResource($nextLevel),
            'user-level.moved'
        );
    }
}
