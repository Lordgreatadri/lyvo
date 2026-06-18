<x-layouts.dashboard role="operator" title="Verification" heading="Verification Status" subheading="Track your identity and business verification progress.">

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Progress tracker --}}
        <div class="card p-6 lg:col-span-2">
            <h2 class="font-semibold text-ink">Verification Progress</h2>
            <div class="mt-6 space-y-1">
                @foreach ($steps as $i => $vstep)
                    <div class="flex items-start gap-4">
                        <div class="flex flex-col items-center">
                            @if ($vstep['state'] === 'done')
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-primary-500 text-white"><x-icon name="check" class="h-4 w-4" /></span>
                            @elseif ($vstep['state'] === 'current')
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-amber-400 text-white text-sm">⏳</span>
                            @else
                                <span class="grid h-9 w-9 place-items-center rounded-full border-2 border-slate-200"></span>
                            @endif
                            @if (! $loop->last)
                                <span class="h-10 w-0.5 {{ $vstep['state'] === 'done' ? 'bg-primary-500' : 'bg-slate-200' }}"></span>
                            @endif
                        </div>
                        <div class="pb-6 pt-1">
                            <p class="font-medium {{ $vstep['state'] === 'pending' ? 'text-ink-muted' : 'text-ink' }}">{{ $vstep['label'] }}</p>
                            <p class="text-sm text-ink-muted">
                                @if ($vstep['state'] === 'done') Completed
                                @elseif ($vstep['state'] === 'current') In progress — awaiting admin review
                                @else Pending @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Verification detail cards --}}
        <div class="space-y-6">
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-ink">Submitted Documents</h3>
                <div class="mt-4 space-y-3">
                    @foreach ([['id-card', 'Ghana Card', 'Verified', 'badge-verified'], ['video', 'Video Verification', 'Submitted', 'badge-info'], ['user', 'Identity Match', 'Verified', 'badge-verified']] as [$icon, $label, $state, $badge])
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-sm text-ink-soft"><x-icon name="{{ $icon }}" class="h-4 w-4 text-primary-600" /> {{ $label }}</span>
                            <span class="{{ $badge }}">{{ $state }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card bg-amber-50 p-5">
                <div class="flex items-center gap-2 text-amber-800">
                    <x-icon name="shield" class="h-5 w-5" />
                    <p class="text-sm font-semibold">Awaiting Admin Review</p>
                </div>
                <p class="mt-2 text-sm text-amber-700">Your dashboard has limited access until an admin completes the final review of your business application.</p>
            </div>
        </div>
    </div>

</x-layouts.dashboard>
