<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const OTP_TTL_MINUTES = 10;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::query()->where('email', $request->string('email'))->first();
        if (! $user || ! Hash::check($request->string('password'), $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load(['role.permissions', 'instructor']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = $request->user();
        if (! Hash::check($request->string('current_password'), $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }
        $user->update([
            'password_hash' => Hash::make($request->string('password')),
            'password_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Password changed']);
    }

    public function requestRegisterOtp(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'department_id' => 'nullable|uuid|exists:departments,id',
        ]);

        $this->issueOtp(
            strtolower(trim((string) $payload['email'])),
            'register',
            [
                'full_name' => (string) $payload['full_name'],
                'email' => strtolower(trim((string) $payload['email'])),
                'password_hash' => Hash::make((string) $payload['password']),
                'department_id' => $payload['department_id'] ?? null,
            ],
        );

        return response()->json(['message' => 'OTP sent to email']);
    }

    public function verifyRegisterOtp(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => 'required|email|max:190',
            'otp' => 'required|string|size:6',
        ]);

        $normalizedEmail = strtolower(trim((string) $payload['email']));
        $otpRow = $this->consumeOtpOrFail($normalizedEmail, 'register', (string) $payload['otp']);
        $registration = json_decode((string) $otpRow->payload_json, true);
        if (! is_array($registration)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP payload.'],
            ]);
        }

        $role = Role::query()->where('code', 'student')->firstOrFail();

        $user = DB::transaction(function () use ($registration, $normalizedEmail, $role) {
            if (User::query()->where('email', $normalizedEmail)->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['This email is already registered.'],
                ]);
            }

            $user = User::query()->create([
                'role_id' => $role->id,
                'department_id' => $registration['department_id'] ?? null,
                'full_name' => $registration['full_name'] ?? '',
                'email' => $normalizedEmail,
                'password_hash' => $registration['password_hash'] ?? '',
                'is_active' => true,
                'password_updated_at' => now(),
            ]);

            UserSetting::query()->create(['user_id' => $user->id]);
            Student::query()->create([
                'user_id' => $user->id,
                'university_number' => $this->generateUniversityNumber(),
                'completed_hours' => 0,
                'year_level' => 1,
                'study_status' => 'regular',
            ]);

            return $user;
        });

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Registration completed',
            'token' => $token,
            'user' => $user->load(['role.permissions', 'instructor']),
        ]);
    }

    public function requestForgotPasswordOtp(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => 'required|email|max:190',
        ]);

        $normalizedEmail = strtolower(trim((string) $payload['email']));
        $user = User::query()->where('email', $normalizedEmail)->first();
        if (! $user) {
            return response()->json(['message' => 'If the email exists, an OTP was sent']);
        }

        $this->issueOtp($normalizedEmail, 'forgot_password', [
            'user_id' => (string) $user->id,
        ]);

        return response()->json(['message' => 'If the email exists, an OTP was sent']);
    }

    public function resetPasswordWithOtp(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => 'required|email|max:190',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $normalizedEmail = strtolower(trim((string) $payload['email']));
        $this->consumeOtpOrFail($normalizedEmail, 'forgot_password', (string) $payload['otp']);

        $user = User::query()->where('email', $normalizedEmail)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        $user->update([
            'password_hash' => Hash::make((string) $payload['password']),
            'password_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Password reset completed']);
    }

    private function issueOtp(string $email, string $purpose, array $payload): void
    {
        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        DB::table('auth_otps')
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->delete();

        DB::table('auth_otps')->insert([
            'id' => (string) Str::uuid(),
            'email' => $email,
            'purpose' => $purpose,
            'otp_hash' => Hash::make($otp),
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'attempts' => 0,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::raw(
            "Your OptiTime OTP code is: {$otp}\nThis code expires in ".self::OTP_TTL_MINUTES.' minutes.',
            static function ($message) use ($email, $purpose): void {
                $message->to($email)->subject('OptiTime OTP - '.str_replace('_', ' ', $purpose));
            }
        );
    }

    private function consumeOtpOrFail(string $email, string $purpose, string $otp): object
    {
        $otpRow = DB::table('auth_otps')
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->orderByDesc('created_at')
            ->first();

        if (! $otpRow || now()->greaterThan($otpRow->expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP expired or not found.'],
            ]);
        }

        if ((int) $otpRow->attempts >= 5) {
            DB::table('auth_otps')->where('id', $otpRow->id)->delete();
            throw ValidationException::withMessages([
                'otp' => ['OTP attempts exceeded.'],
            ]);
        }

        if (! Hash::check($otp, (string) $otpRow->otp_hash)) {
            DB::table('auth_otps')
                ->where('id', $otpRow->id)
                ->update([
                    'attempts' => (int) $otpRow->attempts + 1,
                    'updated_at' => now(),
                ]);

            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        DB::table('auth_otps')->where('id', $otpRow->id)->delete();

        return $otpRow;
    }

    private function generateUniversityNumber(): string
    {
        do {
            $value = 'STU-'.random_int(100000, 999999);
        } while (Student::query()->where('university_number', $value)->exists());

        return $value;
    }
}
