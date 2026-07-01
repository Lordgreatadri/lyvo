<x-layouts.onboarding title="Become a Verified Operator">

    <form method="POST" action="{{ route('register.operator.store') }}" enctype="multipart/form-data"
          x-data="{ step: 1, total: 3 }" class="space-y-8">
        @csrf

        {{-- ===== Step indicator ===== --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <h1 class="font-display text-2xl font-bold text-ink">Become a Verified Operator</h1>
                <span class="badge-info" x-text="`Step ${step} of ${total}`">Step 1 of 3</span>
            </div>
            <p class="text-sm text-ink-muted">Identity verification is required before your business is approved and listed publicly.</p>

            <div class="mt-6 grid grid-cols-3 gap-2">
                <template x-for="i in total" :key="i">
                    <div class="h-1.5 rounded-full transition-colors" :class="i <= step ? 'bg-lyvo-gradient' : 'bg-slate-200'"></div>
                </template>
            </div>
        </div>

        {{-- Validation summary (errors can come from any step) --}}
        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-800">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                    <input type="text" name="business_name" value="{{ old('business_name') }}" class="form-input" placeholder="e.g. Adwoa Couture" />
                </div>
                <div>
                    <label class="form-label">Owner full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="Adwoa Mensah" />
                </div>
                <div>
                    <label class="form-label">Phone number</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" class="form-input" placeholder="+233 24 000 0000" />
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="business@example.com" />
                </div>

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
                    <select name="business_category" class="form-select">
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(old('business_category') === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Business location</label>
                    <input type="text" name="business_location" value="{{ old('business_location') }}" class="form-input" placeholder="Accra, Greater Accra" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Business description</label>
                    <textarea rows="3" name="business_description" class="form-textarea" placeholder="What do you sell and what makes you trustworthy?">{{ old('business_description') }}</textarea>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="button" @click="step = 2" class="btn-primary">Continue <x-icon name="arrow-right" class="h-4 w-4" /></button>
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
                    <p>Your Ghana Card is checked to confirm your identity. Front and back images are required.</p>
                </div>
            </div>

            <div class="mt-6 space-y-5">
                <div>
                    <label class="form-label">Ghana Card number</label>
                    <input type="text" name="ghana_card_number" value="{{ old('ghana_card_number') }}" class="form-input" placeholder="GHA-000000000-0" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Ghana Card (Front)</label>
                        <input type="file" name="ghana_card_front" accept="image/*"
                               class="block w-full text-sm text-ink-soft file:mr-4 file:rounded-xl file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-600 hover:file:bg-primary-100" />
                        <p class="mt-1 text-xs text-ink-muted">PNG or JPG, up to 5MB</p>
                    </div>
                    <div>
                        <label class="form-label">Ghana Card (Back)</label>
                        <input type="file" name="ghana_card_back" accept="image/*"
                               class="block w-full text-sm text-ink-soft file:mr-4 file:rounded-xl file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-600 hover:file:bg-primary-100" />
                        <p class="mt-1 text-xs text-ink-muted">PNG or JPG, up to 5MB</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-between">
                <button type="button" @click="step = 1" class="btn-ghost">Back</button>
                <button type="button" @click="step = 3" class="btn-primary">Continue <x-icon name="arrow-right" class="h-4 w-4" /></button>
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
                <div>
                    <label class="form-label">Upload verification video</label>
                    <input type="file" name="verification_video" accept="video/mp4,video/quicktime,video/webm"
                           class="block w-full text-sm text-ink-soft file:mr-4 file:rounded-xl file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-600 hover:file:bg-primary-100" />
                    <p class="mt-1 text-xs text-ink-muted">MP4, MOV or WebM, up to 50MB</p>
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

            <div class="mt-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800">
                After you submit, your account stays in <span class="font-semibold">Pending Review</span> until an admin verifies your business. You'll verify your email &amp; phone next.
            </div>

            <div class="mt-8 flex justify-between">
                <button type="button" @click="step = 2" class="btn-ghost">Back</button>
                <button type="submit" class="btn-primary">Submit for review <x-icon name="arrow-right" class="h-4 w-4" /></button>
            </div>
        </div>

    </form>

</x-layouts.onboarding>
