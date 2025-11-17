<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Level;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'units', description: 'Unit management')]
class UnitController extends BaseController
{
    public function __construct(
        private readonly UnitService $unitService
    )
    {
    }

    /**
     * GET /v1/app/levels/{levelId}/units
     * Get all units for a level
     */
    #[OA\Get(
        path: '/v1/app/levels/{levelId}/units',
        operationId: 'getUnits',
        summary: 'Get all units for a level',
        tags: ['units'],
        parameters: [
            new OA\Parameter(
                name: 'levelId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Units retrieved',
                content: new OA\JsonContent(type: 'array', items: new OA\Items())
            ),
            new OA\Response(response: 404, description: 'Level not found')
        ]
    )]
    public function index(Level $level): JsonResponse
    {
        $units = $this->unitService->getByLevel($level);

        return $this->sendResponse(
            UnitResource::collection($units),
            'units.retrieved'
        );
    }

    /**
     * GET /v1/app/units/{id}
     * Get single unit
     */
    #[OA\Get(
        path: '/v1/app/units/{id}',
        operationId: 'getUnit',
        summary: 'Get unit details',
        tags: ['units'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Unit details'),
            new OA\Response(response: 404, description: 'Unit not found')
        ]
    )]
    public function show(Unit $unit): JsonResponse
    {
        return $this->sendResponse(
            new UnitResource($unit->load('level')),
            'units.show'
        );
    }

    /**
     * POST /v1/app/units
     * Create unit (admin only)
     */
    #[OA\Post(
        path: '/v1/app/units',
        operationId: 'createUnit',
        summary: 'Create a new unit',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['level_id', 'sequence', 'title_fa', 'title_en'],
                    properties: [
                        new OA\Property(property: 'level_id', type: 'integer'),
                        new OA\Property(property: 'sequence', type: 'integer'),
                        new OA\Property(property: 'title_fa', type: 'string'),
                        new OA\Property(property: 'title_en', type: 'string'),
                        new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                        new OA\Property(property: 'description_en', type: 'string', nullable: true),
                        new OA\Property(property: 'introduction_fa', type: 'string', nullable: true),
                        new OA\Property(property: 'introduction_en', type: 'string', nullable: true),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'is_published', type: 'boolean', default: true),
                    ],
                    type: 'object'
                )
            )
        ),
        tags: ['units'],
        responses: [
            new OA\Response(response: 201, description: 'Unit created'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $data = $request->validated();

        // تبدیل مقادیر بولین
        $data['is_published'] = filter_var($data['is_published'], FILTER_VALIDATE_BOOLEAN);

        // Handle file upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('units', 'public');
            $data['image_url'] = $path;
            unset($data['image']);
        }

        $unit = $this->unitService->create($data);

        return $this->sendResponse(
            new UnitResource($unit),
            'units.created',
            201
        );
    }

    /**
     * PUT /v1/app/units/{id}
     * Update unit (admin only)
     */
    #[OA\Put(
        path: '/v1/app/units/{id}',
        operationId: 'updateUnit',
        summary: 'Update a unit',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: [],
                    properties: [
                        new OA\Property(property: 'sequence', type: 'integer'),
                        new OA\Property(property: 'title_fa', type: 'string'),
                        new OA\Property(property: 'title_en', type: 'string'),
                        new OA\Property(property: 'description_fa', type: 'string', nullable: true),
                        new OA\Property(property: 'description_en', type: 'string', nullable: true),
                        new OA\Property(property: 'introduction_fa', type: 'string', nullable: true),
                        new OA\Property(property: 'introduction_en', type: 'string', nullable: true),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'is_published', type: 'boolean'),
                    ],
                    type: 'object'
                )
            )
        ),
        tags: ['units'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Unit updated'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 404, description: 'Unit not found')
        ]
    )]
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $data = $request->validated();

        // تبدیل مقادیر بولین
        $data['is_published'] = filter_var($data['is_published'], FILTER_VALIDATE_BOOLEAN);

        // Handle file upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if (!empty($unit->image_url) && !Str::startsWith($unit->image_url, ['http://','https://'])) {
                Storage::disk('public')->delete($unit->image_url);
            }

            $path = $request->file('image')->store('units', 'public');
            $data['image_url'] = $path;
            unset($data['image']);
        }

        $unit = $this->unitService->update($unit, $data);

        return $this->sendResponse(
            new UnitResource($unit),
            'units.updated'
        );
    }

    /**
     * DELETE /v1/app/units/{id}
     * Delete unit (admin only)
     */
    #[OA\Delete(
        path: '/v1/app/units/{id}',
        operationId: 'deleteUnit',
        summary: 'Delete a unit',
        security: [['sanctum' => []]],
        tags: ['units'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Unit deleted'),
            new OA\Response(response: 404, description: 'Unit not found')
        ]
    )]
    public function destroy(Unit $unit): JsonResponse
    {
        // Delete associated image file
        if (!empty($unit->image_url) && !Str::startsWith($unit->image_url, ['http://', 'https://'])) {
            Storage::disk('public')->delete($unit->image_url);
        }

        $this->unitService->delete($unit);

        return $this->sendResponse(
            null,
            'units.deleted'
        );
    }
}
