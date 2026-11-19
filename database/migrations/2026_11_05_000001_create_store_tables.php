<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('category');
            $table->string('unit', 40);
            $table->decimal('purchase_price', 14, 2);
            $table->decimal('selling_price', 14, 2);
            $table->unsignedInteger('current_stock')->default(0);
            $table->unsignedInteger('min_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['name', 'code']);
            $table->index(['is_active', 'current_stock']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('purchased_at')->index();
            $table->decimal('total_amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 24)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('sold_at')->index();
            $table->string('payment_method', 20);
            $table->decimal('total_amount', 14, 2);
            $table->decimal('amount_received', 14, 2);
            $table->decimal('change_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('product_code', 40);
            $table->string('unit', 40);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('sale_item_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('movement_type', 20);
            $table->integer('qty_change');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });

        Schema::create('receipt_counters', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->date('counter_date')->nullable();
            $table->unsignedInteger('last_sequence')->default(0);
        });

        DB::table('receipt_counters')->insert([
            'id' => 1,
            'counter_date' => null,
            'last_sequence' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_counters');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
    }
};
