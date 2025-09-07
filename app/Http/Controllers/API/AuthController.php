<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Services\SocialAuthService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'auth', description: 'Authentication & Social login')]
class AuthController extends BaseController
{
    private const SUPPORTED_PROVIDERS = ['google', 'facebook'];

    public function __construct(
        private readonly SocialAuthService $socialAuthService
    ) {}

    #[OA\Post(
        path: '/v1/app/auth/register',
        operationId: 'appAuthRegister',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'shahab'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Passw0rd!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'Passw0rd!'),
                ],
                type: 'object'
            )
        ),
        tags: ['auth'],
        responses: [
            new OA\Response(response: 201, description: 'User registered successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [], [
            'name'     => __('validation.attributes.name'),
            'email'    => __('validation.attributes.email'),
            'password' => __('validation.attributes.password'),
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $data = $validator->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        event(new Registered($user));

        $token = $user->createToken('MyApp', ['*'], now()->addDays(30));

        $payload = [
            'user'       => $this->serializeUser($user),
            'token'      => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toISOString(),
        ];

        return $this->sendResponse($payload, 'auth.register_success', Response::HTTP_CREATED);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id'               => $user->id,
            'name'             => $user->name,
            'email'            => $user->email,
            'avatar'           => $user->avatar,
            'locale'           => $user->locale ?? app()->getLocale(),
            'social_providers' => $this->socialAuthService->getUserSocialProviders($user),
        ];
    }

    #[OA\Post(
        path: '/v1/app/auth/login',
        operationId: 'appAuthLogin',
        summary: 'Login user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Passw0rd!'),
                ],
                type: 'object'
            )
        ),
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'User logged in successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate(
            [
                'email'    => 'required|email',
                'password' => 'required|string',
            ],
            [],
            [
                'email'    => __('validation.attributes.email'),
                'password' => __('validation.attributes.password'),
            ]
        );

        if (!Auth::attempt($credentials)) {
            return $this->sendError('auth.invalid_credentials', [], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $user  = $request->user();
        $token = $user->createToken('MyApp', ['*'], now()->addDays(30));

        $payload = [
            'user'       => $this->serializeUser($user),
            'token'      => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toISOString(),
        ];

        return $this->sendResponse($payload, 'auth.login_success');
    }

    #[OA\Get(
        path: '/v1/app/auth/{provider}/redirect',
        operationId: 'appAuthSocialRedirect',
        summary: 'Redirect to social provider',
        tags: ['auth'],
        parameters: [
            new OA\Parameter(
                name: 'provider', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', enum: self::SUPPORTED_PROVIDERS),
                example: 'google'
            ),
            new OA\Parameter(
                name: 'test', description: 'Return JSON instead of 302 (diagnostic)',
                in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), example: true
            ),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirect to provider'),
            new OA\Response(response: 422, description: 'Unsupported provider'),
        ]
    )]
    public function socialRedirect(string $provider): RedirectResponse|JsonResponse
    {
        if (!in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            return $this->sendError('auth.unsupported_provider', ['provider' => $provider], 422);
        }

        try {
            $driver = Socialite::driver($provider)->stateless();

            if ($provider === 'google') {
                $driver->scopes(['openid', 'profile', 'email']);
            }

            $redirect = $driver->redirect();

            if (request()->boolean('test')) {
                return $this->sendResponse(
                    ['url' => $redirect->getTargetUrl()],
                    'auth.social_redirect_ok'
                );
            }

            return $redirect;

        } catch (\Throwable $e) {
            Log::error('Social redirect failed', [
                'provider' => $provider,
                'msg'      => $e->getMessage(),
            ]);
            report($e);

            return $this->sendError('auth.social_failed', ['provider' => $provider], 422);
        }
    }

    #[OA\Get(
        path: '/v1/app/auth/{provider}/callback',
        operationId: 'appAuthSocialCallback',
        summary: 'Handle social provider callback',
        tags: ['auth'],
        parameters: [
            new OA\Parameter(
                name: 'provider', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', enum: self::SUPPORTED_PROVIDERS), example: 'google'
            ),
            new OA\Parameter(
                name: 'code', description: 'Authorization code from provider',
                in: 'query', required: false, schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'state', description: 'Opaque state (ignored in stateless)',
                in: 'query', required: false, schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'error', description: 'Provider error (if user denied)',
                in: 'query', required: false, schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Social authentication successful'),
            new OA\Response(response: 422, description: 'Social authentication failed'),
        ]
    )]
    public function socialCallback(string $provider): JsonResponse
    {
        // Debug step 1: بررسی اولیه
        try {
            if (!in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported provider',
                    'debug' => 'Step 1: Provider validation failed',
                    'provider' => $provider
                ], 422);
            }

            // Debug step 2: بررسی error از provider
            if ($err = request('error')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider returned error',
                    'debug' => 'Step 2: Provider error',
                    'error' => $err,
                    'error_description' => request('error_description'),
                    'provider' => $provider
                ], 422);
            }

            // Debug step 3: بررسی code
            if (!request()->has('code')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing authorization code',
                    'debug' => 'Step 3: Code validation failed',
                    'query_params' => request()->all()
                ], 422);
            }

            // Debug step 4: تست Socialite driver
            try {
                $driver = Socialite::driver($provider)->stateless();
                if ($provider === 'google') {
                    $driver->scopes(['openid', 'profile', 'email']);
                }
            }
            catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Socialite driver failed',
                    'debug' => 'Step 4: Driver creation failed',
                    'error' => $e->getMessage(),
                    'provider' => $provider
                ], 500);
            }

            // Debug step 5: تست user retrieval
            try {
                $socialUser = $driver->user();
            }
            catch (ClientException $e) {
                $body = (string) optional($e->getResponse())->getBody();
                return response()->json([
                    'success' => false,
                    'message' => 'OAuth client error',
                    'debug' => 'Step 5: User retrieval failed - Client Error',
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                    'response_body' => substr($body, 0, 1000) // محدود کردن طول
                ], 422);
            }
            catch (ConnectException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'OAuth connection error',
                    'debug' => 'Step 5: User retrieval failed - Connection Error',
                    'provider' => $provider,
                    'error' => $e->getMessage()
                ], 502);
            }
            catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'OAuth unknown error',
                    'debug' => 'Step 5: User retrieval failed - Unknown Error',
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                    'class' => get_class($e)
                ], 500);
            }

            // Debug step 6: بررسی social user data
            if (!$socialUser || !$socialUser->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid social user data',
                    'debug' => 'Step 6: Social user validation failed',
                    'social_user' => [
                        'id' => $socialUser->id ?? null,
                        'email' => $socialUser->email ?? null,
                        'name' => $socialUser->name ?? null,
                        'has_user' => !is_null($socialUser)
                    ]
                ], 422);
            }

            // Debug step 7: تست social auth service
            try {
                $result = $this->socialAuthService->handleSocialUser($provider, $socialUser);

                return response()->json([
                    'success' => true,
                    'message' => 'Social authentication successful',
                    'debug' => 'Step 7: All steps completed successfully',
                    'data' => $result
                ]);

            }
            catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Social auth service failed',
                    'debug' => 'Step 7: SocialAuthService failed',
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                    'class' => get_class($e),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ], 500);
            }

        }
        catch (\Exception $e) {
            // کلی fallback برای هر ارور غیرمنتظره
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error in social callback',
                'debug' => 'Unexpected Exception',
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => array_slice($e->getTrace(), 0, 5) // فقط 5 تا اول
            ], 500);
        }
    }

    #[OA\Post(
        path: '/v1/app/auth/validate-token',
        operationId: 'appAuthValidateToken',
        summary: 'Validate Sanctum token',
        security: [['sanctum' => []]],
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Token is valid'),
            new OA\Response(response: 401, description: 'Token is invalid'),
        ]
    )]
    public function validateToken(Request $request): JsonResponse
    {
        /** @var User $user */
        $user  = $request->user();
        $token = $user->currentAccessToken();

        $payload = [
            'user'       => $this->serializeUser($user),
            'expires_at' => $token->expires_at?->toISOString(),
        ];

        return $this->sendResponse($payload, 'auth.token_valid');
    }

    #[OA\Post(
        path: '/v1/app/auth/logout',
        operationId: 'appAuthLogout',
        summary: 'Logout user and revoke token',
        security: [['sanctum' => []]],
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logout successful'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->sendResponse(null, 'auth.logout_success');
    }

    #[OA\Post(
        path: '/v1/app/auth/logout-all',
        operationId: 'appAuthLogoutAll',
        summary: 'Logout from all devices',
        security: [['sanctum' => []]],
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logout from all devices successful'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return $this->sendResponse(null, 'auth.logout_all_success');
    }
}
