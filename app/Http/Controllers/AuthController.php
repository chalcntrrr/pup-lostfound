<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    /** Staff login: email + password. Only Admin and Super Admin accounts can log in. */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Please enter username and password.',
            'password.required' => 'Please enter username and password.',
        ]);

        $email = strtolower(trim($data['login']));

        if (! Auth::attempt(['email' => $email, 'password' => $data['password']], $request->boolean('remember'))) {
            // Only worth logging when someone fails to get into an ADMIN account.
            $target = User::where('email', $email)->first();
            if ($target?->isAdmin()) {
                ActivityLog::record('Failed login', $data['login'], $target);
            }

            throw ValidationException::withMessages([
                'login' => 'Invalid username or password.',
            ]);
        }

        // The system is for the Lost and Found office only. Old student / faculty accounts can't get in.
        if (! Auth::user()->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'This system is for Lost and Found office staff only (Admin and Super Admin).',
            ]);
        }

        $request->session()->regenerate();
        ActivityLog::record('Logged in', Auth::user()->roleLabel());

        return redirect()->intended($this->homeFor(Auth::user()))
            ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
    }

    public function logout(Request $request)
    {
        ActivityLog::record('Logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    /** Where each role lands after logging in. */
    private function homeFor(User $user): string
    {
        return route('items.index'); // ADMIN DASHBOARD
    }
}
