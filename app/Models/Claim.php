<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Claim extends Model
{
    protected $fillable = [
        'item_id', 'user_id', 'claimant_name', 'claimant_id_number', 'proof_details', 'proof_image_path', 'contact_number',
        'id_presented',
        'date_lost', 'status', 'admin_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_lost' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** True when the item was released to a walk-in owner (no account). */
    public function isWalkIn(): bool
    {
        return $this->user_id === null;
    }

    public function claimantName(): string
    {
        return $this->user?->name ?? $this->claimant_name ?? 'Unknown';
    }

    /** Student / employee number of the claimant (walk-in: the number the admin typed in). */
    public function claimantIdNumber(): ?string
    {
        return $this->user ? $this->user->idNumber() : $this->claimant_id_number;
    }

    public function claimantSection(): ?string
    {
        return $this->user?->course_section;
    }

    public function claimantContact(): ?string
    {
        return $this->contact_number ?: $this->user?->email;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /** Claim slip reference number, e.g. 2026-LF-0012 (same format as the Java claim slip). */
    public function referenceNo(): string
    {
        return ($this->reviewed_at ?? $this->created_at)->format('Y') . '-LF-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Release the item to this claimant (the admin checked their ID at the office):
     * claim APPROVED, item CLAIMED, other pending claims on the item rejected, and the
     * claimant's own open lost report for the same item closed (RESOLVED, linked).
     */
    public function approveAndRelease(User $admin, string $idPresented, ?string $notes = null): void
    {
        DB::transaction(function () use ($admin, $idPresented, $notes) {
            $this->update([
                'status' => 'approved',
                'id_presented' => $idPresented,
                'admin_notes' => $notes,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $found = $this->item;

            // Find the claimant's matching open lost report(s) while the item is still open.
            // (A walk-in owner has no account, so the admin resolves their report directly.)
            $matchingLost = $this->user_id === null
                ? collect()
                : Item::where('user_id', $this->user_id)->where('type', 'lost')->open()->get()
                    ->filter(fn (Item $lost) => $lost->possibleMatches(20)->contains('id', $found->id));

            $found->update(['status' => 'claimed']);

            $found->claims()->whereKeyNot($this->id)->where('status', 'pending')->update([
                'status' => 'rejected',
                'admin_notes' => 'The item was released to its verified owner.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // The claimant got their item back: close their matching lost report(s).
            $matchingLost->each(function (Item $lost) use ($found) {
                    $lost->update(['status' => 'resolved', 'resolution' => 'matched', 'matched_item_id' => $found->id]);
                    $lost->claims()->where('status', 'pending')->update(['status' => 'rejected', 'admin_notes' => 'Report was resolved.', 'reviewed_at' => now()]);
                });
        });
    }

    /**
     * Admin releases a found item straight to the person standing at the office (no account, no earlier
     * report): the claim is created already APPROVED, the item becomes CLAIMED and a claim slip is issued.
     * $d = claimant_name, claimant_id_number?, claimant_contact?, proof_details, id_presented
     */
    public static function releaseToClaimant(Item $found, User $admin, array $d): self
    {
        $claim = self::create([
            'item_id' => $found->id,
            'user_id' => null,
            'claimant_name' => trim($d['claimant_name']),
            'claimant_id_number' => ($d['claimant_id_number'] ?? null) ?: null,
            'proof_details' => mb_substr($d['proof_details'], 0, 1000),
            'contact_number' => ($d['claimant_contact'] ?? null) ?: null,
            'status' => 'pending',
        ]);

        $claim->approveAndRelease($admin, $d['id_presented']);

        return $claim->fresh();
    }

    /**
     * Admin resolves a lost report with a found item: create the claim for the owner and release it.
     * The owner is the report's account holder, or (walk-in report) the person typed in by the admin.
     */
    public static function releaseToOwner(Item $found, Item $lost, User $admin, string $idPresented, string $details, $dateLost): self
    {
        $walkIn = $lost->isWalkIn();

        $claim = self::create([
            'item_id' => $found->id,
            'user_id' => $walkIn ? null : $lost->user_id,
            'claimant_name' => $walkIn ? $lost->owner_name : null,
            'claimant_id_number' => $walkIn ? $lost->owner_id_number : null,
            'proof_details' => mb_substr($details, 0, 1000),
            'contact_number' => $walkIn ? mb_substr((string) $lost->owner_contact, 0, 20) : $lost->user->contact_number,
            'date_lost' => $dateLost,
            'status' => 'pending',
        ]);
        $claim->approveAndRelease($admin, $idPresented, 'Released at the office after matching the lost report.');

        return $claim;
    }
}
