<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureNotRateLimited($request);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($this->throttleKey($request));

            $user = Auth::user();

            // Block suspended users or users of a deactivated company.
            if ($user->is_active === false || ($user->company && $user->company->is_active === false)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'هذا الحساب موقوف حالياً. يرجى التواصل مع إدارة المنصة.']);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Count this failed attempt toward the throttle.
        RateLimiter::hit($this->throttleKey($request));

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'بيانات الدخول غير صحيحة']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Block after 5 failed attempts per email+IP for 60 seconds. */
    protected function ensureNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "محاولات كثيرة. حاول مرة أخرى بعد {$seconds} ثانية.",
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }
}
