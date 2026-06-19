<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out and blocks any authenticated user whose own account, or whose
 * company, has been deactivated — so suspension takes effect on the very
 * next request, not just at the login screen.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Block only when explicitly deactivated; an unset/null flag is treated
        // as active (the column defaults to true).
        if ($user && ($user->is_active === false || ($user->company && $user->company->is_active === false))) {
            // API (token) clients: deny with JSON, no session to clear.
            if ($request->expectsJson()) {
                abort(403, 'تم إيقاف هذا الحساب. يرجى التواصل مع إدارة المنصة.');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'تم إيقاف هذا الحساب. يرجى التواصل مع إدارة المنصة.']);
        }

        return $next($request);
    }
}
