<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeVerifyMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);

        abort_unless(hash_equals($hash, sha1($user->email)), 403, 'Invalid verification link.');

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        if (Auth::check() && Auth::id() === $user->id) {
            return redirect()->route('dashboard')->with('status', 'Email verified — welcome to ScrubRoster!');
        }

        return redirect()->route('login')->with('status', 'Email verified. Please sign in to continue.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            Mail::to($user->email)->send(new WelcomeVerifyMail($user, $user->tenant));
        } catch (\Throwable $e) {
            Log::error("Failed resending verification email to {$user->email}: ".$e->getMessage());

            return back()->withErrors(['email' => 'Could not send the verification email. Please try again later.']);
        }

        return back()->with('status', 'A fresh verification link has been sent to your email address.');
    }
}
