<?php

namespace App\Support;

/**
 * DemoData
 * --------
 * Phase 1 placeholder dataset for the LYVO prototype.
 *
 * This is a temporary, in-memory source of truth used purely to demonstrate
 * the full user journey and data flow to the client before the database layer
 * is implemented. Every key/shape here intentionally mirrors the real domain
 * models we will migrate later (operators, products, escrow transactions,
 * reviews, trust scores, verification states, etc.).
 *
 * NOTE: When the DB phase begins, these arrays are replaced by Eloquent models
 * keyed by UUID (not auto-increment PK). The `uuid` field on each record below
 * already anticipates that — public routes resolve operators by `uuid`.
 */
class DemoData
{
    /**
     * Business categories shown across the directory and registration.
     *
     * @return array<int, array<string, string>>
     */
    public static function categories(): array
    {
        return [
            ['slug' => 'fashion',       'name' => 'Fashion',        'icon' => 'shirt',     'count' => 248],
            ['slug' => 'electronics',   'name' => 'Electronics',    'icon' => 'cpu',       'count' => 187],
            ['slug' => 'beauty',        'name' => 'Beauty',         'icon' => 'sparkles',  'count' => 163],
            ['slug' => 'food',          'name' => 'Food',           'icon' => 'utensils',  'count' => 211],
            ['slug' => 'services',      'name' => 'Services',       'icon' => 'briefcase', 'count' => 134],
            ['slug' => 'automotive',    'name' => 'Automotive',     'icon' => 'car',       'count' => 76],
            ['slug' => 'home-living',   'name' => 'Home & Living',  'icon' => 'home',      'count' => 98],
            ['slug' => 'health',        'name' => 'Health',         'icon' => 'heart',     'count' => 54],
        ];
    }

    /**
     * Verified operators for the public directory & profile pages.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function operators(): array
    {
        return [
            [
                'uuid' => '8f1c0a2e-6b1a-4d3e-9f10-1a2b3c4d5e01',
                'name' => 'Adwoa Couture',
                'owner' => 'Adwoa Mensah',
                'category' => 'Fashion',
                'category_slug' => 'fashion',
                'location' => 'Accra, Greater Accra',
                'rating' => 4.9,
                'reviews' => 342,
                'trust_score' => 96,
                'trust_level' => 'Trusted Operator',
                'verified' => true,
                'tagline' => 'Bespoke African-inspired fashion, tailored to fit.',
                'logo_bg' => 'from-rose-500 to-pink-600',
                'cover' => 'from-rose-400 via-pink-500 to-fuchsia-600',
                'tags' => ['Trending', 'Top Rated'],
                'joined' => 'Jan 2024',
                'hours' => 'Mon–Sat · 9:00 AM – 6:00 PM',
                'phone' => '+233 24 000 0001',
            ],
            [
                'uuid' => '8f1c0a2e-6b1a-4d3e-9f10-1a2b3c4d5e02',
                'name' => 'TechHub GH',
                'owner' => 'Kwame Asante',
                'category' => 'Electronics',
                'category_slug' => 'electronics',
                'location' => 'Kumasi, Ashanti',
                'rating' => 4.8,
                'reviews' => 521,
                'trust_score' => 94,
                'trust_level' => 'Trusted Operator',
                'verified' => true,
                'tagline' => 'Genuine gadgets with warranty. No knock-offs, ever.',
                'logo_bg' => 'from-sky-500 to-blue-600',
                'cover' => 'from-sky-400 via-blue-500 to-indigo-600',
                'tags' => ['Top Rated'],
                'joined' => 'Nov 2023',
                'hours' => 'Mon–Fri · 8:30 AM – 7:00 PM',
                'phone' => '+233 24 000 0002',
            ],
            [
                'uuid' => '8f1c0a2e-6b1a-4d3e-9f10-1a2b3c4d5e03',
                'name' => 'Glow Beauty Bar',
                'owner' => 'Ama Owusu',
                'category' => 'Beauty',
                'category_slug' => 'beauty',
                'location' => 'Tema, Greater Accra',
                'rating' => 4.7,
                'reviews' => 198,
                'trust_score' => 88,
                'trust_level' => 'Verified Operator',
                'verified' => true,
                'tagline' => 'Clean, cruelty-free beauty products and skincare.',
                'logo_bg' => 'from-amber-500 to-orange-600',
                'cover' => 'from-amber-400 via-orange-500 to-rose-500',
                'tags' => ['New'],
                'joined' => 'Mar 2025',
                'hours' => 'Tue–Sun · 10:00 AM – 8:00 PM',
                'phone' => '+233 24 000 0003',
            ],
            [
                'uuid' => '8f1c0a2e-6b1a-4d3e-9f10-1a2b3c4d5e04',
                'name' => 'Mama Yaa Kitchen',
                'owner' => 'Yaa Boateng',
                'category' => 'Food',
                'category_slug' => 'food',
                'location' => 'Takoradi, Western',
                'rating' => 5.0,
                'reviews' => 412,
                'trust_score' => 92,
                'trust_level' => 'Trusted Operator',
                'verified' => true,
                'tagline' => 'Authentic home-cooked Ghanaian meals, delivered hot.',
                'logo_bg' => 'from-emerald-500 to-green-600',
                'cover' => 'from-emerald-400 via-teal-500 to-green-600',
                'tags' => ['Trending'],
                'joined' => 'Aug 2024',
                'hours' => 'Daily · 11:00 AM – 9:00 PM',
                'phone' => '+233 24 000 0004',
            ],
            [
                'uuid' => '8f1c0a2e-6b1a-4d3e-9f10-1a2b3c4d5e05',
                'name' => 'AutoCare Pro',
                'owner' => 'Kofi Darko',
                'category' => 'Automotive',
                'category_slug' => 'automotive',
                'location' => 'Accra, Greater Accra',
                'rating' => 4.6,
                'reviews' => 87,
                'trust_score' => 79,
                'trust_level' => 'Verified Operator',
                'verified' => true,
                'tagline' => 'Trusted car servicing and genuine spare parts.',
                'logo_bg' => 'from-slate-600 to-gray-800',
                'cover' => 'from-slate-500 via-gray-600 to-zinc-700',
                'tags' => [],
                'joined' => 'Feb 2025',
                'hours' => 'Mon–Sat · 7:30 AM – 6:30 PM',
                'phone' => '+233 24 000 0005',
            ],
            [
                'uuid' => '8f1c0a2e-6b1a-4d3e-9f10-1a2b3c4d5e06',
                'name' => 'Nest Home & Living',
                'owner' => 'Efua Sarpong',
                'category' => 'Home & Living',
                'category_slug' => 'home-living',
                'location' => 'Cape Coast, Central',
                'rating' => 4.8,
                'reviews' => 156,
                'trust_score' => 86,
                'trust_level' => 'Verified Operator',
                'verified' => true,
                'tagline' => 'Curated home décor and furniture that lasts.',
                'logo_bg' => 'from-teal-500 to-cyan-600',
                'cover' => 'from-teal-400 via-cyan-500 to-sky-600',
                'tags' => ['New'],
                'joined' => 'Apr 2025',
                'hours' => 'Mon–Sat · 9:00 AM – 6:00 PM',
                'phone' => '+233 24 000 0006',
            ],
        ];
    }

    /**
     * Find a single operator by its UUID (mirrors route-model binding by uuid).
     *
     * @return array<string, mixed>|null
     */
    public static function operator(string $uuid): ?array
    {
        foreach (self::operators() as $operator) {
            if ($operator['uuid'] === $uuid) {
                return $operator;
            }
        }

        return null;
    }

    /**
     * Products/services shown on an operator profile.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function products(): array
    {
        return [
            ['uuid' => 'p1000000-0000-0000-0000-000000000001', 'name' => 'Custom Kente Blazer', 'price' => 850.00, 'image_bg' => 'from-rose-400 to-pink-500', 'tag' => 'Bestseller'],
            ['uuid' => 'p1000000-0000-0000-0000-000000000002', 'name' => 'Ankara Two-Piece Set',  'price' => 420.00, 'image_bg' => 'from-fuchsia-400 to-purple-500', 'tag' => null],
            ['uuid' => 'p1000000-0000-0000-0000-000000000003', 'name' => 'Tailored Agbada',       'price' => 1200.00, 'image_bg' => 'from-amber-400 to-orange-500', 'tag' => 'Premium'],
            ['uuid' => 'p1000000-0000-0000-0000-000000000004', 'name' => 'Beaded Accessory Set',  'price' => 180.00, 'image_bg' => 'from-emerald-400 to-teal-500', 'tag' => null],
        ];
    }

    /**
     * Reviews shown on operator profile.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function reviews(): array
    {
        return [
            ['author' => 'Nana Adjei',   'rating' => 5, 'date' => '2 days ago',  'body' => 'Paid through LYVO escrow and the funds were only released after I confirmed delivery. Outfit was perfect!'],
            ['author' => 'Linda O.',      'rating' => 5, 'date' => '1 week ago',  'body' => 'Verified operator, super responsive. The trust badge gave me confidence to order.'],
            ['author' => 'Samuel K.',     'rating' => 4, 'date' => '3 weeks ago', 'body' => 'Great quality, slight delay but the escrow protection kept me calm throughout.'],
        ];
    }

    /**
     * Escrow transactions (customer & operator dashboards).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function escrowTransactions(): array
    {
        return [
            ['uuid' => 'e1000000-0000-0000-0000-000000000001', 'ref' => 'LYV-ESC-10293', 'operator' => 'Adwoa Couture',    'item' => 'Custom Kente Blazer', 'amount' => 850.00, 'status' => 'Funds Held',     'status_key' => 'held',       'date' => 'Jun 14, 2026'],
            ['uuid' => 'e1000000-0000-0000-0000-000000000002', 'ref' => 'LYV-ESC-10288', 'operator' => 'TechHub GH',       'item' => 'Wireless Earbuds Pro', 'amount' => 540.00, 'status' => 'Seller Processing', 'status_key' => 'processing', 'date' => 'Jun 12, 2026'],
            ['uuid' => 'e1000000-0000-0000-0000-000000000003', 'ref' => 'LYV-ESC-10277', 'operator' => 'Mama Yaa Kitchen',  'item' => 'Weekly Meal Plan',     'amount' => 320.00, 'status' => 'Delivered',      'status_key' => 'delivered',  'date' => 'Jun 09, 2026'],
            ['uuid' => 'e1000000-0000-0000-0000-000000000004', 'ref' => 'LYV-ESC-10255', 'operator' => 'Glow Beauty Bar',   'item' => 'Skincare Bundle',      'amount' => 260.00, 'status' => 'Funds Released', 'status_key' => 'released',   'date' => 'Jun 02, 2026'],
        ];
    }

    /**
     * Escrow status pipeline used by the visual timeline.
     *
     * @return array<int, array<string, string>>
     */
    public static function escrowPipeline(): array
    {
        return [
            ['key' => 'initiated',  'label' => 'Payment Initiated',  'desc' => 'Customer starts a secure payment'],
            ['key' => 'held',       'label' => 'Funds Held Securely', 'desc' => 'LYVO holds funds in escrow'],
            ['key' => 'processing', 'label' => 'Seller Processing',  'desc' => 'Operator prepares the order'],
            ['key' => 'delivered',  'label' => 'Delivered',          'desc' => 'Operator marks as delivered'],
            ['key' => 'released',   'label' => 'Funds Released',     'desc' => 'Buyer confirms, funds released'],
        ];
    }

    /**
     * Verification steps for the operator onboarding tracker.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function verificationSteps(): array
    {
        return [
            ['key' => 'registration', 'label' => 'Registration Submitted',   'state' => 'done'],
            ['key' => 'ghana_card',   'label' => 'Ghana Card Uploaded',      'state' => 'done'],
            ['key' => 'video',        'label' => 'Video Verification',       'state' => 'done'],
            ['key' => 'review',       'label' => 'Admin Review',             'state' => 'current'],
            ['key' => 'verified',     'label' => 'Verified Operator',        'state' => 'pending'],
        ];
    }

    /**
     * Pending verification queue for the admin verification center.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function verificationQueue(): array
    {
        return [
            ['uuid' => 'v1000000-0000-0000-0000-000000000001', 'business' => 'Zuri Hair Studio', 'owner' => 'Akua Frimpong', 'category' => 'Beauty',      'submitted' => '2 hours ago',  'ghana_card' => true, 'video' => true,  'risk' => 'low'],
            ['uuid' => 'v1000000-0000-0000-0000-000000000002', 'business' => 'GadgetPlug',       'owner' => 'Yaw Mensah',     'category' => 'Electronics', 'submitted' => '5 hours ago',  'ghana_card' => true, 'video' => false, 'risk' => 'medium'],
            ['uuid' => 'v1000000-0000-0000-0000-000000000003', 'business' => 'Fresh Farms GH',   'owner' => 'Esi Quarcoo',    'category' => 'Food',        'submitted' => '1 day ago',    'ghana_card' => true, 'video' => true,  'risk' => 'low'],
            ['uuid' => 'v1000000-0000-0000-0000-000000000004', 'business' => 'QuickFix Repairs', 'owner' => 'Kojo Antwi',     'category' => 'Services',    'submitted' => '2 days ago',   'ghana_card' => false,'video' => false, 'risk' => 'high'],
        ];
    }

    /**
     * Trust score levels reference (badge colors & ranges).
     *
     * @return array<int, array<string, string>>
     */
    public static function trustLevels(): array
    {
        return [
            ['label' => 'Trusted Operator', 'range' => '90–100', 'color' => 'text-primary-600', 'dot' => 'bg-primary-500'],
            ['label' => 'Verified Operator','range' => '70–89',  'color' => 'text-sky-600',     'dot' => 'bg-sky-500'],
            ['label' => 'Growing Operator', 'range' => '50–69',  'color' => 'text-amber-600',   'dot' => 'bg-amber-500'],
            ['label' => 'Under Review',     'range' => '< 50',   'color' => 'text-rose-600',    'dot' => 'bg-rose-500'],
        ];
    }

    /**
     * KPI metrics for dashboards (customer / operator / admin).
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public static function metrics(): array
    {
        return [
            'customer' => [
                ['label' => 'Active Escrows',    'value' => '2',       'delta' => '+1 this week',  'icon' => 'shield'],
                ['label' => 'Saved Operators',   'value' => '12',      'delta' => '+3 this month', 'icon' => 'bookmark'],
                ['label' => 'Total Protected',   'value' => 'GH₵ 4.2k','delta' => 'Lifetime',      'icon' => 'lock'],
                ['label' => 'Reviews Posted',    'value' => '7',       'delta' => '+2 this month', 'icon' => 'star'],
            ],
            'operator' => [
                ['label' => 'Profile Views',     'value' => '1,284',   'delta' => '+18% this week','icon' => 'eye'],
                ['label' => 'Leads Received',    'value' => '96',      'delta' => '+12 this week', 'icon' => 'inbox'],
                ['label' => 'Funds in Escrow',   'value' => 'GH₵ 6.1k','delta' => '4 active',      'icon' => 'shield'],
                ['label' => 'Trust Score',       'value' => '96',      'delta' => 'Trusted',       'icon' => 'badge'],
            ],
            'admin' => [
                ['label' => 'Pending Reviews',   'value' => '24',      'delta' => '8 high priority','icon' => 'clipboard'],
                ['label' => 'Verified Operators','value' => '1,162',   'delta' => '+34 this week',  'icon' => 'badge'],
                ['label' => 'Escrow Volume',     'value' => 'GH₵ 482k','delta' => 'This month',     'icon' => 'shield'],
                ['label' => 'Open Disputes',     'value' => '6',       'delta' => '2 escalated',    'icon' => 'flag'],
            ],
        ];
    }
}
