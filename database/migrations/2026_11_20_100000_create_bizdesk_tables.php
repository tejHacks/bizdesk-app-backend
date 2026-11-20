<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BizDesk schema. Money columns are integers in minor units (kobo/cents),
 * exactly like the BizDesk Flutter app, so totals never have rounding errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_name')->default('My Business')->after('name');
            $table->string('currency', 8)->default('₦')->after('business_name');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('sku', 40)->nullable();
            $table->string('category', 60)->default('General');
            $table->unsignedBigInteger('price');          // minor units
            $table->unsignedBigInteger('cost')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('low_stock_at')->default(5);
            $table->timestamps();

            $table->unique(['user_id', 'sku']);
            $table->index(['user_id', 'category']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('number');                    // per-business receipt number
            $table->string('payment_method', 20)->default('cash');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('cost_total')->default(0);
            $table->string('note')->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();

            $table->unique(['user_id', 'number']);
            $table->index(['user_id', 'sold_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);                  // copied at sale time
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('cost')->default(0);
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('total');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40);
            $table->unsignedBigInteger('amount');
            $table->string('note')->nullable();
            $table->date('spent_on');
            $table->timestamps();

            $table->index(['user_id', 'spent_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['business_name', 'currency']));
    }
};
