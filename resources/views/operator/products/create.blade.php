<x-layouts.dashboard role="operator" title="Add item" heading="Add a new item" subheading="List a product or service for sale.">

    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('operator.products.store') }}" enctype="multipart/form-data" class="card space-y-5 p-6">
            @csrf
            @include('operator.products._fields', ['product' => null])

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('operator.products.index') }}" class="btn-outline btn-sm">Cancel</a>
                <button class="btn-primary">Create item</button>
            </div>
        </form>
    </div>

</x-layouts.dashboard>
