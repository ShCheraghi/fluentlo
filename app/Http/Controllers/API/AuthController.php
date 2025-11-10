<?php

namespace App\Http\Controllers\API;

use App\Jobs\SendWelcomeEmail;
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

        SendWelcomeEmail::dispatch($user);

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
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'shahabcheraghi@live.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'iShahab1234'),
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
                'message'  => $e->getMessage(),
            ]);

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
        if (!in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            return $this->sendError('auth.unsupported_provider', ['provider' => $provider], 422);
        }

        if (request('error')) {
            return $this->sendError('auth.social_denied', ['provider' => $provider], 422);
        }

        if (!request()->has('code')) {
            return $this->sendError('auth.missing_code', [], 422);
        }

        try {
            $driver = Socialite::driver($provider)->stateless();

            if ($provider === 'google') {
                $driver->scopes(['openid', 'profile', 'email']);
            }

            $socialUser = $driver->user();

            if (!$socialUser || !$socialUser->email) {
                return $this->sendError('auth.invalid_social_user', [], 422);
            }

            $result = $this->socialAuthService->handleSocialUser($provider, $socialUser);

            return $this->sendResponse($result, 'auth.social_success');

        } catch (ClientException $e) {
            Log::error('Social auth client error', [
                'provider' => $provider,
                'message'  => $e->getMessage(),
            ]);
            return $this->sendError('auth.social_client_error', [], 422);

        } catch (ConnectException $e) {
            Log::error('Social auth connection error', [
                'provider' => $provider,
                'message'  => $e->getMessage(),
            ]);
            return $this->sendError('auth.social_connection_error', [], 502);

        } catch (\Throwable $e) {
            Log::error('Social auth failed', [
                'provider' => $provider,
                'message'  => $e->getMessage(),
            ]);
            return $this->sendError('auth.social_failed', [], 500);
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
