<?php

namespace App\Observers;

use App\Services\FirebaseSync;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

/**
 * Mirrors items, claims and admin accounts to Firebase. It only runs AFTER the database transaction
 * is committed, so a cancelled / failed save never leaves a record in Firebase.
 */
class FirebaseObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Model $model): void
    {
        FirebaseSync::sync($model);
    }

    public function deleted(Model $model): void
    {
        FirebaseSync::forget($model);
    }
}
