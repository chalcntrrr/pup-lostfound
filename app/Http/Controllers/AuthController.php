<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    /** Log in with email OR student number + password. */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Please enter username and password.',
            'password.required' => 'Please enter username and password.',
        ]);

        // Log in with email, student number or (faculty) employee number.
        $email = $data['login'];
        if (! filter_var($data['login'], FILTER_VALIDATE_EMAIL)) {
            $email = User::where('student_number', $data['login'])
                ->orWhere('employee_number', $data['login'])
                ->value('email') ?? $data['login'];
        }

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

        $request->session()->regenerate();
        ActivityLog::record('Logged in', Auth::user()->roleLabel());

        return redirect()->intended($this->homeFor(Auth::user()))
            ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Student / faculty sign-up. Admin accounts are NOT created here: an existing admin adds them
     * from Account > "+ Add Another Admin", so nobody can make themselves an admin.
     */
    public function register(Request $request)
    {
        $isFaculty = $request->input('account_type') === 'faculty';

        $data = $request->validate([
            'account_type' => ['required', Rule::in(['student', 'faculty'])],
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s.,\'-]+$/u'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'student_number' => $isFaculty ? ['nullable'] : ['required', 'regex:/^\d{4}-\d{5}-[A-Z]{2}-\d$/', 'unique:users,student_number'],
            'employee_number' => $isFaculty ? ['nullable', 'string', 'max:30', 'unique:users,employee_number'] : ['nullable'],
            'course_section' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'regex:/^09\d{2}-?\d{3}-?\d{4}$/'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'Passwords do not match.',
            'name.regex' => 'Name can only contain letters, spaces, commas, periods and hyphens.',
            'student_number.regex' => 'Student ID must follow this format: YYYY-00000-SR-0',
            'student_number.unique' => 'This Student ID already has an account.',
            'employee_number.unique' => 'This Employee ID already has an account.',
            'account_type.required' => 'Choose whether you are a student or a faculty member.',
            'contact_number.regex' => 'Contact number must follow this format: 09XX-XXX-XXXX',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'student_number' => $isFaculty ? null : $data['student_number'],
            'employee_number' => $isFaculty && filled($data['employee_number'] ?? null) ? trim($data['employee_number']) : null,
            'course_section' => $data['course_section'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            // only student or faculty can be picked here: never an admin, whatever the form sends
            'role' => $isFaculty ? User::ROLE_FACULTY : User::ROLE_STUDENT,
            'password' => $data['password'], // hashed automatically by the User model
        ]);

        ActivityLog::record('Registered', $user->roleLabel() . ' account: ' . $user->email, $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->homeFor($user))->with('success', 'Account created. Welcome, ' . $user->name . '!');
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
        return route('items.index'); // STUDENT DASHBOARD or ADMIN DASHBOARD
    }
}
