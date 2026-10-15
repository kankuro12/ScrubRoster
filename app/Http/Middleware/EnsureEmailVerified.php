<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft-then-hard email verification gate.
 *
 * A newly registered tenant admin may use the workspace immediately, but if
 * the email is still unverified 30 days after registration the dashboard is
 * locked until they verify. Staff (non-admin) accounts are never blocked.
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail
            && ! $user->hasVerifiedEmail()
            && (int) $user->role === User::ROLE_ADMIN
            && $user->created_at
            && $user->created_at->lte(now()->subDays(30))
        ) {
            return redirect()
                ->route('verification.notice')
                ->with('status', 'Your 30-day grace period has ended. Please verify your email to keep using the workspace.');
        }

        return $next($request);
    }
}
