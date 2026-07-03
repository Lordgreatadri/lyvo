<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * products
 * --------
 * Catalogue items an approved operator lists for sale. Item images live in the
 * Spatie media library (collection "images"), not here.
 *
 * Promotion boosting is denormalised onto this row (`boost_weight`,
 * `boosted_until`, `is_featured`) so the hot public "top of home" query is a
 * single index-ordered read (ORDER BY boost_weight DESC, published_at DESC) with
 * no join to the promotion tables. The promotion tables (Phase 2B) remain the
 * source of truth and keep these columns in sync.
 *
 * Indexing targets the queries the store actually runs: public listing filters
 * by status + published_at and orders by boost/recency; operators read their own
 * catalogue by operator_profile_id; category pages and analytics group by
 * business_category_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('operator_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('GHS');

            // NULL quantity = a service / unlimited stock; 0 = sold out.
            $table->unsignedInteger('quantity')->nullable();

            $table->string('status', 16)->default('draft');

            // Denormalised promotion boost (kept in sync by the promotion domain).
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('boost_weight')->default(0);
            $table->timestamp('boosted_until')->nullable();

            // Lightweight popularity + sales counters (incremented, not joined).
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('sold_count')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // An operator's slugs are unique within their own catalogue.
            $table->unique(['operator_profile_id', 'slug']);

            // Public store: visible items, ordered by boost then recency.
            $table->index(['status', 'published_at']);
            $table->index(['boost_weight', 'published_at']);
            $table->index('business_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
