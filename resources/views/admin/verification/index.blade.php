@extends('layouts.app')

@section('content')
    <div class="space-y-8">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold">Verification Queue</h1>
            <p class="text-gray-500">
                Review business profile credentials and submitted licenses
            </p>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-4 text-left">Company</th>
                        <th class="p-4 text-left">Licenses</th>
                        <th class="p-4 text-left">Status</th>
                        <th class="p-4 text-left">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($distributors as $d)
                        <tr class="border-t">
                            <td class="p-4 font-medium">
                                {{ $d->company_name }}
                            </td>

                             <td class="p-4 space-y-2">
                                <div class="font-bold text-xs uppercase text-gray-400 mb-1">Registration Docs</div>
                                <div class="space-y-1">
                                    @php
                                        $docs = [
                                            'valid_id' => ['label' => 'Valid ID', 'path' => $d->valid_id_path, 'expiry' => $d->valid_id_expires_at],
                                            'business_license' => ['label' => 'Business Permit', 'path' => $d->business_license_path, 'expiry' => $d->business_license_expires_at],
                                            'dti_sec' => ['label' => 'DTI/SEC', 'path' => $d->dti_sec_path, 'expiry' => $d->dti_sec_expires_at],
                                            'bir_form' => ['label' => 'BIR Form', 'path' => $d->bir_form_path, 'expiry' => $d->bir_form_expires_at],
                                            'fda_license' => ['label' => 'FDA License', 'path' => $d->fda_license_path, 'expiry' => $d->fda_license_expires_at],
                                            'prc_id' => ['label' => 'PRC ID', 'path' => $d->prc_id_path, 'expiry' => $d->prc_id_expires_at],
                                        ];
                                    @endphp

                                    @foreach($docs as $key => $doc)
                                        @if($doc['path'])
                                            <div class="flex items-center justify-between gap-4 text-xs">
                                                <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank" class="text-blue-600 hover:underline">
                                                    {{ $doc['label'] }}
                                                </a>
                                                <span class="{{ $doc['expiry'] && \Carbon\Carbon::parse($doc['expiry'])->isPast() ? 'text-red-600 font-bold' : ($doc['expiry'] && \Carbon\Carbon::parse($doc['expiry'])->diffInDays(now()) < 30 ? 'text-amber-600 font-medium' : 'text-gray-500') }}">
                                                    {{ $doc['expiry'] ? \Carbon\Carbon::parse($doc['expiry'])->format('M d, Y') : 'No Date' }}
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                @if($d->licenses->count() > 0)
                                    <div class="font-bold text-xs uppercase text-gray-400 mt-3 mb-1">Additional Licenses</div>
                                    @foreach($d->licenses as $l)
                                        <div class="flex items-center justify-between gap-4 text-xs border-l-2 border-blue-500 pl-2">
                                            <a href="{{ asset('storage/' . $l->file_path) }}" target="_blank" class="text-blue-600 hover:underline">
                                                {{ $l->type }}
                                            </a>
                                            <span class="{{ $l->expires_at && \Carbon\Carbon::parse($l->expires_at)->isPast() ? 'text-red-600 font-bold' : ($l->expires_at && \Carbon\Carbon::parse($l->expires_at)->diffInDays(now()) < 30 ? 'text-amber-600 font-medium' : 'text-gray-500') }}">
                                                {{ $l->expires_at ? \Carbon\Carbon::parse($l->expires_at)->format('M d, Y') : 'No Date' }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </td>

                            <td class="p-4">
                                @if($d->is_verified)
                                    <span class="text-green-600 font-semibold">
                                        Verified
                                    </span>
                                @else
                                    <span class="text-yellow-600 font-semibold">
                                        Pending
                                    </span>
                                @endif
                            </td>

                            <td class="p-4 space-x-2">
                                @if(!$d->is_verified)
                                    <form method="POST" action="{{ route('admin.verifications.approve', $d->id) }}" class="inline"
                                        onsubmit="return confirm('Are you sure you want to approve this business profile?')">
                                        @csrf
                                        <button class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                            Approve
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.verifications.reject', $d->id) }}" class="inline"
                                        onsubmit="return confirm('Are you sure you want to REJECT this business profile?')">
                                        @csrf
                                        <button class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                            Reject
                                        </button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-500">
                                No pending verifications.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection