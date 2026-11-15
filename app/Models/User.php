<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_FACULTY = 'faculty';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPERADMIN = 'superadmin';

    /** At most two super admins: one can be the backup of the other. */
    public const MAX_SUPERADMINS = 2;

    protected $fillable = [
        'name',
        'email',
        'student_number',
        'employee_number',
        'role',
        'contact_number',
        'course_section',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // passwords are always stored hashed (bcrypt)
        ];
    }

    /** Admins AND super admins (a super admin can do everything an admin can). */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPERADMIN], true);
    }

    /** Only super admins manage accounts: add admins, delete accounts, reset passwords. */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_SUPERADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_FACULTY => 'Faculty',
            default => 'Student',
        };
    }

    public static function superAdminSlotsLeft(): int
    {
        return max(0, self::MAX_SUPERADMINS - self::where('role', self::ROLE_SUPERADMIN)->count());
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isFaculty(): bool
    {
        return $this->role === self::ROLE_FACULTY;
    }

    /** Students AND faculty: the people who report their own lost items and claim found items. */
    public function isMember(): bool
    {
        return $this->isStudent() || $this->isFaculty();
    }

    /** Student number for students, employee number for faculty (null for admins). */
    public function idNumber(): ?string
    {
        return $this->student_number ?: $this->employee_number;
    }

    /** "Student ID" or "Employee ID", for labels. */
    public function idLabel(): string
    {
        return $this->isFaculty() ? 'Employee ID' : 'Student ID';
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    /** Claims this admin approved or rejected. */
    public function reviewedClaims(): HasMany
    {
        return $this->hasMany(Claim::class, 'reviewed_by');
    }

    /**
     * Why this account must be kept (null = it can be deleted).
     * Deleting an account also deletes its reports and claims, so accounts tied to official
     * records (released items, claim slips, logged found items) are kept for the office.
     */
    public function deletionBlockReason(): ?string
    {
        if ($this->claims()->where('status', 'approved')->exists()) {
            return 'This account has received items (approved claims and claim slips on record).';
        }
        if ($this->items()->where('type', 'found')->exists()) {
            return 'This account logged surrendered items that are on record.';
        }
        if ($this->items()->whereNotNull('owner_name')->exists()) {
            return 'This account logged walk-in lost reports that are on record.';
        }
        if ($this->reviewedClaims()->exists()) {
            return 'This account reviewed claims; its name is on claim slips.';
        }

        return null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }
}
