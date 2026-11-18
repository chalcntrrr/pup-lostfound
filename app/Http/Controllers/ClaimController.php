<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClaimController extends Controller
{
    /** Shows the private proof-of-claim photo to the claimant or an admin. */
    public function proof(Request $request, Claim $claim)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $claim->user_id === $user->id, 403);
        abort_unless($claim->proof_image_path && Storage::disk('local')->exists($claim->proof_image_path), 404);

        return response()->file(Storage::disk('local')->path($claim->proof_image_path));
    }

    /** LOST AND FOUND CLAIM SLIP (admin copy + claimant's receipt), like the Java ClaimSlipPdfGenerator. */
    public function slip(Request $request, Claim $claim)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $claim->user_id === $user->id, 403);
        abort_unless($claim->isApproved(), 404, 'A claim slip is only available for approved claims.');

        return view('claims.slip', ['claim' => $claim->load(['item.category', 'user', 'reviewer'])]);
    }

}
