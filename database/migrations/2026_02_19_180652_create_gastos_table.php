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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id('gas_id');

            $table->date('gas_fecha')->index();
            $table->enum('gas_tipo', ['compra', 'gasto'])->default('gasto')->index();

            $table->string('gas_descripcion', 255);
            $table->string('gas_referencia', 50)->nullable(); // No. factura/recibo

            $table->decimal('gas_monto', 12, 2);

            $table->foreignId('cat_id')
                ->constrained('categorias_gastos', 'cat_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
