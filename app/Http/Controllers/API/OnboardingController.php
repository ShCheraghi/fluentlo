<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\StoreOnboardingScreenRequest;
use App\Http\Requests\UpdateOnboardingScreenRequest;
use App\Http\Resources\OnboardingScreenResource;
use App\Models\OnboardingScreen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(
    name: 'Onboarding',
    description: 'Onboarding screens for app (public) and admin management'
)]
class OnboardingController extends BaseController
{
    // ===================== App =====================

    #[OA\Get(
        path: '/v1/app/onboarding/screens',
        operationId: 'getOnboardingScreens',
        summary: 'Get all active onboarding screens (App)',
        tags: ['Onboarding'],
        responses: [new OA\Response(response: 200, description: 'List of onboarding screens')]
    )]
    public function getScreens(): JsonResponse
    {
        $screens = OnboardingScreen::active()
            ->ordered()
            ->get();

        $version = OnboardingScreen::currentVersion();

        return $this->sendResponse([
            'version'       => $version,
            'screens'       => OnboardingScreenResource::collection($screens),
            'total_screens' => $screens->count(),
        ], 'onboarding.screens_retrieved');
    }

    // ===================== Admin =====================

    #[OA\Get(
        path: '/v1/admin/onboarding-screens',
        operationId: 'adminGetOnboardingScreens',
        summary: 'Get all onboarding screens (Admin)',
        security: [['sanctum' => []]],
        tags: ['Onboarding'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of onboarding screens (admin)'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = OnboardingScreen::query()->ordered();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min($request->integer('per_page', 15), 100);
        $screens = $query->paginate($perPage);

        return $this->sendResponse([
            'screens'    => OnboardingScreenResource::collection($screens->items()),
            'pagination' => [
                'current_page' => $screens->currentPage(),
                'last_page'    => $screens->lastPage(),
                'per_page'     => $screens->perPage(),
                'total'        => $screens->total(),
                'from'         => $screens->firstItem(),
                'to'           => $screens->lastItem(),
            ],
        ], 'admin.onboarding.index_success');
    }

    #[OA\Post(
        path: '/v1/admin/onboarding-screens',
        operationId: 'adminStoreOnboardingScreen',
        summary: 'Create new onboarding screen (Admin)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['title','image','background_color','text_color','button_color','order_index'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', maxLength: 255),
                        new OA\Property(property: 'subtitle', type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                        new OA\Property(property: 'background_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$'),
                        new OA\Property(property: 'text_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$'),
                        new OA\Property(property: 'button_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$'),
                        new OA\Property(property: 'order_index', type: 'integer', minimum: 0),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                    ],
                    type: 'object'
                )
            )
        ),
        tags: ['Onboarding'],
        responses: [
            new OA\Response(response: 201, description: 'Onboarding screen created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreOnboardingScreenRequest $request): JsonResponse
    {
        $data = $request->validated();

        // ذخیره فایل روی public
        $path = $request->file('image')->store('onboarding', 'public');
        $data['image_path'] = $path;
        unset($data['image']);

        $screen = OnboardingScreen::create($data);

        return $this->sendResponse(
            new OnboardingScreenResource($screen),
            'admin.onboarding.created_success',
            Response::HTTP_CREATED
        );
    }

    #[OA\Get(
        path: '/v1/admin/onboarding-screens/{id}',
        operationId: 'adminShowOnboardingScreen',
        summary: 'Get specific onboarding screen (Admin)',
        security: [['sanctum' => []]],
        tags: ['Onboarding'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Onboarding screen details'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(OnboardingScreen $onboardingScreen): JsonResponse
    {
        return $this->sendResponse(
            new OnboardingScreenResource($onboardingScreen),
            'admin.onboarding.show_success'
        );
    }

    #[OA\Put(
        path: '/v1/admin/onboarding-screens/{id}',
        operationId: 'adminUpdateOnboardingScreen',
        summary: 'Update onboarding screen (Admin)',
        security: [['sanctum' => []]],
        tags: ['Onboarding'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: [],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', maxLength: 255),
                        new OA\Property(property: 'subtitle', type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'), // اختیاری
                        new OA\Property(property: 'background_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$'),
                        new OA\Property(property: 'text_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$'),
                        new OA\Property(property: 'button_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$'),
                        new OA\Property(property: 'order_index', type: 'integer', minimum: 0),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateOnboardingScreenRequest $request, OnboardingScreen $onboardingScreen): JsonResponse
    {
        $data = $request->validated();

        // اگر فایل جدید آمد: فایل قبلی را حذف و جدید را ذخیره کن
        if ($request->hasFile('image')) {
            if (!empty($onboardingScreen->image_path) && !Str::startsWith($onboardingScreen->image_path, ['http://','https://'])) {
                Storage::disk('public')->delete($onboardingScreen->image_path);
            }
            $path = $request->file('image')->store('onboarding', 'public');
            $data['image_path'] = $path;
            unset($data['image']);
        }

        $onboardingScreen->update($data);

        return $this->sendResponse(
            new OnboardingScreenResource($onboardingScreen->fresh()),
            'admin.onboarding.updated_success'
        );
    }

    #[OA\Delete(
        path: '/v1/admin/onboarding-screens/{id}',
        operationId: 'adminDeleteOnboardingScreen',
        summary: 'Delete onboarding screen (Admin)',
        security: [['sanctum' => []]],
        tags: ['Onboarding'],
        responses: [
            new OA\Response(response: 200, description: 'Deleted successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(OnboardingScreen $onboardingScreen): JsonResponse
    {
        // حذف فایل فیزیکی
        if (!empty($onboardingScreen->image_path) && !Str::startsWith($onboardingScreen->image_path, ['http://','https://'])) {
            Storage::disk('public')->delete($onboardingScreen->image_path);
        }

        $onboardingScreen->delete();

        return $this->sendResponse(
            ['message' => 'Onboarding screen deleted successfully.'],
            'admin.onboarding.deleted_success'
        );
    }

    #[OA\Post(
        path: '/v1/admin/onboarding-screens/{id}/toggle-status',
        operationId: 'adminToggleOnboardingScreenStatus',
        summary: 'Toggle onboarding screen active status (Admin)',
        security: [['sanctum' => []]],
        tags: ['Onboarding'],
        responses: [new OA\Response(response: 200, description: 'Status toggled successfully')]
    )]
    public function toggleStatus(OnboardingScreen $onboardingScreen): JsonResponse
    {
        $onboardingScreen->update(['is_active' => !$onboardingScreen->is_active]);

        return $this->sendResponse(
            new OnboardingScreenResource($onboardingScreen->fresh()),
            'admin.onboarding.status_toggled'
        );
    }

    #[OA\Post(
        path: '/v1/admin/onboarding-screens/reorder',
        operationId: 'adminReorderOnboardingScreens',
        summary: 'Reorder onboarding screens (Admin)',
        security: [['sanctum' => []]],
        tags: ['Onboarding'],
        responses: [new OA\Response(response: 200, description: 'Screens reordered successfully')]
    )]
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'screens'               => 'required|array',
            'screens.*.id'          => 'required|exists:onboarding_screens,id',
            'screens.*.order_index' => 'required|integer|min:0',
        ]);

        foreach ($request->screens as $item) {
            OnboardingScreen::whereKey($item['id'])
                ->update(['order_index' => $item['order_index']]);
        }

        $screens = OnboardingScreen::whereIn('id', collect($request->screens)->pluck('id'))
            ->ordered()
            ->get();

        return $this->sendResponse(
            OnboardingScreenResource::collection($screens),
            'admin.onboarding.reordered_success'
        );
    }
}
