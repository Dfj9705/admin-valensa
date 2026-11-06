<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cuentas_bancarias', function (Blueprint $table) {
            $table->decimal('cue_saldo_inicial', 15, 2)
                ->default(0)
                ->after('cue_moneda');

            $table->date('cue_fecha_inicio')
                ->nullable()
                ->after('cue_saldo_inicial');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_bancarias', function (Blueprint $table) {
            $table->dropColumn([
                'cue_saldo_inicial',
                'cue_fecha_inicio',
            ]);
        });
    }
};
