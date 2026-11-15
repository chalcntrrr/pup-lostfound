<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Protected by 'role:admin'; account management (add admin, delete, reset password) by 'role:superadmin' (see routes/web.php). */
class AdminController extends Controller
{
    /** "+ Add Another Admin": SUPER ADMIN ONLY (route middleware role:superadmin). */
    public function createAdmin()
    {
        return view('admin.create-admin', ['slotsLeft' => User::superAdminSlotsLeft()]);
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_SUPERADMIN])],
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s.,\'-]+$/u'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.regex' => 'Name can only contain letters, spaces, commas, periods and hyphens.',
            'email.unique' => 'Username already taken. Choose another.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        if ($data['role'] === User::ROLE_SUPERADMIN && User::superAdminSlotsLeft() === 0) {
            return back()->withInput()->withErrors([
                'role' => 'There can only be ' . User::MAX_SUPERADMINS . ' Super Admins. Create this account as an Admin.',
            ]);
        }

        $admin = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'password' => $data['password'],
        ]);

        ActivityLog::record('Created ' . strtolower($admin->roleLabel()) . ' account', $admin->email);

        return redirect()->route('admin.users')
            ->with('success', "Account created. {$admin->name} can now log in as {$admin->roleLabel()}.");
    }

    /** SUPER ADMIN ONLY: set a new password for someone who forgot theirs (not another super admin). */
    public function resetPassword(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'A Super Admin changes their own password on the Account page.');
        }

        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ], [
            'new_password.required' => 'Enter a new password.',
            'new_password.min' => 'Password must be at least 6 characters.',
        ]);

        $user->update(['password' => $data['new_password']]);
        ActivityLog::record('Reset password', "{$user->name} ({$user->email})");

        return back()->with('success', "Password reset for {$user->name}. Give them the new password and ask them to change it on their Account page.");
    }

    public function claims(Request $request)
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])],
        ])['status'] ?? 'pending';

        return view('admin.claims', [
            'status' => $status,
            'claims' => Claim::with(['item.category', 'user', 'reviewer'])
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    /** Approve: claim -> approved, item -> claimed, other pending claims on the item -> rejected. */
    public function approve(Request $request, Claim $claim)
    {
        if (! $claim->isPending() || ! $claim->item->isOpen()) {
            return back()->with('error', 'This claim can no longer be approved.');
        }

        // The admin checks the claimant's ID at the office before releasing the item.
        $data = $request->validate([
            'id_presented' => ['required', 'string', 'max:100'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'id_presented.required' => 'Enter the ID the claimant presented before approving.',
        ]);
        $notes = $data['admin_notes'] ?? null;

        $claim->approveAndRelease($request->user(), $data['id_presented'], $notes);

        ActivityLog::record('Approved claim', "Claim #{$claim->id} by {$claim->claimantName()} for item #{$claim->item_id} {$claim->item->name}");

        return back()->with('success', 'Claim approved. The item is now marked as CLAIMED.')
            ->with('slip', $claim->id);
    }

    public function reject(Request $request, Claim $claim)
    {
        $notes = $request->validate(['admin_notes' => ['nullable', 'string', 'max:500']])['admin_notes'] ?? null;

        if (! $claim->isPending()) {
            return back()->with('error', 'This claim was already reviewed.');
        }

        $claim->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('Rejected claim', "Claim #{$claim->id} by {$claim->claimantName()}");

        return back()->with('success', 'Claim rejected.');
    }

    public function users(Request $request)
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        return view('admin.users', [
            'search' => $search,
            'users' => User::withCount(['items', 'claims'])
                ->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")))
                ->orderBy('role')->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Admins can delete accounts (not their own). The account's reports and claims go with it,
     * so accounts tied to official records are kept (see User::deletionBlockReason).
     */
    public function destroyUser(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin accounts cannot be deleted.');
        }

        if ($reason = $user->deletionBlockReason()) {
            return back()->with('error', "{$user->name} can't be deleted. {$reason}");
        }

        $label = "{$user->name} ({$user->email})";
        $user->delete();
        ActivityLog::record('Deleted user', $label);

        return back()->with('success', "Deleted {$label}.");
    }

    /** SUPER ADMIN ONLY: make an existing Admin a Super Admin (there can only be MAX_SUPERADMINS). */
    public function promote(Request $request, User $user)
    {
        if ($user->role !== User::ROLE_ADMIN) {
            return back()->with('error', 'Only an Admin account can be promoted to Super Admin.');
        }

        $promoted = DB::transaction(function () use ($user) {
            // Lock the current super admins so two people can't both take the last slot.
            $count = User::where('role', User::ROLE_SUPERADMIN)->lockForUpdate()->count();
            if ($count >= User::MAX_SUPERADMINS) {
                return false;
            }
            $user->update(['role' => User::ROLE_SUPERADMIN]);

            return true;
        });

        if (! $promoted) {
            return back()->with('error', 'There are already ' . User::MAX_SUPERADMINS . ' Super Admins (the maximum). Demote one of them to Admin first, then promote ' . $user->name . '.');
        }

        ActivityLog::record('Promoted to Super Admin', "{$user->name} ({$user->email})");

        return back()->with('success', "{$user->name} is now a Super Admin.");
    }

    /** SUPER ADMIN ONLY: step another Super Admin down to Admin (frees a slot). Never yourself. */
    public function demote(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot demote yourself. Ask the other Super Admin to do it.');
        }

        if (! $user->isSuperAdmin()) {
            return back()->with('error', 'This account is not a Super Admin.');
        }

        $user->update(['role' => User::ROLE_ADMIN]);
        ActivityLog::record('Demoted to Admin', "{$user->name} ({$user->email})");

        return back()->with('success', "{$user->name} is now an Admin. A Super Admin slot is free.");
    }

    public function logs()
    {
        return view('admin.logs', [
            'logs' => ActivityLog::with('user')->latest('id')->paginate(25),
            'keep' => ActivityLog::KEEP,
        ]);
    }
}
