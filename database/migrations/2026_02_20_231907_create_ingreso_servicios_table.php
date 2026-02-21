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
        Schema::create('ingresos_servicios', function (Blueprint $table) {
            $table->id('ing_id');

            $table->date('ing_fecha')->index();
            $table->string('ing_lugar', 150); // Lugar / Servicio
            $table->string('ing_observaciones', 255)->nullable();

            $table->decimal('ing_monto', 12, 2)->default(0);

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
        Schema::dropIfExists('ingresos_servicios');
    }
};
