<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Keeps a copy of the system's records in the Firebase Realtime Database.
 *
 * MySQL stays the main database (search, filters, matching and claims depend on it). Every time an item,
 * a claim or an admin account is saved or deleted, the same record is written to Firebase:
 *
 *     items/{id}    claims/{id}    users/{id}
 *
 * Only what is needed to SEE the data in the Firebase console is copied. Never copied: passwords,
 * contact numbers, ID numbers, the finder's details, the claimant's description and photos.
 *
 * Firebase is a copy, so a Firebase problem must never break the system: every call is wrapped,
 * failures are only written to storage/logs/laravel.log.
 */
class FirebaseSync
{
    /** Is syncing switched on (FIREBASE_SYNC) and is the Firebase database URL set? */
    public static function enabled(): bool
    {
        if (! config('services.firebase_sync.enabled')) {
            return false;
        }

        $project = config('firebase.default', 'app');

        return filled(config("firebase.projects.{$project}.database.url"));
    }

    /** Write (or, when $data is null, remove) one record. Returns false when Firebase could not be reached. */
    public static function put(string $path, ?array $data): bool
    {
        try {
            $reference = app('firebase.database')->getReference($path);
            $data === null ? $reference->remove() : $reference->set($data);

            return true;
        } catch (\Throwable $e) {
            Log::warning("Firebase sync failed for {$path}: " . $e->getMessage());

            return false;
        }
    }

    /** Copy a saved record to Firebase (or remove it when it should not be there). */
    public static function sync(Model $model): bool
    {
        $path = self::path($model);
        if (! $path || ! self::enabled()) {
            return true;
        }

        return self::put($path, self::payload($model));
    }

    /** Remove a deleted record from Firebase. */
    public static function forget(Model $model): bool
    {
        $path = self::path($model);
        if (! $path || ! self::enabled()) {
            return true;
        }

        return self::put($path, null);
    }

    public static function path(Model $model): ?string
    {
        return match (true) {
            $model instanceof Item => "items/{$model->id}",
            $model instanceof Claim => "claims/{$model->id}",
            $model instanceof User => "users/{$model->id}",
            default => null,
        };
    }

    /** What is stored in Firebase for this record. Null = nothing (e.g. an old student account). */
    public static function payload(Model $model): ?array
    {
        return match (true) {
            $model instanceof Item => self::item($model),
            $model instanceof Claim => self::claim($model),
            $model instanceof User => self::user($model),
            default => null,
        };
    }

    private static function item(Item $item): array
    {
        $walkIn = $item->isWalkIn();

        return [
            'id' => $item->id,
            'type' => $item->type,                       // lost / found
            'name' => $item->name,
            'category' => $item->category?->name,
            'description' => $item->description,
            'location' => $item->location,
            'date_happened' => $item->date_happened?->format('Y-m-d'),
            'time_happened' => $item->time_happened ? (string) $item->time_happened : null,
            'status' => $item->status,                   // open / claimed / resolved
            'resolution' => $item->resolution,           // matched / outside
            'matched_item_id' => $item->matched_item_id,
            'walk_in' => $walkIn,
            'owner_name' => $walkIn ? $item->owner_name : null,
            'has_photo' => filled($item->image_path),
            'logged_by' => $item->user_id,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    private static function claim(Claim $claim): array
    {
        return [
            'id' => $claim->id,
            'item_id' => $claim->item_id,
            'status' => $claim->status,                  // pending / approved / rejected
            'claimant_name' => $claim->claimantName(),
            'walk_in' => $claim->isWalkIn(),
            'id_presented' => $claim->id_presented,      // the kind of ID shown, e.g. "PUP School ID"
            'reviewed_by' => $claim->reviewed_by,
            'reviewed_at' => $claim->reviewed_at?->toIso8601String(),
            'created_at' => $claim->created_at?->toIso8601String(),
        ];
    }

    /** Only office accounts (admin / super admin) are copied. */
    private static function user(User $user): ?array
    {
        if (! $user->isAdmin()) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
