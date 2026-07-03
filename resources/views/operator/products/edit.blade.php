<x-layouts.dashboard role="operator" title="Edit item" heading="Edit item" :subheading="$product->name">

    @if (session('status'))
        <div class="mx-auto mb-6 max-w-2xl rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mx-auto max-w-2xl space-y-6">
        {{-- Existing images --}}
        @if ($product->getMedia('images')->count())
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-ink">Current images</h2>
                <div class="grid grid-cols-4 gap-3 sm:grid-cols-6">
                    @foreach ($product->getMedia('images') as $media)
                        <div class="aspect-square overflow-hidden rounded-xl bg-surface-muted">
                            <img src="{{ $media->getUrl('thumb') ?: $media->getUrl() }}" alt="" class="h-full w-full object-cover" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('operator.products.update', $product) }}" enctype="multipart/form-data" class="card space-y-5 p-6">
            @csrf
            @method('PUT')
            @include('operator.products._fields', ['product' => $product])

            <div class="flex items-center justify-between border-t border-slate-100 pt-5">
                <button form="delete-product" class="text-sm font-medium text-rose-600 hover:underline">Delete item</button>
                <div class="flex items-center gap-3">
                    <a href="{{ route('operator.products.index') }}" class="btn-outline btn-sm">Back</a>
                    <button class="btn-primary">Save changes</button>
                </div>
            </div>
        </form>

        <form id="delete-product" method="POST" action="{{ route('operator.products.destroy', $product) }}" onsubmit="return confirm('Delete this item permanently?');">
            @csrf
            @method('DELETE')
        </form>
    </div>

</x-layouts.dashboard>
