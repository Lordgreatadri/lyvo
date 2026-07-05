<x-layouts.dashboard role="operator" title="My Catalogue" heading="Catalogue" subheading="Manage the items you sell on LYVO.">

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-ink-muted"><span class="font-semibold text-ink">{{ $products->total() }}</span> items</p>
        <a href="{{ route('operator.products.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Add item</a>
    </div>

    @if ($products->count())
        <div class="card overflow-hidden">
            <div class="divide-y divide-slate-100">
                @foreach ($products as $product)
                    @php $image = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images'); @endphp
                    <div class="flex items-center gap-4 p-4">
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                            @if ($image)
                                <img src="{{ $image }}" alt="{{ $product->name }}" class="h-full w-full object-cover" />
                            @else
                                <div class="grid h-full w-full place-items-center text-primary-500"><x-icon name="box" class="h-6 w-6" /></div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $product->name }}</p>
                            <p class="truncate text-xs text-ink-muted">
                                {{ $product->category?->name ?? 'Uncategorised' }}
                                @if ($product->quantity !== null) · {{ $product->quantity }} in stock @endif
                            </p>
                        </div>
                        <p class="hidden text-sm font-semibold text-ink sm:block">GH₵ {{ number_format((float) $product->price, 2) }}</p>
                        <span @class([
                            'badge',
                            'bg-emerald-50 text-emerald-700' => $product->status->color() === 'emerald',
                            'bg-amber-50 text-amber-700' => $product->status->color() === 'amber',
                            'bg-slate-100 text-slate-600' => $product->status->color() === 'slate',
                            'bg-rose-50 text-rose-700' => $product->status->color() === 'rose',
                        ])>{{ $product->status->label() }}</span>

                        <div class="flex items-center gap-2">
                            @if ($product->isPublished())
                                <form method="POST" action="{{ route('operator.products.unpublish', $product) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn-outline btn-sm">Hide</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('operator.products.publish', $product) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn-primary btn-sm">Publish</button>
                                </form>
                            @endif
                            <a href="{{ route('operator.products.edit', $product) }}" class="text-ink-muted hover:text-ink"><x-icon name="settings" class="h-4 w-4" /></a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8">{{ $products->links() }}</div>
    @else
        <div class="card grid place-items-center gap-3 p-16 text-center">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="box" class="h-7 w-7" /></span>
            <p class="font-semibold text-ink">No items yet</p>
            <p class="text-sm text-ink-muted">Add your first item to start selling on LYVO.</p>
            <a href="{{ route('operator.products.create') }}" class="btn-primary btn-sm mt-2"><x-icon name="plus" class="h-4 w-4" /> Add item</a>
        </div>
    @endif

</x-layouts.dashboard>
