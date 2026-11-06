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
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id('cue_id');

            $table->foreignId('ban_id')
                ->constrained('bancos', 'ban_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('cue_nombre', 100);
            $table->string('cue_numero', 50);
            $table->enum('cue_tipo', ['monetaria', 'ahorro']);
            $table->string('cue_moneda', 3)->default('GTQ');
            $table->boolean('cue_activa')->default(true);

            $table->unique(['ban_id', 'cue_numero']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
