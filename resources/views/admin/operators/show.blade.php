<x-layouts.dashboard role="admin" title="Review Operator" heading="{{ $operator->business_name }}" subheading="Verify identity and approve or reject this operator.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Details + media --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-6">
                <h2 class="font-semibold text-ink">Business details</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-ink-muted">Owner</dt><dd class="font-medium text-ink">{{ $operator->owner_full_name }}</dd></div>
                    <div><dt class="text-ink-muted">Category</dt><dd class="font-medium text-ink">{{ $operator->category?->name ?? '—' }}</dd></div>
                    <div><dt class="text-ink-muted">Email</dt><dd class="font-medium text-ink">{{ $operator->user->email }}</dd></div>
                    <div><dt class="text-ink-muted">Phone</dt><dd class="font-medium text-ink">{{ $operator->user->phone }}</dd></div>
                    <div><dt class="text-ink-muted">Location</dt><dd class="font-medium text-ink">{{ $operator->business_location }}</dd></div>
                    <div><dt class="text-ink-muted">Ghana Card</dt><dd class="font-medium text-ink">{{ $operator->ghana_card_number ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-ink-muted">Description</dt><dd class="text-ink-soft">{{ $operator->business_description }}</dd></div>
                </dl>
            </div>

            <div class="card p-6">
                <h2 class="font-semibold text-ink">Identity &amp; video</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @php
                        $front = $operator->getFirstMediaUrl('ghana_card_front');
                        $back = $operator->getFirstMediaUrl('ghana_card_back');
                        $video = $operator->getFirstMediaUrl('verification_video');
                    @endphp
                    <div>
                        <p class="mb-2 text-xs font-medium text-ink-muted">Ghana Card (Front)</p>
                        @if ($front)
                            <a href="{{ $front }}" target="_blank"><img src="{{ $front }}" alt="Ghana Card front" class="h-32 w-full rounded-xl object-cover"></a>
                        @else
                            <div class="grid h-32 place-items-center rounded-xl bg-surface-muted text-xs text-ink-muted">Not provided</div>
                        @endif
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-medium text-ink-muted">Ghana Card (Back)</p>
                        @if ($back)
                            <a href="{{ $back }}" target="_blank"><img src="{{ $back }}" alt="Ghana Card back" class="h-32 w-full rounded-xl object-cover"></a>
                        @else
                            <div class="grid h-32 place-items-center rounded-xl bg-surface-muted text-xs text-ink-muted">Not provided</div>
                        @endif
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-medium text-ink-muted">Verification video</p>
                        @if ($video)
                            <video src="{{ $video }}" controls class="h-32 w-full rounded-xl bg-ink object-cover"></video>
                        @else
                            <div class="grid h-32 place-items-center rounded-xl bg-surface-muted text-xs text-ink-muted">Not provided</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Audit trail --}}
            <div class="card p-6">
                <h2 class="font-semibold text-ink">Verification history</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($operator->verificationEvents->sortByDesc('created_at') as $event)
                        <div class="flex items-start gap-3 text-sm">
                            <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-surface-muted text-ink-soft"><x-icon name="shield" class="h-3.5 w-3.5" /></span>
                            <div>
                                <p class="text-ink"><span class="font-medium">{{ $event->from_status ?? 'new' }}</span> &rarr; <span class="font-medium">{{ $event->to_status }}</span></p>
                                <p class="text-xs text-ink-muted">{{ $event->actor?->name ?? 'System' }} · {{ $event->created_at->diffForHumans() }}</p>
                                @if ($event->notes)<p class="mt-1 text-xs text-ink-soft">{{ $event->notes }}</p>@endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">No history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Decision panel --}}
        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-xs font-medium text-ink-muted">Current status</p>
                <p class="mt-1">
                    <span class="badge bg-{{ $operator->verification_status->badgeColor() }}-50 text-{{ $operator->verification_status->badgeColor() }}-700">
                        {{ $operator->verification_status->label() }}
                    </span>
                </p>

                @unless ($operator->isApproved())
                    <form method="POST" action="{{ route('admin.operators.review', $operator) }}" class="mt-4">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn-outline w-full">Mark under review</button>
                    </form>

                    <form method="POST" action="{{ route('admin.operators.approve', $operator) }}" class="mt-2">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn-primary w-full">Approve operator</button>
                    </form>
                @endunless

                <form method="POST" action="{{ route('admin.operators.reject', $operator) }}" class="mt-4 space-y-2">
                    @csrf @method('PATCH')
                    <label class="form-label">Rejection reason</label>
                    <textarea name="rejection_reason" rows="3" class="form-textarea" placeholder="Explain what's missing or invalid…">{{ old('rejection_reason') }}</textarea>
                    <x-input-error :messages="$errors->get('rejection_reason')" class="mt-1" />
                    <button type="submit" class="btn w-full border border-rose-200 text-rose-600 hover:bg-rose-50">Reject application</button>
                </form>
            </div>

            <a href="{{ route('admin.operators.index') }}" class="btn-ghost w-full justify-center">Back to queue</a>
        </div>
    </div>

</x-layouts.dashboard>
