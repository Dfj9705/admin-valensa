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
        Schema::create('municipios', function (Blueprint $table) {
            $table->id('mun_id');
            $table->foreignId('dep_id')->constrained('departamentos', 'dep_id')->cascadeOnDelete();
            $table->string('mun_nombre', 120);
            $table->boolean('mun_estado')->default(true);
            $table->timestamps();

            $table->unique(['dep_id', 'mun_nombre']);
            $table->index(['dep_id', 'mun_estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
