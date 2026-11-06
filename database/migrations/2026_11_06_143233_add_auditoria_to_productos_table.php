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
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('pro_created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('pro_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['pro_created_by']);
            $table->dropForeign(['pro_updated_by']);

            $table->dropColumn([
                'pro_created_by',
                'pro_updated_by',
            ]);
        });
    }
};
