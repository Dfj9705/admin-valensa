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
        Schema::create('venta_productos', function (Blueprint $table) {

            $table->id();

            // Relación con venta
            $table->foreignId('ven_id')
                ->constrained('ventas', 'ven_id')
                ->cascadeOnDelete();

            // Relación directa con producto
            $table->foreignId('pro_id')
                ->constrained('productos', 'pro_id')
                ->restrictOnDelete();

            // Cantidad y precios
            $table->decimal('qty', 12, 3)->default(1.000);
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('discount', 12, 4)->default(0.0000);
            $table->decimal('line_total', 12, 2)->default(0.00);

            // Snapshot del producto (muy importante para histórico)
            $table->string('description_snapshot');
            $table->string('uom_snapshot', 20)->default('UNI');

            $table->json('meta')->nullable();

            $table->timestamps();

            // Índices recomendados
            $table->index('ven_id');
            $table->index('pro_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_productos');
    }
};
