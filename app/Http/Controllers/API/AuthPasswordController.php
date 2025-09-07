<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(
    name: 'auth',
    description: 'Authentication endpoints (login/register/password)'
)]
class AuthPasswordController extends BaseController
{
    #[OA\Post(
        path: '/v1/app/auth/forgot-password',
        operationId: 'app_forgot_password',
        summary: 'Forgot password (send reset link)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'shahabcheraghi@live.com'),
                ],
                type: 'object'
            )
        ),
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Reset link sent (generic OK)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function forgotPassword(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc,dns'],
        ], [], [
            'email' => __('validation.attributes.email'),
        ]);

        if ($v->fails()) {
            return $this->sendValidationError($v->errors()->toArray());
        }

        $email = $request->string('email')->lower()->toString();

        // برای جلوگیری از user enumeration: پاسخ نهایی همیشه جنریک است.
        $user = User::where('email', $email)->first();

        if ($user) {
            // اگر کاربر فقط سوشال بوده و پسورد ندارد، لینک ایمیل نده
            if (method_exists($user, 'isSocialUser') && $user->isSocialUser() && !$user->password) {
                return $this->sendError('auth.social_user_no_password', [], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $status = Password::sendResetLink(['email' => $email]);
            if ($status !== Password::RESET_LINK_SENT) {
                // می‌تونی همچنان 200 برگردونی تا جنریک بمونه؛ اینجا 422 برمی‌گردونیم تا کلاینت باخبر شود
                return $this->sendError('auth.reset_link_failed', ['email' => [__($status)]], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        return $this->sendResponse(
            ['message' => 'If your email is registered, you will receive a password reset link.'],
            'auth.reset_link_sent'
        );
    }

    #[OA\Post(
        path: '/v1/app/auth/reset-password',
        operationId: 'app_reset_password',
        summary: 'Reset password (with token)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'token', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'token', type: 'string', example: 'reset_token_here'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewPassword123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewPassword123!'),
                ],
                type: 'object'
            )
        ),
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Password reset successful'),
            new OA\Response(response: 422, description: 'Invalid token or validation error'),
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc,dns'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [], [
            'token' => __('validation.attributes.token'),
            'email' => __('validation.attributes.email'),
            'password' => __('validation.attributes.password'),
        ]);

        if ($v->fails()) {
            return $this->sendValidationError($v->errors()->toArray());
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // ابطال همه توکن‌ها (لاگ‌اوت همه دستگاه‌ها)
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->sendError('auth.reset_failed', ['error' => __($status)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->sendResponse(['message' => 'Password has been reset successfully.'], 'auth.password_reset_success');
    }

    #[OA\Post(
        path: '/v1/app/auth/change-password',
        operationId: 'app_change_password',
        summary: 'Change password (authenticated user)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'CurrentPassword123'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewPassword123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewPassword123!'),
                ],
                type: 'object'
            )
        ),
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Password changed'),
            new OA\Response(response: 401, description: 'Unauthorized / current password incorrect'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [], [
            'current_password' => __('validation.attributes.current_password'),
            'password' => __('validation.attributes.password'),
        ]);

        if ($v->fails()) {
            return $this->sendValidationError($v->errors()->toArray());
        }

        /** @var User $user */
        $user = $request->user();

        // اگر کاربر Social است و پسورد ندارد: اولین بار پسورد تنظیم می‌کند
        if (method_exists($user, 'isSocialUser') && $user->isSocialUser() && !$user->password) {
            $user->update([
                'password' => Hash::make($request->string('password')),
                'remember_token' => Str::random(60),
            ]);
            // سایر توکن‌ها را ابطال می‌کنیم
            if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $current = $user->currentAccessToken()->id;
                $user->tokens()->where('id', '!=', $current)->delete();
            } else {
                $user->tokens()->delete();
            }
            return $this->sendResponse(['message' => 'Password set successfully.'], 'auth.password_set_success');
        }

        // بررسی پسورد فعلی
        if (!Hash::check($request->string('current_password'), $user->password)) {
            return $this->sendError('auth.current_password_incorrect', [], Response::HTTP_UNAUTHORIZED);
        }

        // تغییر پسورد
        $user->update([
            'password' => Hash::make($request->string('password')),
            'remember_token' => Str::random(60),
        ]);

        // همه توکن‌های دیگر را ابطال کن (به جز توکن فعلی)
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $currentId = $user->currentAccessToken()->id;
            $user->tokens()->where('id', '!=', $currentId)->delete();
        } else {
            $user->tokens()->delete();
        }

        return $this->sendResponse(['message' => 'Password changed successfully.'], 'auth.password_change_success');
    }
}
