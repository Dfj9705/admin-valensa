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
        Schema::create('egresos_servicios', function (Blueprint $table) {
            $table->id('egr_id');

            $table->date('egr_fecha')->index();
            $table->string('egr_lugar', 150); // lugar / servicio / proveedor corto
            $table->string('egr_concepto', 200); // “Pago técnico”, “Gasolina”, etc.
            $table->string('egr_observaciones', 255)->nullable();

            $table->decimal('egr_monto', 12, 2)->default(0);

            // Opcionales útiles (podés quitar si querés ultra simple)
            $table->string('egr_metodo_pago', 30)->nullable(); // efectivo, transferencia, tarjeta
            $table->string('egr_referencia', 80)->nullable();  // no. boleta, no. transferencia

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egresos_servicios');
    }
};
