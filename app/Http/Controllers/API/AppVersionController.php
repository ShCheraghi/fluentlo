<?php

namespace App\Http\Controllers\API;

use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: AppVersionController::TAG,
    description: 'App version management (public + admin)'
)]
class AppVersionController extends BaseController
{
    private const TAG = 'App Version';

    // ===================== Public =====================

    #[OA\Post(
        path: '/v1/app/version/check',
        operationId: 'app_check_version',
        summary: 'Check for app updates (Public)',
        tags: [self::TAG],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['platform', 'version', 'build_number'],
                properties: [
                    new OA\Property(property: 'platform', type: 'string', enum: ['ios','android'], example: 'ios'),
                    new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                    new OA\Property(property: 'build_number', type: 'integer', example: 1),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Version check result'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function checkVersion(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'platform'      => ['required','string','in:ios,android'],
            'version'       => ['required','string','max:50'],
            'build_number'  => ['required','integer','min:1'],
        ]);

        if ($v->fails()) {
            return $this->sendValidationError($v->errors()->toArray());
        }

        $platform         = (string) $request->input('platform');
        $clientVersion    = ltrim((string) $request->input('version'), 'vV'); // normalize "v1.2.3"
        $clientBuild      = (int) $request->input('build_number');

        // آخرین نسخهٔ فعال برای پلتفرم (بر اساس build_number)
        $latest = AppVersion::query()
            ->where('platform', $platform)
            ->where('is_active', true)
            ->orderByDesc('build_number')
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return $this->sendResponse([
                'force_update'        => false,
                'latest_version'      => $clientVersion,
                'latest_build_number' => $clientBuild,
                'title'               => null,
                'description'         => null,
                'store_link'          => null,
            ], 'app_version.no_update_policy');
        }

        // آخرین «نسخهٔ اجباری» فعال؛ اگر کاربر پایین‌تر از این بیلد باشد → force
        $forced = AppVersion::query()
            ->where('platform', $platform)
            ->where('is_active', true)
            ->where('force_update', true)
            ->orderByDesc('build_number')
            ->orderByDesc('id')
            ->first();

        $forceUpdate = $forced ? ($clientBuild < (int) $forced->build_number) : false;

        // اگر کاربر از آخرین بیلد عقب نیست، آپ‌تو‌دیت محسوب می‌شود
        if ($clientBuild >= (int) $latest->build_number) {
            return $this->sendResponse([
                'force_update'        => false,
                'latest_version'      => $latest->version,
                'latest_build_number' => (int) $latest->build_number,
                'title'               => null,
                'description'         => null,
                'store_link'          => method_exists($latest, 'getStoreLink') ? $latest->getStoreLink() : $this->firstStoreLink($latest),
            ], 'app_version.up_to_date');
        }

        return $this->sendResponse([
            'force_update'        => $forceUpdate,
            'latest_version'      => $latest->version,
            'latest_build_number' => (int) $latest->build_number,
            'title'               => $latest->title,
            'description'         => $latest->description, // می‌تواند HTML باشد
            'store_link'          => method_exists($latest, 'getStoreLink') ? $latest->getStoreLink() : $this->firstStoreLink($latest),
        ], $forceUpdate ? 'app_version.force_update_required' : 'app_version.update_available');
    }

    #[OA\Get(
        path: '/v1/app/version/latest',
        operationId: 'app_latest_version',
        summary: 'Get latest version info (Public)',
        tags: [self::TAG],
        parameters: [
            new OA\Parameter(
                name: 'platform',
                description: 'Platform (ios/android)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['ios','android'])
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Latest version info'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function getLatestVersion(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'platform' => ['required','string','in:ios,android'],
        ]);

        if ($v->fails()) {
            return $this->sendValidationError($v->errors()->toArray());
        }

        $platform = (string) $request->input('platform');

        $latest = AppVersion::query()
            ->where('platform', $platform)
            ->where('is_active', true)
            ->orderByDesc('build_number')
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return $this->sendError('app_version.not_found', [], 404);
        }

        return $this->sendResponse([
            'platform'            => $latest->platform,
            'version'             => $latest->version,
            'build_number'        => (int) $latest->build_number,
            'force_update'        => (bool) $latest->force_update,
            'title'               => $latest->title,
            'description'         => $latest->description,
            'store_link'          => method_exists($latest, 'getStoreLink') ? $latest->getStoreLink() : $this->firstStoreLink($latest),
        ], 'app_version.latest_retrieved');
    }

    // ===================== Admin (secured via Sanctum at route level) =====================

    #[OA\Get(
        path: '/v1/admin/app-versions',
        operationId: 'admin_app_versions_index',
        summary: 'List app versions (Admin)',
        security: [['sanctum' => []]],
        tags: [self::TAG],
        parameters: [
            new OA\Parameter(name: 'platform', in: 'query', schema: new OA\Schema(type: 'string', enum: ['ios','android'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'App versions list'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function adminIndex(Request $request): JsonResponse
    {
        $query = AppVersion::query()->orderByDesc('id');

        if ($request->filled('platform')) {
            $query->where('platform', (string) $request->input('platform'));
        }

        $perPage  = max(1, min((int) $request->input('per_page', 15), 100));
        $versions = $query->paginate($perPage);

        return $this->sendResponse($versions, 'app_version.admin.list_retrieved');
    }

    #[OA\Post(
        path: '/v1/admin/app-versions',
        operationId: 'admin_app_versions_store',
        summary: 'Create app version (Admin)',
        security: [['sanctum' => []]],
        tags: [self::TAG],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['platform','version','build_number'],
                properties: [
                    new OA\Property(property: 'platform', type: 'string', enum: ['ios','android']),
                    new OA\Property(property: 'version', type: 'string', example: '1.0.1'),
                    new OA\Property(property: 'build_number', type: 'integer', example: 2),
                    new OA\Property(property: 'force_update', type: 'boolean', example: false),
                    new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Update Available'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: '<p>New features</p>'),
                    new OA\Property(
                        property: 'store_links',
                        type: 'object',
                        nullable: true,
                        properties: [
                            new OA\Property(property: 'app_store', type: 'string', nullable: true),
                            new OA\Property(property: 'google_play', type: 'string', nullable: true),
                            new OA\Property(property: 'direct', type: 'string', nullable: true),
                        ]
                    ),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'App version created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function adminStore(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'platform'      => ['required','string','in:ios,android'],
            'version'       => ['required','string','max:50'],
            'build_number'  => ['required','integer','min:1'],
            'force_update'  => ['nullable','boolean'],
            'title'         => ['nullable','string','max:255'],
            'description'   => ['nullable','string'],
            'store_links'   => ['nullable','array'],
            'store_links.app_store'  => ['nullable','url'],
            'store_links.google_play'=> ['nullable','url'],
            'store_links.direct'     => ['nullable','url'],
            'is_active'     => ['nullable','boolean'],
        ]);

        if ($v->fails()) {
            return $this->sendValidationError($v->errors()->toArray());
        }

        // جلوگیری از ایجاد رکورد تکراری با همان platform+version+build_number
        $exists = AppVersion::query()
            ->where('platform', (string) $request->input('platform'))
            ->where('version',  (string) $request->input('version'))
            ->where('build_number', (int) $request->input('build_number'))
            ->exists();

        if ($exists) {
            return $this->sendError('app_version.admin.already_exists', [], 422);
        }

        $payload = $this->coerceBooleans($request->all(), ['force_update','is_active']);
        $row = AppVersion::create($payload);

        return $this->sendResponse($row, 'app_version.admin.created', 201);
    }

    #[OA\Get(
        path: '/v1/admin/app-versions/{appVersion}',
        operationId: 'admin_app_versions_show',
        summary: 'Get app version details (Admin)',
        security: [['sanctum' => []]],
        tags: [self::TAG],
        parameters: [
            new OA\Parameter(name: 'appVersion', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'App version details'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function adminShow(AppVersion $appVersion): JsonResponse
    {
        return $this->sendResponse($appVersion, 'app_version.admin.retrieved');
    }

    #[OA\Put(
        path: '/v1/admin/app-versions/{appVersion}',
        operationId: 'admin_app_versions_update',
        summary: 'Update app version (Admin)',
        security: [['sanctum' => []]],
        tags: [self::TAG],
        parameters: [
            new OA\Parameter(name: 'appVersion', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'platform', type: 'string', enum: ['ios','android']),
                    new OA\Property(property: 'version', type: 'string', example: '1.0.1'),
                    new OA\Property(property: 'build_number', type: 'integer', example: 2),
                    new OA\Property(property: 'force_update', type: 'boolean', example: false),
                    new OA\Property(property: 'title', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'store_links',
                        type: 'object',
                        nullable: true,
                        properties: [
                            new OA\Property(property: 'app_store', type: 'string', nullable: true),
                            new OA\Property(property: 'google_play', type: 'string', nullable: true),
                            new OA\Property(property: 'direct', type: 'string', nullable: true),
                        ]
                    ),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'App version updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function adminUpdate(Request $request, AppVersion $appVersion): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'platform'      => ['nullable','string','in:ios,android'],
            'version'       => ['nullable','string','max:50'],
            'build_number'  => ['nullable','integer','min:1'],
            'force_update'  => ['nullable','boolean'],
            'title'         => ['nullable','string','max:255'],
            'description'   => ['nullable','string'],
            'store_links'   => ['nullable','array'],
            'store_links.app_store'  => ['nullable','url'],
            'store_links.google_play'=> ['nullable','url'],
            'store_links.direct'     => ['nullable','url'],
            'is_active'     => ['nullable','boolean'],
        ]);

        if ($v->fails()) {
            return $this->sendValidationError($v->errors()->toArray());
        }

        // جلوگیری از تکرار (به جز رکورد فعلی)
        $platform = $request->input('platform', $appVersion->platform);
        $version  = $request->input('version',  $appVersion->version);
        $build    = $request->input('build_number', $appVersion->build_number);

        $exists = AppVersion::query()
            ->where('platform', (string) $platform)
            ->where('version',  (string) $version)
            ->where('build_number', (int) $build)
            ->where('id', '!=', $appVersion->id)
            ->exists();

        if ($exists) {
            return $this->sendError('app_version.admin.already_exists', [], 422);
        }

        $payload = $this->coerceBooleans($request->all(), ['force_update','is_active']);
        $appVersion->update($payload);

        return $this->sendResponse($appVersion->fresh(), 'app_version.admin.updated');
    }

    #[OA\Delete(
        path: '/v1/admin/app-versions/{appVersion}',
        operationId: 'admin_app_versions_destroy',
        summary: 'Delete app version (Admin)',
        security: [['sanctum' => []]],
        tags: [self::TAG],
        parameters: [
            new OA\Parameter(name: 'appVersion', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'App version deleted'),
        ]
    )]
    public function adminDestroy(AppVersion $appVersion): JsonResponse
    {
        $appVersion->delete();
        return $this->sendResponse(null, 'app_version.admin.deleted');
    }

    #[OA\Post(
        path: '/v1/admin/app-versions/{appVersion}/toggle-status',
        operationId: 'admin_app_versions_toggle_status',
        summary: 'Toggle app version status (Admin)',
        security: [['sanctum' => []]],
        tags: [self::TAG],
        parameters: [
            new OA\Parameter(name: 'appVersion', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status toggled'),
        ]
    )]
    public function adminToggleStatus(AppVersion $appVersion): JsonResponse
    {
        $appVersion->update(['is_active' => !$appVersion->is_active]);

        return $this->sendResponse(
            $appVersion->fresh(),
            $appVersion->is_active ? 'app_version.admin.activated' : 'app_version.admin.deactivated'
        );
    }

    // ===================== Helpers =====================

    /**
     * تبدیل 'true'/'false'/'1'/'0' به boolean واقعی در payload
     */
    private function coerceBooleans(array $payload, array $keys): array
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $payload)) {
                $payload[$k] = filter_var($payload[$k], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            }
        }
        return $payload;
    }

    /**
     * اگر متد getStoreLink در مدل نباشد، از اولین لینک معتبر در store_links استفاده می‌کند.
     */
    private function firstStoreLink(AppVersion $row): ?string
    {
        if (is_array($row->store_links)) {
            foreach (['app_store','google_play','direct'] as $k) {
                $url = Arr::get($row->store_links, $k);
                if (is_string($url) && strlen($url)) {
                    return $url;
                }
            }
        }
        return null;
    }
}
