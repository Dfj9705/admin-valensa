<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('ven_subtotal', 10, 2)->nullable()->change();
            $table->decimal('ven_tax', 10, 2)->nullable()->change();
            $table->decimal('ven_total', 10, 2)->nullable()->change();
            $table->datetime('ven_confirmed_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('ven_subtotal', 10, 2)->default(0)->change();
            $table->decimal('ven_tax', 10, 2)->default(0)->change();
            $table->decimal('ven_total', 10, 2)->default(0)->change();
            $table->datetime('ven_confirmed_at')->default(null)->change();
        });
    }
};
