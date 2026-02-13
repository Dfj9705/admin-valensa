<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('movimientos_productos', function (Blueprint $table) {
            $table->id('mop_id');

            $table->unsignedBigInteger('pro_id'); // FK productos

            // entrada | salida | ajuste | devolucion
            $table->string('mop_tipo', 20);

            // cantidad siempre positiva; el signo lo define mop_tipo
            $table->integer('mop_cantidad');

            // opcional: útil si quieres costo promedio / auditoría
            $table->decimal('mop_costo_unitario', 12, 2)->nullable();

            // Referencias opcionales (para relacionarlo a ventas, compras, etc.)
            $table->string('mop_referencia_tipo', 30)->nullable(); // ej: 'venta'
            $table->unsignedBigInteger('mop_referencia_id')->nullable(); // ej: ven_id

            $table->text('mop_observacion')->nullable();
            $table->timestamp('mop_fecha')->useCurrent();

            $table->timestamps();

            $table->foreign('pro_id')->references('pro_id')->on('productos')->cascadeOnDelete();

            $table->index(['pro_id', 'mop_fecha']);
            $table->index(['mop_tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_productos');
    }

};
