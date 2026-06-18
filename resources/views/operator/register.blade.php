<x-layouts.onboarding title="Become a Verified Operator">

    <div x-data="{ step: 1, total: 4 }" class="space-y-8">

        {{-- ===== Step indicator ===== --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <h1 class="font-display text-2xl font-bold text-ink">Become a Verified Operator</h1>
                <span class="badge-info" x-text="`Step ${step} of ${total}`">Step 1 of 4</span>
            </div>
            <p class="text-sm text-ink-muted">Identity verification is required before your business is approved and listed publicly.</p>

            <div class="mt-6 grid grid-cols-4 gap-2">
                <template x-for="i in total" :key="i">
                    <div class="h-1.5 rounded-full transition-colors" :class="i <= step ? 'bg-lyvo-gradient' : 'bg-slate-200'"></div>
                </template>
            </div>
        </div>

        {{-- ===== STEP 1: Business Information ===== --}}
        <div x-show="step === 1" x-transition class="card p-6 sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="briefcase" class="h-5 w-5" /></span>
                <div>
                    <h2 class="text-lg font-semibold text-ink">Business Information</h2>
                    <p class="text-sm text-ink-muted">Tell customers about your business.</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="form-label">Business name</label>
                    <input type="text" name="business_name" class="form-input" placeholder="e.g. Adwoa Couture" />
                </div>
                <div>
                    <label class="form-label">Owner full name</label>
                    <input type="text" name="name" class="form-input" placeholder="Adwoa Mensah" />
                </div>
                <div>
                    <label class="form-label">Phone number</label>
                    <input type="tel" name="phone" class="form-input" placeholder="+233 24 000 0000" />
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="business@example.com" />
                </div>

                {{-- Login credentials: owner name, email, phone & password are stored on the users table --}}
                <div class="sm:col-span-2">
                    <div class="flex items-center gap-2 rounded-xl bg-sky-50 px-3.5 py-2.5 text-xs text-sky-700">
                        <x-icon name="lock" class="h-4 w-4 shrink-0" />
                        Your email, phone &amp; password below become your secure login credentials.
                    </div>
                </div>
                <x-password-input name="password" label="Password" required />
                <x-password-input name="password_confirmation" label="Confirm password" required />

                <div>
                    <label class="form-label">Business category</label>
                    <select name="category" class="form-select">
                        <option value="">Select a category</option>
                        @foreach (\App\Support\DemoData::categories() as $category)
                            <option value="{{ $category['slug'] }}">{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Business location</label>
                    <input type="text" name="location" class="form-input" placeholder="Accra, Greater Accra" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Business description</label>
                    <textarea rows="3" name="description" class="form-textarea" placeholder="What do you sell and what makes you trustworthy?"></textarea>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button @click="step = 2" class="btn-primary">Continue <x-icon name="arrow-right" class="h-4 w-4" /></button>
            </div>
        </div>

        {{-- ===== STEP 2: Ghana Card Verification ===== --}}
        <div x-show="step === 2" x-cloak x-transition class="card p-6 sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-lyvo-gradient-soft text-brand-blue"><x-icon name="id-card" class="h-5 w-5" /></span>
                <div>
                    <h2 class="text-lg font-semibold text-ink">Ghana Card Verification</h2>
                    <p class="text-sm text-ink-muted">Identity verification required before approval.</p>
                </div>
            </div>

            <div class="rounded-2xl bg-sky-50 p-4 text-sm text-sky-800">
                <div class="flex items-start gap-2">
                    <x-icon name="shield" class="mt-0.5 h-4 w-4 shrink-0" />
                    <p>Your Ghana Card is checked against the national database (MetaMap GovCheck) to confirm your identity.</p>
                </div>
            </div>

            <div class="mt-6 space-y-5">
                <div>
                    <label class="form-label">Ghana Card number</label>
                    <input type="text" class="form-input" placeholder="GHA-000000000-0" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach (['Front', 'Back'] as $side)
                        <div>
                            <label class="form-label">Ghana Card ({{ $side }})</label>
                            <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-surface-muted p-6 text-center">
                                <span class="grid h-11 w-11 place-items-center rounded-xl bg-white text-primary-600 shadow-soft"><x-icon name="id-card" class="h-5 w-5" /></span>
                                <p class="mt-3 text-sm font-medium text-ink">Upload {{ strtolower($side) }} image</p>
                                <p class="text-xs text-ink-muted">PNG or JPG, up to 5MB</p>
                                <button type="button" class="btn-outline btn-sm mt-3">Choose file</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 flex justify-between">
                <button @click="step = 1" class="btn-ghost">Back</button>
                <button @click="step = 3" class="btn-primary">Continue <x-icon name="arrow-right" class="h-4 w-4" /></button>
            </div>
        </div>

        {{-- ===== STEP 3: Video Verification ===== --}}
        <div x-show="step === 3" x-cloak x-transition class="card p-6 sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-lyvo-gradient-soft text-brand-teal"><x-icon name="video" class="h-5 w-5" /></span>
                <div>
                    <h2 class="text-lg font-semibold text-ink">Video Verification</h2>
                    <p class="text-sm text-ink-muted">Helps protect customers and reduce fraud.</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="flex flex-col items-center justify-center rounded-2xl bg-ink p-8 text-center text-white">
                    <span class="relative grid h-16 w-16 place-items-center rounded-full bg-white/10">
                        <x-icon name="video" class="h-7 w-7" />
                        <span class="absolute inset-0 animate-pulse-ring rounded-full bg-white/30"></span>
                    </span>
                    <p class="mt-4 font-semibold">Record a live verification video</p>
                    <p class="mt-1 text-sm text-slate-400">Or upload a short pre-recorded clip</p>
                    <div class="mt-5 flex gap-3">
                        <button type="button" class="btn bg-white text-ink hover:bg-white/90 btn-sm">Record now</button>
                        <button type="button" class="btn border border-white/30 text-white hover:bg-white/10 btn-sm">Upload video</button>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-ink">In your video, please:</p>
                    <ul class="mt-4 space-y-3">
                        @foreach (['Make sure your face is clearly visible', 'State your business name', 'State your full name', 'Show your Ghana Card briefly'] as $instruction)
                            <li class="flex items-center gap-3 text-sm text-ink-soft">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary-50 text-primary-600"><x-icon name="check" class="h-3.5 w-3.5" /></span>
                                {{ $instruction }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="mt-8 flex justify-between">
                <button @click="step = 2" class="btn-ghost">Back</button>
                <button @click="step = 4" class="btn-primary">Submit for review <x-icon name="arrow-right" class="h-4 w-4" /></button>
            </div>
        </div>

        {{-- ===== STEP 4: Verification Status Tracker ===== --}}
        <div x-show="step === 4" x-cloak x-transition class="card p-6 sm:p-8 text-center">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-primary-50 text-primary-600">
                <x-icon name="check-circle" class="h-8 w-8" />
            </span>
            <h2 class="mt-5 font-display text-2xl font-bold text-ink">Application submitted!</h2>
            <p class="mt-2 text-sm text-ink-muted">Your account is in <span class="font-semibold text-amber-600">Pending Review</span>. The dashboard unlocks once an admin verifies your business.</p>

            <div class="mx-auto mt-8 max-w-md space-y-1 text-left">
                @foreach (\App\Support\DemoData::verificationSteps() as $vstep)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-3 {{ $vstep['state'] === 'current' ? 'bg-amber-50' : '' }}">
                        @if ($vstep['state'] === 'done')
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-primary-500 text-white"><x-icon name="check" class="h-4 w-4" /></span>
                        @elseif ($vstep['state'] === 'current')
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-amber-400 text-white">⏳</span>
                        @else
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full border-2 border-slate-200"></span>
                        @endif
                        <span class="text-sm font-medium {{ $vstep['state'] === 'pending' ? 'text-ink-muted' : 'text-ink' }}">{{ $vstep['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('operator.dashboard') }}" class="btn-primary">Preview operator dashboard</a>
                <a href="{{ route('home') }}" class="btn-ghost">Back to home</a>
            </div>
        </div>

    </div>

</x-layouts.onboarding>
