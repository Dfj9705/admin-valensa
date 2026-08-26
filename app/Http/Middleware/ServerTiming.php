<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        // Medir únicamente solicitudes de Livewire.
        if (!$request->is('livewire/update')) {
            return $next($request);
        }

        $startedAt = defined('LARAVEL_START')
            ? LARAVEL_START
            : microtime(true);
        $databaseMs = 0.0;
        $queryCount = 0;

        DB::listen(function (QueryExecuted $query) use (&$databaseMs, &$queryCount): void {
            $databaseMs += $query->time;
            $queryCount++;

            if ($query->time >= 300) {
                Log::warning('Consulta SQL lenta', [
                    'time_ms' => round($query->time, 2),
                    'sql' => $query->sql,
                    'path' => request()->path(),
                ]);
            }
        });

        $response = $next($request);

        $totalMs = (microtime(true) - $startedAt) * 1000;
        $otherMs = max(0, $totalMs - $databaseMs);

        $response->headers->set(
            'Server-Timing',
            sprintf(
                'laravel;dur=%.2f, db;dur=%.2f;desc="%d queries", other;dur=%.2f',
                $totalMs,
                $databaseMs,
                $queryCount,
                $otherMs,
            )
        );

        if ($totalMs >= 500) {
            Log::warning('Solicitud Livewire lenta', [
                'total_ms' => round($totalMs, 2),
                'database_ms' => round($databaseMs, 2),
                'other_ms' => round($otherMs, 2),
                'queries' => $queryCount,
                'livewire_method' => $request->input(
                    'components.0.calls.0.method'
                ),
                'updated_fields' => array_keys(
                    $request->input('components.0.updates', [])
                ),
            ]);
        }

        return $response;
    }
}
