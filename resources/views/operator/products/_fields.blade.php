{{-- Shared operator product form fields. Expects $product (Product|null) and $categories. --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-ink" for="name">Item name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $product?->name) }}" class="form-input" required maxlength="160" />
    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="mb-1.5 block text-sm font-medium text-ink" for="business_category_id">Category</label>
    <select id="business_category_id" name="business_category_id" class="form-select">
        <option value="">Uncategorised</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((int) old('business_category_id', $product?->business_category_id) === $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    @error('business_category_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label class="mb-1.5 block text-sm font-medium text-ink" for="price">Price (GH₵)</label>
        <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product?->price) }}" class="form-input" required />
        @error('price')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-ink" for="quantity">Quantity in stock</label>
        <input id="quantity" name="quantity" type="number" min="0" value="{{ old('quantity', $product?->quantity) }}" class="form-input" placeholder="Leave blank for services" />
        @error('quantity')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="mb-1.5 block text-sm font-medium text-ink" for="description">Description</label>
    <textarea id="description" name="description" rows="5" class="form-input" maxlength="5000" placeholder="Describe your item…">{{ old('description', $product?->description) }}</textarea>
    @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="mb-1.5 block text-sm font-medium text-ink" for="images">Images</label>
    <input id="images" name="images[]" type="file" accept="image/*" multiple class="form-input" />
    <p class="mt-1 text-xs text-ink-muted">Up to 6 images · JPG, PNG or WebP · max 5MB each.</p>
    @error('images.*')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
</div>
