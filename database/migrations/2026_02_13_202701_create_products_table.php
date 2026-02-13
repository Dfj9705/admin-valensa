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
        Schema::create('productos', function (Blueprint $table) {
            $table->id('pro_id');

            // Básico
            $table->string('pro_nombre', 150);
            $table->string('pro_sku', 100)->nullable()->unique();
            $table->text('pro_descripcion')->nullable();

            // Inventario
            $table->integer('pro_stock')->default(0);

            // Precios
            $table->decimal('pro_precio_costo', 12, 2)->default(0);
            $table->decimal('pro_precio_venta_min', 12, 2)->default(0);
            $table->decimal('pro_precio_venta_max', 12, 2)->default(0);

            // Galería de imágenes (rutas en JSON)
            $table->json('pro_imagenes')->nullable();

            // Estado
            $table->boolean('pro_activo')->default(true);

            $table->timestamps();

            $table->index('pro_nombre');
            $table->index('pro_activo');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
