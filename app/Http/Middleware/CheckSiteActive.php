<?php

namespace App\Http\Middleware;

use App\Settings\KaidoSetting;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckSiteActive
{
    public function handle($request, Closure $next)
    {
        $settings = app(KaidoSetting::class);

        // Allow full access if site is active
        if ($settings->site_active) {
            return $next($request);
        }

        // Allow login, logout, reset password pages
        if (
            $request->is('login') || $request->is('logout') ||
            $request->is('password/*') || $request->is('admin/login')
        ) {
            return $next($request);
        }

        // Allow super admin
        $user = Auth::user();
        if ($user && $user->hasRole('super_admin')) {
            return $next($request);
        }

        // Otherwise, show maintenance page
        // return response()->view('errors.503', [], 503);
        return abort(503);
    }
}
