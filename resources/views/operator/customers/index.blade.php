<x-layouts.dashboard role="operator" title="Customers" heading="Customers" subheading="Everyone who has ordered from your store.">

    <div class="card overflow-hidden">
        @if ($customers->isEmpty())
            <div class="p-12 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-surface-muted text-ink-muted"><x-icon name="users" class="h-7 w-7" /></span>
                <p class="mt-4 font-semibold text-ink">No customers yet</p>
                <p class="mt-1 text-sm text-ink-muted">Buyers appear here after their first order.</p>
            </div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">Customer</th>
                        <th class="px-5 py-3 font-medium">Orders</th>
                        <th class="px-5 py-3 font-medium">Spent</th>
                        <th class="px-5 py-3 font-medium">Last order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($customers as $row)
                        <tr class="hover:bg-surface-muted">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-500 to-brand-teal text-xs font-bold text-white">
                                        {{ \Illuminate\Support\Str::of($row['customer']->name)->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-ink">{{ $row['customer']->name }}</p>
                                        <p class="text-xs text-ink-muted">{{ $row['customer']->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-medium text-ink">{{ $row['orders'] }}</td>
                            <td class="px-5 py-4 font-semibold text-primary-700">GH₵ {{ number_format((float) $row['spent'], 2) }}</td>
                            <td class="px-5 py-4 text-ink-muted">{{ \Illuminate\Support\Carbon::parse($row['last_order'])->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</x-layouts.dashboard>
