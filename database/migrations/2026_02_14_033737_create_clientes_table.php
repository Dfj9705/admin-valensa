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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('cli_id');

            // Nombre interno y nombre para FEL (si difiere)
            $table->string('cli_nombre', 150);
            $table->string('cli_nombre_fel', 150)->nullable();

            $table->string('cli_email', 150)->nullable();
            $table->string('cli_telefono', 50)->nullable();

            // Identificación
            $table->string('cli_nit', 20)->nullable()->index(); // aquí cabe "CF"
            $table->string('cli_cui', 20)->nullable()->index();

            // Dirección FEL
            $table->string('cli_direccion', 255)->nullable();
            $table->foreignId('cli_departamento_id')->nullable()
                ->constrained('departamentos', 'dep_id')->nullOnDelete();

            $table->foreignId('cli_municipio_id')->nullable()
                ->constrained('municipios', 'mun_id')->nullOnDelete();

            $table->boolean('cli_activo')->default(true);

            // Auditoría
            $table->foreignId('cli_creado_por')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->foreignId('cli_actualizado_por')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Índices/únicos (ojo con CF)
            $table->unique(['cli_cui']);
            $table->index(['cli_departamento_id', 'cli_municipio_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
