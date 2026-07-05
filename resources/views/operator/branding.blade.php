<x-layouts.dashboard role="operator" title="Storefront branding" heading="Storefront branding" subheading="Upload your own cover banner and logo for your public page.">

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $coverUrl = $profile->getFirstMediaUrl('cover');
        $logoUrl = $profile->getFirstMediaUrl('logo');
    @endphp

    <form method="POST" action="{{ route('operator.branding.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-6">
        @csrf

        {{-- Cover --}}
        <div class="card p-6">
            <h2 class="font-semibold text-ink">Cover banner</h2>
            <p class="text-sm text-ink-muted">Recommended 1600×400. JPG, PNG or WEBP up to 4MB.</p>
            <div class="mt-4 h-40 overflow-hidden rounded-2xl {{ $coverUrl ? '' : 'bg-lyvo-gradient' }}">
                @if ($coverUrl)<img src="{{ $coverUrl }}" alt="Current cover" class="h-full w-full object-cover" />@endif
            </div>
            <input type="file" name="cover" accept="image/*" class="form-input mt-4" />
        </div>

        {{-- Logo --}}
        <div class="card p-6">
            <h2 class="font-semibold text-ink">Logo</h2>
            <p class="text-sm text-ink-muted">Square image works best. JPG, PNG or WEBP up to 2MB.</p>
            <div class="mt-4 flex items-center gap-4">
                <div class="h-20 w-20 overflow-hidden rounded-2xl bg-lyvo-gradient ring-2 ring-white">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Current logo" class="h-full w-full object-cover" />
                    @else
                        <span class="grid h-full w-full place-items-center text-xl font-bold text-white">{{ \Illuminate\Support\Str::of($profile->business_name)->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}</span>
                    @endif
                </div>
                <input type="file" name="logo" accept="image/*" class="form-input flex-1" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary"><x-icon name="check-circle" class="h-5 w-5" /> Save branding</button>
            <a href="{{ route('directory.show', $profile) }}" target="_blank" class="btn-outline"><x-icon name="globe" class="h-5 w-5" /> Preview public page</a>
        </div>
    </form>

</x-layouts.dashboard>
