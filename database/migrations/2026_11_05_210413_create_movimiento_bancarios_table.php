<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('movimientos_bancarios', function (Blueprint $table) {
            $table->id('mov_id');

            $table->foreignId('cue_id')
                ->constrained('cuentas_bancarias', 'cue_id')
                ->restrictOnDelete();

            $table->date('mov_fecha');

            $table->enum('mov_tipo', [
                'ingreso',
                'egreso',
            ]);

            $table->string('mov_concepto', 150);

            $table->decimal('mov_monto', 15, 2);

            $table->string('mov_referencia', 100)->nullable();

            $table->foreignId('mov_created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['cue_id', 'mov_fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_bancarios');
    }
};
