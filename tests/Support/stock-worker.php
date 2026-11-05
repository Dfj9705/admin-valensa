<?php

// Credentials travel over stdin, never command-line arguments or test output.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$input = json_decode(trim(fgets(STDIN)), true, 512, JSON_THROW_ON_ERROR);
if (! preg_match('/^valensa_auth_test_[a-f0-9]{16}$/', $input['database']['database'])) {
    exit(2);
}
config(['database.default' => 'stock_test', 'database.connections.stock_test' => $input['database']]);
echo "READY\n";
flush();
if (trim(fgets(STDIN)) !== 'GO') {
    exit(3);
}
try {
    \Illuminate\Support\Facades\DB::transaction(function () use ($input) {
        \App\Models\ProductoMovimiento::create([
            'pro_id' => $input['product'], 'mop_tipo' => 'salida', 'mop_cantidad' => 4, 'mop_fecha' => now(),
        ]);
        // Hold the successful insertion open while the other writer attempts its withdrawal.
        usleep(500000);
    });
    echo "OK\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "INSUFFICIENT\n";
} catch (\Throwable $e) {
    echo 'ERROR:'.get_class($e)."\n";
    exit(1);
}
