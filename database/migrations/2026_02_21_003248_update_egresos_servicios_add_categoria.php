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
        Schema::table('egresos_servicios', function (Blueprint $table) {

            $table->dropColumn('egr_concepto');

            $table->foreignId('cat_id')
                ->after('egr_lugar')
                ->constrained('categorias_gastos', 'cat_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('egresos_servicios', function (Blueprint $table) {

            $table->dropForeign(['cat_id']);
            $table->dropColumn('cat_id');

            $table->string('egr_concepto', 200);
        });
    }
};
