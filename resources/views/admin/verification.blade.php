<x-layouts.dashboard role="admin" title="Verification Center" heading="Verification Center" subheading="Review Ghana Card, video and business applications.">

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach (['All', 'Pending', 'High risk', 'Ghana Card', 'Video'] as $i => $filter)
            <button class="badge {{ $i === 0 ? 'bg-ink text-white' : 'bg-white text-ink-soft shadow-soft hover:bg-slate-50' }}">{{ $filter }}</button>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="hidden grid-cols-12 gap-4 border-b border-slate-100 bg-surface-muted px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted lg:grid">
            <div class="col-span-4">Business</div>
            <div class="col-span-2">Category</div>
            <div class="col-span-2">Documents</div>
            <div class="col-span-2">Risk</div>
            <div class="col-span-2 text-right">Action</div>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach ($queue as $item)
                <div class="grid grid-cols-2 items-center gap-4 px-5 py-4 lg:grid-cols-12">
                    <div class="col-span-2 flex items-center gap-3 lg:col-span-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-surface-muted text-sm font-bold text-ink">
                            {{ \Illuminate\Support\Str::of($item['business'])->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $item['business'] }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $item['owner'] }} · {{ $item['submitted'] }}</p>
                        </div>
                    </div>

                    <div class="lg:col-span-2"><span class="badge-info">{{ $item['category'] }}</span></div>

                    <div class="flex gap-1.5 lg:col-span-2">
                        <span class="badge {{ $item['ghana_card'] ? 'badge-verified' : 'badge-rejected' }}" title="Ghana Card">
                            <x-icon name="id-card" class="h-3.5 w-3.5" />
                        </span>
                        <span class="badge {{ $item['video'] ? 'badge-verified' : 'badge-pending' }}" title="Video">
                            <x-icon name="video" class="h-3.5 w-3.5" />
                        </span>
                    </div>

                    <div class="lg:col-span-2">
                        @php $riskBadge = ['low' => 'badge-verified', 'medium' => 'badge-pending', 'high' => 'badge-rejected'][$item['risk']]; @endphp
                        <span class="{{ $riskBadge }}">{{ ucfirst($item['risk']) }}</span>
                    </div>

                    <div class="col-span-2 flex justify-end gap-2 lg:col-span-2">
                        <button class="btn-outline btn-sm text-rose-600 hover:border-rose-300">Reject</button>
                        <button class="btn-primary btn-sm">Approve</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</x-layouts.dashboard>
