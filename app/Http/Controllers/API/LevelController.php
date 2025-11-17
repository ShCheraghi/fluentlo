<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\LevelStoreRequest;
use App\Http\Requests\LevelUpdateRequest;
use App\Http\Resources\LevelResource;
use App\Models\Level;
use App\Services\LevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'levels', description: 'Language levels management')]
class LevelController extends BaseController
{
    public function __construct(
        private readonly LevelService $levelService
    ) {}

    // ---------------------------
    // GET /v1/app/levels
    // ---------------------------
    #[OA\Get(
        path: '/v1/app/levels',
        operationId: 'getLevels',
        summary: 'Get all levels',
        tags: ['levels'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Levels retrieved',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'code', type: 'string'),
                            new OA\Property(property: 'name_fa', type: 'string'),
                            new OA\Property(property: 'name_en', type: 'string'),
                            new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                            new OA\Property(property: 'description_en', type: 'string', nullable: true),
                            new OA\Property(property: 'order', type: 'integer'),
                            new OA\Property(property: 'icon', type: 'string', nullable: true),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        ],
                        type: 'object'
                    )
                )
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $levels = $this->levelService->all();

        return $this->sendResponse(
            LevelResource::collection($levels),
            'levels.retrieved'
        );
    }

    // ---------------------------
    // POST /v1/app/levels
    // ---------------------------
    #[OA\Post(
        path: '/v1/app/levels',
        operationId: 'createLevel',
        summary: 'Create a new level',
        security: [['sanctum' => []]],
        tags: ['levels'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name_fa', 'name_en', 'order'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', maxLength: 10),
                    new OA\Property(property: 'name_fa', type: 'string', maxLength: 255),
                    new OA\Property(property: 'name_en', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                    new OA\Property(property: 'description_en', type: 'string', nullable: true),
                    new OA\Property(property: 'order', type: 'integer', minimum: 1),
                    new OA\Property(property: 'icon', type: 'string', maxLength: 50, nullable: true),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Level created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'code', type: 'string'),
                        new OA\Property(property: 'name_fa', type: 'string'),
                        new OA\Property(property: 'name_en', type: 'string'),
                        new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                        new OA\Property(property: 'description_en', type: 'string', nullable: true),
                        new OA\Property(property: 'order', type: 'integer'),
                        new OA\Property(property: 'icon', type: 'string', nullable: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(LevelStoreRequest $request): JsonResponse
    {
        $level = $this->levelService->create($request->validated());

        return $this->sendResponse(
            new LevelResource($level),
            'levels.created',
            201
        );
    }

    // ---------------------------
    // GET /v1/app/levels/{id}
    // ---------------------------
    #[OA\Get(
        path: '/v1/app/levels/{id}',
        operationId: 'showLevel',
        summary: 'Get level details',
        tags: ['levels'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Level details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'code', type: 'string'),
                        new OA\Property(property: 'name_fa', type: 'string'),
                        new OA\Property(property: 'name_en', type: 'string'),
                        new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                        new OA\Property(property: 'description_en', type: 'string', nullable: true),
                        new OA\Property(property: 'order', type: 'integer'),
                        new OA\Property(property: 'icon', type: 'string', nullable: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function show(Level $level): JsonResponse
    {
        return $this->sendResponse(
            new LevelResource($level),
            'levels.show'
        );
    }

    // ---------------------------
    // PUT /v1/app/levels/{id}
    // ---------------------------
    #[OA\Put(
        path: '/v1/app/levels/{id}',
        operationId: 'updateLevel',
        summary: 'Update a level',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name_fa', 'name_en', 'order'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', maxLength: 10),
                    new OA\Property(property: 'name_fa', type: 'string', maxLength: 255),
                    new OA\Property(property: 'name_en', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                    new OA\Property(property: 'description_en', type: 'string', nullable: true),
                    new OA\Property(property: 'order', type: 'integer', minimum: 1),
                    new OA\Property(property: 'icon', type: 'string', maxLength: 50, nullable: true),
                ],
                type: 'object'
            )
        ),
        tags: ['levels'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Level updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'code', type: 'string'),
                        new OA\Property(property: 'name_fa', type: 'string'),
                        new OA\Property(property: 'name_en', type: 'string'),
                        new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                        new OA\Property(property: 'description_en', type: 'string', nullable: true),
                        new OA\Property(property: 'order', type: 'integer'),
                        new OA\Property(property: 'icon', type: 'string', nullable: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function update(LevelUpdateRequest $request, Level $level): JsonResponse
    {
        $level = $this->levelService->update($level, $request->validated());

        return $this->sendResponse(
            new LevelResource($level),
            'levels.updated'
        );
    }

    // ---------------------------
    // DELETE /v1/app/levels/{id}
    // ---------------------------
    #[OA\Delete(
        path: '/v1/app/levels/{id}',
        operationId: 'deleteLevel',
        summary: 'Delete a level',
        security: [['sanctum' => []]],
        tags: ['levels'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Level deleted'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function destroy(Level $level): JsonResponse
    {
        $this->levelService->delete($level);

        return $this->sendResponse(
            null,
            'levels.deleted'
        );
    }

    // ---------------------------
    // POST /v1/app/levels/reorder
    // ---------------------------
    #[OA\Post(
        path: '/v1/app/levels/reorder',
        operationId: 'reorderLevels',
        summary: 'Reorder levels',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'order', type: 'integer'),
                            ],
                            type: 'object'
                        )
                    )
                ],
                type: 'object'
            )
        ),
        tags: ['levels'],
        responses: [
            new OA\Response(response: 200, description: 'Levels reordered')
        ]
    )]
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'         => ['required', 'array'],
            'items.*.id'    => ['required', 'integer', 'exists:levels,id'],
            'items.*.order' => ['required', 'integer', 'min:1'],
        ]);

        $this->levelService->reorder($data['items']);

        return $this->sendResponse(
            null,
            'levels.reordered'
        );
    }
}
