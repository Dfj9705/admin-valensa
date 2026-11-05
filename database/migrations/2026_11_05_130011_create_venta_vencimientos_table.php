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
        Schema::create('venta_vencimientos', function (Blueprint $table) {
            $table->id('vve_id');

            $table->foreignId('ven_id')
                ->constrained('ventas', 'ven_id')
                ->restrictOnDelete();

            $table->unsignedInteger('vve_numero');
            $table->date('vve_fecha_vencimiento');
            $table->decimal('vve_monto', 14, 2);

            $table->timestamps();

            $table->unique(['ven_id', 'vve_numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_vencimientos');
    }
};
