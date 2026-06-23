<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Token-based authentication for the official mobile client (Sanctum).
 *
 * Mirrors the web login rules: same credentials, same active-account gating,
 * same tenant (company) context — but returns a personal access token + the
 * user profile (company, roles, abilities) as JSON instead of a session.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة'],
            ]);
        }

        // Same gating as the web login: block suspended users / deactivated companies.
        if ($user->is_active === false || ($user->company && $user->company->is_active === false)) {
            throw ValidationException::withMessages([
                'email' => ['هذا الحساب موقوف حالياً. يرجى التواصل مع إدارة المنصة.'],
            ]);
        }

        // A lapsed subscription blocks the mobile app; the admin renews on the web.
        if ($user->company && $user->company->subscription_status === \App\Models\Company::SUB_SUSPENDED) {
            throw ValidationException::withMessages([
                'email' => ['انتهى اشتراك منشأتك. يرجى التواصل مع مدير الحساب للتجديد.'],
            ]);
        }

        $device = $data['device_name'] ?? 'mobile';
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user->load(['company', 'roles'])),
        ]);
    }

    /** Returns the authenticated user's full profile (used on app start to restore session). */
    public function me(Request $request)
    {
        return response()->json([
            'user' => new UserResource($request->user()->load(['company', 'roles'])),
        ]);
    }

    /** Revoke only the token used for this request (logs out this device). */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }
}
