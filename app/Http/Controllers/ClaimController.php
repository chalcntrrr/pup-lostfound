<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClaimController extends Controller
{
    /** CLAIM VERIFICATION form (students only, open FOUND items only). */
    public function create(Request $request, Item $item)
    {
        $this->ensureCanClaim($request, $item);

        return view('claims.create', ['item' => $item->load('category')]);
    }

    public function store(Request $request, Item $item)
    {
        $this->ensureCanClaim($request, $item);

        $data = $request->validate([
            'contact_number' => ['required', 'regex:/^09\d{2}-?\d{3}-?\d{4}$/'],
            'date_lost' => ['required', 'date', 'before_or_equal:today'],
            'proof_details' => ['required', 'string', 'min:10', 'max:1000'],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
        ], [
            'contact_number.regex' => 'Contact number must follow this format: 09XX-XXX-XXXX',
            'proof_details.min' => 'Please describe the item in more detail (at least 10 characters).',
            'proof_image.required' => 'Proof of Claim is required.',
            'proof_image.max' => 'The photo must not be bigger than 4 MB.',
            'date_lost.required' => 'Date Lost is required.',
        ], [
            'proof_details' => 'description',
            'proof_image' => 'proof of claim',
        ]);

        $user = $request->user();
        $user->update(['contact_number' => $data['contact_number']]); // keep it on the account for next time

        // The proof photo is private (storage/app/private): only the claimant and admins can view it.
        $claim = Claim::create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'proof_details' => $data['proof_details'],
            'proof_image_path' => $request->file('proof_image')->store('claim-proofs', 'local'),
            'contact_number' => $data['contact_number'],
            'date_lost' => $data['date_lost'],
            'status' => 'pending',
        ]);

        ActivityLog::record('Submitted claim', "Claim #{$claim->id} for item #{$item->id} {$item->name}");

        return redirect()->route('claims.mine')
            ->with('success', 'Claim submitted! An admin will verify it. Bring a valid ID when you pick up the item.');
    }

    public function mine(Request $request)
    {
        return view('claims.mine', [
            'claims' => $request->user()->claims()->with(['item.category', 'user', 'reviewer'])->latest()->paginate(10),
        ]);
    }

    /** A student can cancel their own claim while it is still pending. */
    public function destroy(Request $request, Claim $claim)
    {
        abort_unless($claim->user_id === $request->user()->id && $claim->isPending(), 403,
            'You can only cancel your own pending claims.');

        if ($claim->proof_image_path) {
            Storage::disk('local')->delete($claim->proof_image_path);
        }
        $claim->delete();
        ActivityLog::record('Cancelled claim', "Claim #{$claim->id}");

        return back()->with('success', 'Claim cancelled.');
    }

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

    private function ensureCanClaim(Request $request, Item $item): void
    {
        $user = $request->user();

        abort_unless($item->canBeClaimedBy($user), 403,
            'Only open FOUND items can be claimed, and you cannot claim your own report.');

        if ($item->claims()->where('user_id', $user->id)->where('status', 'pending')->exists()) {
            abort(redirect()->route('items.show', $item)
                ->with('error', 'You already have a pending claim for this item.'));
        }

        if ($item->claims()->where('user_id', $user->id)->where('status', 'rejected')->exists()) {
            abort(redirect()->route('items.show', $item)
                ->with('error', 'Your claim for this item was rejected. If you believe it is yours, please visit the Lost and Found office.'));
        }
    }
}
