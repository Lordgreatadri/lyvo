<x-layouts.dashboard role="admin" title="Verification Center" heading="Verification Center" subheading="Review Ghana Card, video and business applications.">

    @if (session('status'))
        <div class="mb-6 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="hidden grid-cols-12 gap-4 border-b border-slate-100 bg-surface-muted px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted lg:grid">
            <div class="col-span-4">Business</div>
            <div class="col-span-2">Category</div>
            <div class="col-span-2">Documents</div>
            <div class="col-span-1">Status</div>
            <div class="col-span-3 text-right">Action</div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($queue as $operator)
                @php
                    $hasCard = $operator->getFirstMedia('ghana_card_front') !== null;
                    $hasVideo = $operator->getFirstMedia('verification_video') !== null;
                @endphp
                <div x-data="{ rejecting: false }" class="px-5 py-4">
                    <div class="grid grid-cols-2 items-center gap-4 lg:grid-cols-12">
                        <div class="col-span-2 flex items-center gap-3 lg:col-span-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-surface-muted text-sm font-bold text-ink">
                                {{ \Illuminate\Support\Str::of($operator->business_name)->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink">{{ $operator->business_name }}</p>
                                <p class="truncate text-xs text-ink-muted">{{ $operator->owner_full_name }} · {{ $operator->submitted_at?->diffForHumans() ?? 'Not submitted' }}</p>
                            </div>
                        </div>

                        <div class="lg:col-span-2"><span class="badge-info">{{ $operator->category?->name ?? 'Uncategorised' }}</span></div>

                        <div class="flex gap-1.5 lg:col-span-2">
                            <span class="badge {{ $hasCard ? 'badge-verified' : 'badge-pending' }}" title="Ghana Card">
                                <x-icon name="id-card" class="h-3.5 w-3.5" />
                            </span>
                            <span class="badge {{ $hasVideo ? 'badge-verified' : 'badge-pending' }}" title="Verification video">
                                <x-icon name="badge" class="h-3.5 w-3.5" />
                            </span>
                        </div>

                        <div class="lg:col-span-1">
                            <span class="badge bg-{{ $operator->verification_status->badgeColor() }}-50 text-{{ $operator->verification_status->badgeColor() }}-700">
                                {{ $operator->verification_status->label() }}
                            </span>
                        </div>

                        <div class="col-span-2 flex justify-end gap-2 lg:col-span-3">
                            <a href="{{ route('admin.operators.show', $operator) }}" class="btn-outline btn-sm">Review</a>
                            <button type="button" @click="rejecting = !rejecting" class="btn-outline btn-sm text-rose-600 hover:border-rose-300">Reject</button>
                            <form method="POST" action="{{ route('admin.operators.approve', $operator) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-primary btn-sm">Approve</button>
                            </form>
                        </div>
                    </div>

                    {{-- Inline reject form (reason required) --}}
                    <form x-show="rejecting" x-cloak method="POST" action="{{ route('admin.operators.reject', $operator) }}" class="mt-3 flex flex-col gap-2 sm:flex-row">
                        @csrf @method('PATCH')
                        <input type="text" name="rejection_reason" required maxlength="1000" class="form-input flex-1" placeholder="Reason for rejection…" />
                        <button type="submit" class="btn btn-sm border border-rose-200 text-rose-600 hover:bg-rose-50">Confirm rejection</button>
                    </form>
                </div>
            @empty
                <div class="p-10 text-center">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-surface-muted text-ink-muted"><x-icon name="check-circle" class="h-7 w-7" /></span>
                    <p class="mt-4 font-semibold text-ink">No applications awaiting review</p>
                    <p class="mt-1 text-sm text-ink-muted">New operator submissions will appear here.</p>
                </div>
            @endforelse
        </div>
    </div>

</x-layouts.dashboard>
