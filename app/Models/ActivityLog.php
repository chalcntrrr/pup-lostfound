<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public const UPDATED_AT = null; // logs are never edited

    /** Only the latest this-many entries are kept; older ones are deleted automatically. */
    public const KEEP = 1000;

    protected $fillable = ['user_id', 'action', 'details', 'ip_address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Save one line to the activity log, e.g. ActivityLog::record('Approved claim', 'Claim #3').
     * Only ADMIN and SUPER ADMIN actions are logged: what students and faculty do (log in,
     * report items, claim items) is not office business, so it is skipped here.
     */
    public static function record(string $action, ?string $details = null, ?User $user = null): void
    {
        $actor = $user ?? Auth::user();

        if (! $actor || ! $actor->isAdmin()) {
            return;
        }

        self::create([
            'user_id' => $actor->id,
            'action' => $action,
            'details' => $details ? mb_substr($details, 0, 250) : null,
            'ip_address' => request()?->ip(),
        ]);

        // Keep the table small: drop everything older than the latest KEEP entries.
        $cutoff = (int) self::max('id') - self::KEEP;
        if ($cutoff > 0) {
            self::where('id', '<=', $cutoff)->delete();
        }
    }
}
