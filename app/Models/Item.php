<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    public const TYPES = ['lost', 'found'];
    public const STATUSES = ['open', 'claimed', 'resolved'];

    /** A found item unclaimed this long is flagged for the office to decide (donate, dispose, etc.). */
    public const LONG_UNCLAIMED_DAYS = 30;

    // Common places on campus (from the old app). Users can still type their own.
    public const LOCATIONS = [
        'Main Lobby', 'Court', 'Court Bleacher', 'Kubo', 'Library', 'Canteen',
        'Room 201', 'Room 302', 'Room 303', 'Main Gate', 'Parking Area', 'Other',
    ];

    protected $fillable = [
        'user_id', 'category_id', 'type', 'name', 'description',
        'location', 'date_happened', 'time_happened', 'image_path', 'is_anonymous', 'status',
        'finder_name', 'finder_student_id', 'finder_contact',
        'owner_name', 'owner_id_number', 'owner_contact',
        'resolution', 'matched_item_id',
    ];

    protected function casts(): array
    {
        return [
            'date_happened' => 'date',
            'is_anonymous' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    /** For a lost report resolved with an existing found item: that found item. */
    public function matchedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'matched_item_id');
    }

    /** For a found item: the lost report(s) that were resolved with it. */
    public function resolvedLostReports(): HasMany
    {
        return $this->hasMany(Item::class, 'matched_item_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function isLost(): bool
    {
        return $this->type === 'lost';
    }

    public function isFound(): bool
    {
        return $this->type === 'found';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /** The label shown on badges: LOST / FOUND while open, otherwise CLAIMED / RESOLVED. */
    public function statusLabel(): string
    {
        return $this->isOpen() ? strtoupper($this->type) : strtoupper($this->status);
    }

    /** Whole days a found item has been kept at the office (since it was logged). */
    public function daysAtOffice(): int
    {
        return (int) $this->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay());
    }

    public function isLongUnclaimed(): bool
    {
        return $this->isFound() && $this->isOpen() && $this->daysAtOffice() >= self::LONG_UNCLAIMED_DAYS;
    }

    public function daysAtOfficeLabel(): string
    {
        $days = $this->daysAtOffice();

        return $days === 0 ? 'Since today' : $days . ' ' . ($days === 1 ? 'day' : 'days');
    }

    /** "2:30 PM", or null when no time was given. */
    public function timeLabel(): ?string
    {
        return $this->time_happened ? \Carbon\Carbon::parse($this->time_happened)->format('g:i A') : null;
    }

    /**
     * Who may post what: students and faculty report their own LOST items; the office (admins)
     * logs FOUND items and can also log a LOST item for a walk-in owner who has no account.
     */
    public static function typesFor(User $user): array
    {
        return $user->isAdmin() ? ['found', 'lost'] : ['lost'];
    }

    /** A LOST report an admin logged for someone without an account (owner_* hold that person's details). */
    public function isWalkIn(): bool
    {
        return $this->isLost() && $this->owner_name !== null;
    }

    /** Name of whoever lost the item: the walk-in owner, or the account that posted the report. */
    public function ownerName(): string
    {
        return $this->isWalkIn() ? $this->owner_name : $this->user->name;
    }

    /** Student / employee number of whoever lost the item (null when unknown). */
    public function ownerIdNumber(): ?string
    {
        return $this->isWalkIn() ? $this->owner_id_number : $this->user->idNumber();
    }

    public function ownerContact(): ?string
    {
        return $this->isWalkIn() ? $this->owner_contact : $this->user->contact_number;
    }

    /** Released items are official records: they can't be deleted (null = can be deleted). */
    public function deleteBlockReason(): ?string
    {
        if ($this->status === 'claimed' || $this->claims()->where('status', 'approved')->exists()) {
            return 'This item was already released to its owner, so it stays on record.';
        }

        return null;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    // ---- Permission rules -------------------------------------------------

    /** Owner can edit/delete while the post is still open; admins always can. */
    public function canBeManagedBy(User $user): bool
    {
        return $user->isAdmin() || ($user->id === $this->user_id && $this->isOpen());
    }

    /** Only students can claim, only open FOUND items, and not their own post. */
    public function canBeClaimedBy(User $user): bool
    {
        return $user->isMember()
            && $this->isFound()
            && $this->isOpen()
            && $user->id !== $this->user_id;
    }

    // ---- Matching (ported from the Java ItemService) ---------------------

    private const NOISE_WORDS = [
        'a', 'an', 'and', 'at', 'for', 'from', 'in', 'is', 'it', 'of',
        'on', 'or', 'the', 'to', 'with', 'lost', 'found', 'item',
    ];

    /** Open items of the opposite type that look similar to this one (best first). */
    public function possibleMatches(int $limit = 6): Collection
    {
        if (! $this->isOpen()) {
            return new Collection();
        }

        $otherType = $this->isLost() ? 'found' : 'lost';

        return self::with('category')
            ->open()
            ->where('type', $otherType)
            ->get()
            ->map(function (Item $other) {
                $other->match_score = $this->matchScore($other);

                return $other;
            })
            ->filter(fn (Item $other) => $other->match_score >= 4)
            ->sortByDesc('match_score')
            ->take($limit)
            ->values();
    }

    private function matchScore(Item $other): int
    {
        $score = $this->category_id === $other->category_id ? 3 : 0;

        $myName = self::words($this->name);
        $theirName = self::words($other->name);
        $myDesc = self::words($this->description);
        $theirDesc = self::words($other->description);

        $score += count(array_intersect($myName, $theirName)) * 3;
        $score += count(array_intersect($myName, $theirDesc)) * 2;
        $score += count(array_intersect($myDesc, $theirName)) * 2;
        $score += count(array_intersect($myDesc, $theirDesc));

        if (strcasecmp(trim($this->name), trim($other->name)) === 0) {
            $score += 5;
        }

        return $score;
    }

    private static function words(?string $text): array
    {
        if (! $text) {
            return [];
        }

        $words = preg_split('/[^a-z0-9]+/', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(
            $words,
            fn ($w) => strlen($w) > 2 && ! in_array($w, self::NOISE_WORDS, true)
        )));
    }
}
