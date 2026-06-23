<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces account/company status on every request:
 *  - HARD block when the user or company is explicitly deactivated (manual ban).
 *  - SOFT block when the company's subscription has lapsed (suspended): the
 *    company admin may still reach the renewal page to pay & reactivate, while
 *    everyone else — and all API clients — is blocked.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // HARD block — manual deactivation (null flag is treated as active).
        if ($user->is_active === false || ($user->company && $user->company->is_active === false)) {
            return $this->block($request, 'تم إيقاف هذا الحساب. يرجى التواصل مع إدارة المنصة.');
        }

        // SOFT block — lapsed subscription.
        $company = $user->company;
        if ($company && $company->subscription_status === Company::SUB_SUSPENDED) {
            if ($request->expectsJson()) {
                abort(403, 'انتهى اشتراك منشأتك. يرجى تجديده للمتابعة.');
            }

            if ($user->can('admin-access')) {
                if ($request->routeIs('company.subscription', 'company.subscription.*', 'logout')) {
                    return $next($request);
                }

                return redirect()->route('company.subscription')
                    ->with('warning', 'انتهى اشتراك منشأتك — يرجى التجديد للمتابعة.');
            }

            return $this->block($request, 'انتهى اشتراك منشأتك. يرجى التواصل مع مدير الحساب للتجديد.');
        }

        return $next($request);
    }

    private function block(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            abort(403, $message);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
