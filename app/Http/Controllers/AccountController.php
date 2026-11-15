<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Claim;
use Illuminate\Http\Request;

/** ACCOUNT page: profile circle, role, quick buttons and the user's own history. */
class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('account.show', [
            'user' => $user,
            'history' => ActivityLog::where('user_id', $user->id)->latest('id')->take(15)->get(),
            'pendingClaims' => $user->isAdmin() ? Claim::where('status', 'pending')->count() : 0,
            'openPassword' => $request->session()->has('errors') && $request->session()->get('errors')->hasAny(['current_password', 'password']),
        ]);
    }

    /** Update name / contact / course from the Account page. */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s.,\'-]+$/u'],
            'contact_number' => ['nullable', 'regex:/^09\d{2}-?\d{3}-?\d{4}$/'],
            'course_section' => ['nullable', 'string', 'max:50'],
        ], [
            'name.regex' => 'Name can only contain letters, spaces, commas, periods and hyphens.',
            'contact_number.regex' => 'Contact number must follow this format: 09XX-XXX-XXXX',
        ]);

        if ($user->isAdmin()) {
            unset($data['course_section']);
        }

        $user->update($data);
        ActivityLog::record('Updated account');

        return back()->with('success', 'Account updated.');
    }

    /** Change Password (everyone). */
    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'password.different' => 'The new password must be different from the current one.',
        ], [
            'password' => 'new password',
        ]);

        $request->user()->update(['password' => $request->input('password')]);
        ActivityLog::record('Changed password');

        return back()->with('success', 'Password changed.');
    }
}
