<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Claim Slip {{ $claim->referenceNo() }} - PUPSRC Lost and Found</title>
    <link rel="icon" href="{{ asset('images/logo-dashboard.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
{{-- LOST AND FOUND CLAIM SLIP: same layout as the Java ClaimSlipPdfGenerator.
     Top half = admin copy, bottom half = claimant's receipt. Use the browser's Print (or "Save as PDF"). --}}
@php
    $item = $claim->item;
    $claimantName = $claim->claimantName();
    $claimantSection = $claim->claimantSection();
    $released = $claim->reviewed_at ?? $claim->updated_at;
    $officer = $claim->reviewer?->name ?? 'Admin Account / Property Officer';
    $itemTitle = strtoupper($item->name) . ' (Item ID: #' . $item->id . ')';
@endphp
<body class="slip-page">
    <div class="slip-toolbar no-print">
        <a class="btn-cancel" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}">BACK</a>
        <button class="btn-primary" type="button" onclick="window.print()">PRINT / SAVE AS PDF</button>
    </div>

    <div class="slip-sheet">
        @foreach (['admin', 'claimant'] as $copy)
            @if ($copy === 'claimant')
                <div class="slip-detach"><span>✂</span> DETACH HERE (PORTION TO BE KEPT BY THE CLAIMANT)</div>
            @endif

            <section class="slip">
                <header class="slip-head">
                    <img src="{{ asset('images/logo.png') }}" alt="">
                    <div class="slip-org">
                        <div class="slip-uni">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</div>
                        <div class="slip-office">Office of the Vice President for Administration - Property Office</div>
                        <div class="slip-title">LOST AND FOUND CLAIM SLIP</div>
                    </div>
                    <div class="slip-ref">
                        <div class="slip-ref-label">Reference No:</div>
                        <div class="slip-ref-no">{{ $claim->referenceNo() }}</div>
                        <div class="slip-barcode" aria-hidden="true"></div>
                    </div>
                </header>

                @if ($copy === 'admin')
                    <p class="slip-banner admin">[ ADMIN COPY ] This form contains verification information for internal inventory auditing and security tracking purposes.</p>

                    <div class="slip-grid">
                        <span class="slip-label">CLAIMANT NAME</span><span class="slip-value">{{ strtoupper($claimantName) }}</span>
                        <span class="slip-label">DATE FILED</span><span class="slip-value">{{ $claim->created_at->format('m / d / Y') }}</span>
                        <span class="slip-label">COURSE &amp; SECTION</span><span class="slip-value">{{ $claimantSection ?: '-' }}</span>
                        <span class="slip-label">CONTACT / EMAIL</span><span class="slip-value">{{ $claim->claimantContact() ?: '-' }}</span>
                    </div>

                    <div class="slip-box admin">
                        <div class="slip-box-title">VERIFIED RELEASED ITEM SPECIFICATIONS</div>
                        <div class="slip-item">{{ $itemTitle }}</div>
                        <div class="slip-small">Unique Identifiers Match: {{ \Illuminate\Support\Str::limit($claim->proof_details, 160) }}</div>
                    </div>

                    <div class="slip-grid">
                        <span class="slip-label">ID DOCUMENT PRESENTED</span><span class="slip-value">{{ $claim->id_presented ?: 'Submitted ID / Proof' }}</span>
                        <span class="slip-label">VERIFICATION METHOD</span><span class="slip-value">Proof of Claim Reviewed</span>
                    </div>

                    <div class="slip-signs">
                        <div><span class="slip-sign-name">{{ strtoupper($claimantName) }}</span><span class="slip-sign-caption">Signature over Printed Name of Claimant</span></div>
                        <div><span class="slip-sign-name">{{ strtoupper($officer) }}</span><span class="slip-sign-caption">Authorized Releasing Officer Signature</span></div>
                    </div>
                @else
                    <p class="slip-banner claimant">[ CLAIMANT'S RECEIPT ] Please keep this copy protected and in a safe place as your official proof of item recovery.</p>

                    <div class="slip-grid">
                        <span class="slip-label">CLAIMANT NAME</span><span class="slip-value">{{ strtoupper($claimantName) }}</span>
                        <span class="slip-label">DATE RELEASED</span><span class="slip-value">{{ $released->format('m / d / Y') }}</span>
                        <span class="slip-label">COURSE &amp; SECTION</span><span class="slip-value">{{ $claimantSection ?: '-' }}</span>
                        <span class="slip-label">TIME RELEASED</span><span class="slip-value">{{ $released->format('h:i A') }}</span>
                    </div>

                    <div class="slip-box claimant">
                        <div class="slip-box-title">RELEASED ITEM DETAILS</div>
                        <div class="slip-item">{{ $itemTitle }}</div>
                        <div class="slip-small">The item above has been thoroughly validated and officially handed over to the rightful owner.</div>
                    </div>

                    <div class="slip-signs">
                        <div><span class="slip-sign-name">{{ strtoupper($claimantName) }}</span><span class="slip-sign-caption">Signature of Claimant</span></div>
                        <div><span class="slip-sign-name">{{ strtoupper($officer) }}</span><span class="slip-sign-caption">Authorized Releasing Officer Seal</span></div>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</body>
</html>
