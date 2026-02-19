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
        Schema::create('emisores', function (Blueprint $table) {
            $table->id('emi_id');

            // Datos del emisor (SAT)
            $table->string('emi_nit', 20)->index();                 // 84106786
            $table->string('emi_nombre_emisor', 150);              // MANUEL FRANCISCO , SANTOS VALENZUELA
            $table->unsignedInteger('emi_codigo_establecimiento'); // 1 o 2
            $table->string('emi_nombre_comercial', 150);           // SERVICIOS VALENSA / VALENSA
            $table->string('emi_correo_emisor', 150)->nullable();  // opcional
            $table->string('emi_afiliacion_iva', 10)->default('GEN'); // PEQ / GEN

            // Dirección (única por NIT en tu caso)
            $table->string('emi_direccion', 255);
            $table->string('emi_codigo_postal', 10)->nullable();
            $table->string('emi_municipio', 100);
            $table->string('emi_departamento', 100);
            $table->string('emi_pais', 2)->default('GT');

            // Frases (siempre es 3/1 para este emisor, lo dejás configurable)
            $table->unsignedTinyInteger('emi_frase_tipo')->default(3);
            $table->unsignedTinyInteger('emi_frase_escenario')->default(1);
            $table->string('emi_frase_texto', 150)->default('NO GENERA DERECHO A CRÉDITO FISCAL');

            // Tekra (por emisor)
            $table->string('emi_tekra_usuario', 100);
            $table->string('emi_tekra_clave', 150);
            $table->string('emi_tekra_cliente', 100);
            $table->string('emi_tekra_contrato', 100);

            $table->boolean('emi_activo')->default(true);

            // Auditoría (si ya la usas)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Evitar duplicados: un NIT con un código de establecimiento único
            $table->unique(['emi_nit', 'emi_codigo_establecimiento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emisores');
    }
};
