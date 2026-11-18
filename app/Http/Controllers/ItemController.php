<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public const FILTERS = ['All', 'LOST', 'FOUND', 'CLAIMED', 'RESOLVED'];

    /** Students and faculty only browse items that are still open (LOST / FOUND). */
    public const MEMBER_FILTERS = ['All', 'LOST', 'FOUND'];

    /** The filter choices this account sees on the dashboard. */
    public static function filtersFor($user): array
    {
        return $user->isAdmin() ? self::FILTERS : self::MEMBER_FILTERS;
    }

    /** After login everyone lands on the dashboard (the item board). */
    public function dashboard()
    {
        return redirect()->route('items.index');
    }

    /** STUDENT DASHBOARD / ADMIN DASHBOARD: item cards with search + status filter. */
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', Rule::in(self::FILTERS)],
        ]);

        $filter = $data['filter'] ?? 'All';
        if (! in_array($filter, self::filtersFor($request->user()), true)) {
            $filter = 'All'; // e.g. a student typing ?filter=CLAIMED in the address bar
        }

        // "All" = everything still open (lost + found). CLAIMED and RESOLVED items are history:
        // they leave the board, and admins can still find them under their own filters.
        $items = Item::with(['category', 'user'])
            ->when($filter === 'All', fn ($q) => $q->open())
            ->when($filter === 'LOST', fn ($q) => $q->open()->where('type', 'lost'))
            ->when($filter === 'FOUND', fn ($q) => $q->open()->where('type', 'found'))
            ->when($filter === 'CLAIMED', fn ($q) => $q->where('status', 'claimed'))
            ->when($filter === 'RESOLVED', fn ($q) => $q->where('status', 'resolved'))
            ->when($data['q'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(16)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'filter' => $filter,
            'filters' => self::filtersFor($request->user()),
            'search' => $data['q'] ?? '',
        ]);
    }

    /** LOST ITEM REPORT / FOUND ITEM REPORT form. */
    public function create(Request $request)
    {
        $type = $request->query('type') === 'found' ? 'found' : 'lost';
        abort_unless(in_array($type, Item::typesFor($request->user()), true), 403,
            'Found items are logged by the Lost and Found office. Please surrender the item at the office.');

        return view('items.form', [
            'item' => new Item(['type' => $type]),
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $item = new Item($data);
        // An admin logging a LOST item is doing it for a walk-in owner (someone without an account).
        $walkIn = $item->isLost() && $request->user()->isAdmin();
        if (! $walkIn) {
            $this->saveContactNumber($request);
        }

        $item->user_id = $request->user()->id; // the account that logged it (for a walk-in: the admin)
        $item->status = 'open';
        $item->fill($this->finderDetails($request, $item->type));
        $item->fill($this->ownerDetails($request, $walkIn));
        $item->image_path = $request->hasFile('image') ? $this->saveImage($request->file('image')) : null;
        $item->save();

        ActivityLog::record($walkIn ? 'Logged walk-in lost item' : 'Posted ' . $item->type . ' item',
            $walkIn ? "#{$item->id} {$item->name} for {$item->owner_name}" : "#{$item->id} {$item->name}");

        return redirect()->route('items.show', $item)
            ->with('success', 'Report saved! The item report has been securely logged.');
    }

    /** FULL DETAILS page. */
    public function show(Request $request, Item $item)
    {
        $user = $request->user();
        $item->load(['category', 'user', 'matchedItem', 'resolvedLostReports']);

        // Admins see every claim; students only see their own.
        $claims = $item->claims()->with(['user', 'reviewer'])->latest()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->get();

        return view('items.show', [
            'item' => $item,
            'claims' => $claims,
            'matches' => $item->possibleMatches(8),
            'hasPendingClaim' => $item->claims()->where('user_id', $user->id)->where('status', 'pending')->exists(),
            'wasRejected' => $item->claims()->where('user_id', $user->id)->where('status', 'rejected')->exists(),
        ]);
    }

    public function edit(Request $request, Item $item)
    {
        $this->authorizeManage($request, $item);

        return view('items.form', [
            'item' => $item,
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Item $item)
    {
        $this->authorizeManage($request, $item);

        $data = $this->validated($request, $item);
        if ($item->user_id === $request->user()->id && ! $item->isWalkIn()) {
            $this->saveContactNumber($request);
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($item);
            $data['image_path'] = $this->saveImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($item);
            $data['image_path'] = null;
        }

        $data += $this->finderDetails($request, $data['type']);
        $data += $this->ownerDetails($request, $item->isWalkIn());

        $item->update($data);
        ActivityLog::record('Edited item', "#{$item->id} {$item->name}");

        return redirect()->route('items.show', $item)->with('success', 'Report updated.');
    }

    public function destroy(Request $request, Item $item)
    {
        $this->authorizeManage($request, $item);

        if ($reason = $item->deleteBlockReason()) {
            return back()->with('error', $reason);
        }

        $this->deleteImage($item);
        $item->delete();
        ActivityLog::record('Deleted item', "#{$item->id} {$item->name}");

        return redirect()->route($request->user()->isAdmin() ? 'items.index' : 'items.mine')
            ->with('success', 'Report deleted.');
    }

    /**
     * RESOLVE (same choices as the Java RESOLVE dialog):
     *  - "matched": an admin resolves a LOST report with an existing FOUND item.
     *               The found item becomes CLAIMED, the lost report becomes RESOLVED, and they are linked.
     *  - "outside": resolved outside the system (e.g. the owner got it back another way,
     *               or an unclaimed found item was turned over / disposed).
     * The owner of an open lost report can only choose "outside" ("I got it back").
     */
    public function resolve(Request $request, Item $item)
    {
        $user = $request->user();
        $isOwnerOfOpenLost = $item->user_id === $user->id && $item->isLost() && $item->isOpen();

        abort_unless($user->isAdmin() || $isOwnerOfOpenLost, 403, 'You cannot resolve this report.');
        abort_if($item->status === 'resolved', 422, 'This report is already resolved.');

        $canMatch = $user->isAdmin() && $item->isLost() && $item->isOpen();

        $data = $request->validate([
            'mode' => ['required', Rule::in($canMatch ? ['matched', 'outside'] : ['outside'])],
            'found_item_id' => ['required_if:mode,matched', 'nullable', 'integer'],
            'id_presented' => ['required_if:mode,matched', 'nullable', 'string', 'max:100'],
        ], [
            'mode.required' => 'Choose how this report was resolved.',
            'found_item_id.required_if' => 'Select the found item that matches this report.',
            'id_presented.required_if' => "Enter the ID the owner presented before releasing the item.",
        ]);

        $found = null;
        if ($data['mode'] === 'matched') {
            $found = Item::whereKey($data['found_item_id'])->where('type', 'found')->open()->first();
            if (! $found) {
                return back()->with('error', 'That found item is no longer available. Pick another one.');
            }
        }

        $ownerName = $item->ownerName();

        $claim = DB::transaction(function () use ($item, $found, $data, $user) {
            $item->update([
                'status' => 'resolved',
                'resolution' => $found ? 'matched' : 'outside',
                'matched_item_id' => $found?->id,
            ]);
            $item->claims()->where('status', 'pending')->update([
                'status' => 'rejected',
                'admin_notes' => 'Report was resolved.',
                'reviewed_at' => now(),
            ]);

            if (! $found) {
                return null;
            }

            // Releasing a found item to the lost report's owner = the same as an approved claim:
            // ID checked at the office, item CLAIMED, claim slip issued.
            return Claim::releaseToOwner($found, $item, $user, $data['id_presented'],
                "Matched with lost report #{$item->id} ({$item->name}): {$item->description}", $item->date_happened);
        });

        ActivityLog::record('Resolved item', $found
            ? "#{$item->id} {$item->name} with found item #{$found->id} {$found->name}, released to {$ownerName}"
            : "#{$item->id} {$item->name} (outside the system)");

        return $found
            ? back()->with('success', "Report RESOLVED. \"{$found->name}\" was released to {$ownerName} and is now CLAIMED.")->with('slip', $claim->id)
            : back()->with('success', 'Report marked as RESOLVED.');
    }

    /**
     * ADMIN: hand a FOUND item to the person at the office. The admin types in who they are, what they
     * said about the item and the ID they showed; the item becomes CLAIMED and a claim slip is issued.
     */
    public function release(Request $request, Item $item)
    {
        $admin = $request->user();
        abort_unless($admin->isAdmin(), 403);
        abort_unless($item->isFound(), 422, 'Only a found item can be released to a claimant.');

        $data = $request->validate([
            'claimant_name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s.,\'-]+$/u'],
            'claimant_id_number' => ['nullable', 'string', 'max:30'],
            'claimant_contact' => ['nullable', 'regex:/^09\d{2}-?\d{3}-?\d{4}$/'],
            'proof_details' => ['required', 'string', 'max:1000'],
            'id_presented' => ['required', 'string', 'max:100'],
        ], [
            'claimant_name.required' => "Enter the claimant's name.",
            'claimant_name.regex' => 'Name can only contain letters, spaces, commas, periods and hyphens.',
            'claimant_contact.regex' => 'Contact number must follow this format: 09XX-XXX-XXXX',
            'proof_details.required' => 'Write what the claimant said about the item (description, contents, marks).',
            'id_presented.required' => 'Enter the ID the claimant presented before releasing the item.',
        ]);

        $claim = DB::transaction(function () use ($item, $admin, $data) {
            // Lock the row so two admins can't release the same item twice.
            $found = Item::whereKey($item->id)->lockForUpdate()->first();
            if (! $found->isOpen()) {
                return null;
            }

            return Claim::releaseToClaimant($found, $admin, $data);
        });

        if (! $claim) {
            return back()->with('error', 'This item was already released or resolved.');
        }

        ActivityLog::record('Released item to claimant', "#{$item->id} {$item->name} to {$claim->claimant_name}");

        return back()->with('success', "\"{$item->name}\" was released to {$claim->claimant_name} and is now CLAIMED.")->with('slip', $claim->id);
    }

    /** Reports made by the logged-in user. */
    public function mine(Request $request)
    {
        $items = $request->user()->items()->with('category')->withCount('claims')->latest()->paginate(16);

        // Flag open lost reports that have a similar FOUND item at the office.
        $items->getCollection()->each(function (Item $item) {
            $item->match_count = $item->isLost() && $item->isOpen() ? $item->possibleMatches()->count() : 0;
        });

        return view('items.mine', ['items' => $items]);
    }

    // ---- helpers ----------------------------------------------------------

    private function validated(Request $request, ?Item $existing = null): array
    {
        $isLost = $request->input('type') === 'lost';
        // Walk-in report: an admin logs a LOST item for an owner who has no account.
        $walkIn = $isLost && $request->user()->isAdmin() && (! $existing || $existing->isWalkIn());
        $recordFinder = ! $isLost && ! $request->boolean('is_anonymous');
        // A report keeps its type when edited; new found items can only come from the office.
        $allowedTypes = $existing ? [$existing->type] : Item::typesFor($request->user());
        abort_if($request->filled('type') && ! in_array($request->input('type'), $allowedTypes, true), 403,
            $existing ? 'A report cannot be changed from lost to found (or back).'
                : 'Found items are logged by the Lost and Found office. Please surrender the item at the office.');

        $data = $request->validate([
            'type' => ['required', Rule::in($allowedTypes)],
            'finder_name' => $recordFinder ? ['required', 'string', 'max:100'] : ['nullable'],
            'finder_student_id' => $recordFinder ? ['nullable', 'string', 'max:30'] : ['nullable'],
            'owner_name' => $walkIn ? ['required', 'string', 'max:100', 'regex:/^[\pL\s.,\'-]+$/u'] : ['nullable'],
            'owner_id_number' => $walkIn ? ['nullable', 'string', 'max:30'] : ['nullable'],
            'owner_contact' => $walkIn ? ['required', 'regex:/^09\d{2}-?\d{3}-?\d{4}$/'] : ['nullable'],
            'finder_contact' => $recordFinder ? ['nullable', 'string', 'max:100'] : ['nullable'],
            'name' => ['required', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:100'],
            'date_happened' => ['required', 'date', 'before_or_equal:today'],
            'time_happened' => ['nullable', 'date_format:H:i'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
            'contact_number' => $isLost && ! $walkIn ? ['required', 'regex:/^09\d{2}-?\d{3}-?\d{4}$/'] : ['nullable'],
            // Status only changes through claim approval or RESOLVE (both recorded), never by editing.
            'status' => ['prohibited'],
        ], [
            'date_happened.before_or_equal' => 'The date cannot be in the future.',
            'image.max' => 'The photo must not be bigger than 4 MB.',
            'contact_number.regex' => 'Contact number must follow this format: 09XX-XXX-XXXX',
            'status.prohibited' => 'The status changes only through claim approval or RESOLVE.',
            'finder_name.required' => "Enter the finder's name, or choose Keep Anonymous.",
            'owner_name.required' => "Enter the owner's name.",
            'owner_name.regex' => 'Name can only contain letters, spaces, commas, periods and hyphens.',
            'owner_contact.required' => "Enter the owner's contact number so the office can reach them.",
            'owner_contact.regex' => 'Contact number must follow this format: 09XX-XXX-XXXX',
        ], [
            'name' => 'item name',
            'category_id' => 'category',
            'date_happened' => 'date',
            'time_happened' => 'time',
        ]);

        unset($data['contact_number'], $data['image'], $data['finder_name'], $data['finder_student_id'], $data['finder_contact'],
            $data['owner_name'], $data['owner_id_number'], $data['owner_contact']);

        return $data;
    }

    /** "Record Finder Details" or "Keep Anonymous" (found items only; visible to admins only). */
    private function finderDetails(Request $request, string $type): array
    {
        $anonymous = $type !== 'found' || $request->boolean('is_anonymous');

        return [
            'is_anonymous' => $type === 'found' && $anonymous,
            'finder_name' => $anonymous ? null : $request->input('finder_name'),
            'finder_student_id' => $anonymous ? null : $request->input('finder_student_id'),
            'finder_contact' => $anonymous ? null : $request->input('finder_contact'),
        ];
    }

    /** Walk-in LOST report: the owner's details typed in by the admin (admins only ever see them). */
    private function ownerDetails(Request $request, bool $walkIn): array
    {
        return [
            'owner_name' => $walkIn ? trim($request->input('owner_name')) : null,
            'owner_id_number' => $walkIn ? ($request->input('owner_id_number') ?: null) : null,
            'owner_contact' => $walkIn ? $request->input('owner_contact') : null,
        ];
    }

    /** Lost reports ask for a contact number; keep it on the user's account. */
    private function saveContactNumber(Request $request): void
    {
        if ($request->filled('contact_number')) {
            $request->user()->update(['contact_number' => $request->input('contact_number')]);
        }
    }

    private function authorizeManage(Request $request, Item $item): void
    {
        abort_unless($item->canBeManagedBy($request->user()), 403,
            'You can only change your own reports while they are still open.');
    }

    /** Photos go to public/uploads/items so they show up without extra setup. */
    private function saveImage(UploadedFile $file): string
    {
        $name = now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $file->extension();
        $file->move(public_path('uploads/items'), $name);

        return 'uploads/items/' . $name;
    }

    private function deleteImage(Item $item): void
    {
        if ($item->image_path && str_starts_with($item->image_path, 'uploads/')) {
            File::delete(public_path($item->image_path));
        }
    }
}
