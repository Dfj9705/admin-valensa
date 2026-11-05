<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MySqlStockConcurrencyTest extends TestCase
{
    public function test_simultaneous_withdrawals_cannot_overdraw_stock(): void
    {
        if (getenv('VALENSA_TEST_MYSQL') !== '1') {
            $this->markTestSkipped('Set VALENSA_TEST_MYSQL=1 to test two real MySQL writers in a disposable database.');
        }
        $database = config('database.connections.mysql');
        self::assertContains($database['host'], ['localhost', '127.0.0.1', '::1'], 'This test only permits local MySQL.');
        $name = 'valensa_auth_test_'.bin2hex(random_bytes(8));
        $database['url'] = null;
        $database['database'] = null;
        config(['database.connections.stock_admin' => $database]);
        $admin = DB::connection('stock_admin');
        $created = false;
        $workers = [];
        try {
            $admin->statement("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $created = true;
            $database['database'] = $name;
            config(['database.default' => 'stock_test', 'database.connections.stock_test' => $database]);
            foreach (['2026_02_13_202701_create_products_table.php', '2026_02_13_224048_create_producto_movimientos_table.php'] as $migration) {
                (require database_path('migrations/'.$migration))->up();
            }
            $product = Producto::create(['pro_nombre' => 'Concurrent equipment']);
            $product->movimientos()->create(['mop_tipo' => 'entrada', 'mop_cantidad' => 5, 'mop_fecha' => now()]);
            for ($i = 0; $i < 2; $i++) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/stock-worker.php')], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                self::assertIsResource($process);
                stream_set_timeout($pipes[1], 20);
                fwrite($pipes[0], json_encode(['database' => $database, 'product' => $product->getKey()])."\n");
                fflush($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            foreach ($workers as [$process, $pipes]) {
                self::assertSame("READY\n", fgets($pipes[1]));
            }
            foreach ($workers as [$process, $pipes]) {
                fwrite($pipes[0], "GO\n");
                fflush($pipes[0]);
            }
            $results = [];
            foreach ($workers as [$process, $pipes]) {
                $results[] = trim(fgets($pipes[1]) ?: 'TIMEOUT');
            }
            sort($results);
            self::assertSame(['INSUFFICIENT', 'OK'], $results);
            self::assertSame(1, $product->fresh()->pro_stock);
            self::assertSame(2, $product->movimientos()->count());
        } finally {
            foreach ($workers as [$process, $pipes]) {
                if (proc_get_status($process)['running']) {
                    proc_terminate($process);
                }
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }
                proc_close($process);
            }
            DB::disconnect('stock_test');
            // Only the randomly named database created by this exact test can be removed.
            if ($created && preg_match('/^valensa_auth_test_[a-f0-9]{16}$/', $name)) {
                $admin->statement("DROP DATABASE `{$name}`");
            }
            DB::disconnect('stock_admin');
        }
    }
}
