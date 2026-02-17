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
        Schema::create('ventas', function (Blueprint $table) {

            $table->id('ven_id');

            // Relaciones
            $table->foreignId('ven_cliente_id')
                ->nullable()
                ->constrained('clientes', 'cli_id')
                ->nullOnDelete();

            // Estado de la venta
            $table->enum('ven_estado', [
                'draft',
                'confirmed',
                'cancelled',
                'certified'
            ])->default('draft');

            // Totales
            $table->decimal('ven_subtotal', 12, 2)->default(0.00);
            $table->decimal('ven_tax', 12, 2)->default(0.00);
            $table->decimal('ven_total', 12, 2)->default(0.00);

            $table->timestamp('ven_confirmed_at')->nullable();

            // Datos FEL
            $table->string('ven_fel_uuid', 80)->nullable();
            $table->string('ven_fel_serie', 50)->nullable();
            $table->string('ven_fel_numero', 50)->nullable();

            $table->enum('ven_fel_status', [
                'pending',
                'certified',
                'error',
                'void'
            ])->nullable();

            $table->dateTime('ven_fel_fecha_hora_emision')->nullable();
            $table->dateTime('ven_fel_fecha_hora_certificacion')->nullable();

            $table->string('ven_fel_nombre_receptor')->nullable();
            $table->string('ven_fel_estado_documento')->nullable();
            $table->string('ven_fel_nit_certificador')->nullable();
            $table->string('ven_fel_nombre_certificador')->nullable();

            $table->longText('ven_fel_qr')->nullable();

            $table->string('ven_fel_fecha_hora_anulacion')->nullable();
            $table->string('ven_fel_motivo_anulacion')->nullable();

            // Auditoría
            $table->foreignId('ven_created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('ven_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
