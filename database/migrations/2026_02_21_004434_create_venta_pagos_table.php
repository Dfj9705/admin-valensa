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
        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id('vpa_id');
            $table->foreignId('ven_id')->constrained('ventas', 'ven_id')->cascadeOnDelete();

            $table->decimal('vpa_monto', 12, 2);
            $table->string('vpa_metodo', 30)->nullable();   // efectivo, transferencia, tarjeta
            $table->string('vpa_referencia', 100)->nullable();
            $table->timestamp('vpa_fecha')->useCurrent();

            // Auditoría (si la usas como en tu estilo)
            $table->foreignId('vpa_created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vpa_updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_pagos');
    }
};
