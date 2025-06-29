<?php

namespace App\Http\Middleware;

use App\Settings\KaidoSetting;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CheckSiteActive
{
    public function handle($request, Closure $next)
    {
        try {
            // Check if settings table exists first
            if (!Schema::hasTable('settings')) {
                // If settings table doesn't exist, allow access
                return $next($request);
            }

            // Debug: Log the current path
            Log::info('CheckSiteActive middleware - Current path: ' . $request->path());

            // Allow login, logout, reset password pages FIRST (before checking site status)
            if (
                $request->is('login') ||
                $request->is('logout') ||
                $request->is('password/*') ||
                $request->is('admin/login') ||
                $request->is('*/login') ||
                $request->is('auth/*') ||
                $request->is('filament/*/login') ||
                $request->is('admin/auth/*') ||
                $request->is('register') ||
                $request->is('forgot-password') ||
                $request->is('reset-password/*')
            ) {
                Log::info('CheckSiteActive middleware - Allowing access to auth route: ' . $request->path());
                return $next($request);
            }

            $settings = app(KaidoSetting::class);
            Log::info('CheckSiteActive middleware - Site active: ' . ($settings->site_active ?? 'null'));

            // Allow full access if site is active
            if ($settings->site_active ?? true) {
                return $next($request);
            }

            // Allow super admin
            $user = Auth::user();
            if ($user && $user->hasRole('super_admin')) {
                Log::info('CheckSiteActive middleware - Allowing super admin access');
                return $next($request);
            }

            // Otherwise, show maintenance page
            Log::info('CheckSiteActive middleware - Blocking access to: ' . $request->path());
            return response()->view('errors.503', [], 503);
        } catch (\Exception $e) {
            // If there's any error with settings, allow access by default
            Log::error('CheckSiteActive middleware error: ' . $e->getMessage());
            return $next($request);
        }
    }
}
