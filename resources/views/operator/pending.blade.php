<x-layouts.onboarding title="Verification in progress">

    @php
        use App\Enums\OperatorVerificationStatus;
        $status = $profile?->verification_status ?? OperatorVerificationStatus::Pending;
        $isRejected = $status === OperatorVerificationStatus::Rejected;
        $operatorName = $profile?->owner_full_name ?? auth()->user()?->name;
        $firstName = \Illuminate\Support\Str::of((string) $operatorName)->trim()->explode(' ')->first();
        $businessName = $profile?->business_name;
    @endphp

    <div class="card p-6 text-center sm:p-10">
        <span class="mx-auto grid h-16 w-16 place-items-center rounded-full {{ $isRejected ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600' }}">
            <x-icon name="{{ $isRejected ? 'flag' : 'shield' }}" class="h-8 w-8" />
        </span>

        @if ($firstName)
            <p class="mt-5 text-sm font-semibold text-primary-600">Welcome, {{ $firstName }} 👋</p>
        @endif

        <h1 class="mt-1 font-display text-2xl font-bold text-ink">
            {{ $isRejected ? 'Application needs attention' : 'Your business is under review' }}
        </h1>

        <p class="mt-3 text-sm text-ink-muted">
            @if ($isRejected)
                An administrator could not verify your application. Please review the note below and resubmit —
                we'll notify you by email and SMS the moment there's an update.
            @else
                Thank you for registering{{ $businessName ? ' '.$businessName : '' }}. Our team is carefully reviewing
                your Ghana Card and verification video to keep LYVO safe and trusted. Your operator dashboard unlocks
                automatically once you're approved, and we'll notify you by email and SMS as soon as your account is
                approved — or if we need anything further.
            @endif
        </p>

        <div class="mx-auto mt-4 inline-flex">
            <span class="badge bg-{{ $status->badgeColor() }}-50 text-{{ $status->badgeColor() }}-700">{{ $status->label() }}</span>
        </div>

        @if ($isRejected && $profile?->rejection_reason)
            <div class="mt-6 rounded-2xl bg-rose-50 p-4 text-left text-sm text-rose-800">
                <p class="font-semibold">Reviewer note</p>
                <p class="mt-1">{{ $profile->rejection_reason }}</p>
            </div>
        @endif

        {{-- Verification timeline --}}
        <div class="mx-auto mt-8 max-w-md space-y-1 text-left">
            @php
                $steps = [
                    ['label' => 'Registration submitted', 'done' => (bool) $profile?->submitted_at],
                    ['label' => 'Ghana Card uploaded', 'done' => (bool) $profile?->ghana_card_submitted_at],
                    ['label' => 'Video verification submitted', 'done' => (bool) $profile?->video_submitted_at],
                    ['label' => 'Admin review', 'done' => $status->isApproved(), 'current' => ! $status->isApproved() && ! $isRejected],
                    ['label' => 'Verified operator', 'done' => $status->isApproved()],
                ];
            @endphp
            @foreach ($steps as $step)
                <div class="flex items-center gap-3 rounded-xl px-3 py-3 {{ ($step['current'] ?? false) ? 'bg-amber-50' : '' }}">
                    @if ($step['done'])
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-primary-500 text-white"><x-icon name="check" class="h-4 w-4" /></span>
                    @elseif ($step['current'] ?? false)
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-amber-400 text-white">⏳</span>
                    @else
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full border-2 border-slate-200"></span>
                    @endif
                    <span class="text-sm font-medium {{ $step['done'] || ($step['current'] ?? false) ? 'text-ink' : 'text-ink-muted' }}">{{ $step['label'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('home') }}" class="btn-ghost">Back to home</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-outline">Log out</button>
            </form>
        </div>
    </div>

</x-layouts.onboarding>
