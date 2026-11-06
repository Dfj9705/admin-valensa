<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movimientos_productos', function (Blueprint $table) {
            $table->foreignId('mop_created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_productos', function (Blueprint $table) {
            $table->dropForeign(['mop_created_by']);
            $table->dropColumn('mop_created_by');
        });
    }
};
