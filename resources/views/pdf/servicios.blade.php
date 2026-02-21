<!doctype html>
<html>

    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: sans-serif;
                font-size: 11px;
            }

            h2 {
                margin: 0 0 6px 0;
            }

            .muted {
                color: #666;
                margin-bottom: 10px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border-bottom: 1px solid #ddd;
                padding: 6px;
            }

            th {
                text-align: left;
                background: #f5f5f5;
            }

            .right {
                text-align: right;
            }

            .green {
                color: #0a7a2f;
                font-weight: bold;
            }

            .red {
                color: #b42318;
                font-weight: bold;
            }

            .totals {
                margin-top: 10px;
                font-size: 12px;
                text-align: right;
            }

            .logo {
                width: 60px;
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body>
        <img class="logo" src="{{ asset('img/logo.png') }}" alt="Logo">
        <h2>Saldo de Servicios</h2>
        <div class="muted">Rango: {{ $desde }} a {{ $hasta }}</div>

        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Detalle</th>
                    <th class="right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total_ingresos = 0;
                    $total_egresos = 0;
                @endphp
                @foreach($movimientos as $m)
                    <tr>
                        <td>{{ $m['fecha'] }}</td>
                        <td class="{{ $m['tipo'] === 'Ingreso' ? 'green' : 'red' }}">{{ $m['tipo'] }}</td>
                        <td>{{ $m['detalle'] }}</td>
                        <td class="right {{ $m['tipo'] === 'Ingreso' ? 'green' : 'red' }}">
                            {{ $m['tipo'] === 'Ingreso' ? '+' : '-' }} Q. {{ number_format($m['monto'], 2) }}
                        </td>
                    </tr>
                    @php
                        $total_ingresos += $m['tipo'] === 'Ingreso' ? $m['monto'] : 0;
                        $total_egresos += $m['tipo'] === 'Egreso' ? $m['monto'] : 0;
                    @endphp
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><b>Pagos servicios:</b> Q. {{ number_format($total_ingresos, 2) }}</div>
            <div><b>Gastos:</b> Q. {{ number_format($total_egresos, 2) }}</div>
            <div class="{{ $total_ingresos - $total_egresos >= 0 ? 'green' : 'red' }}"><b>Saldo servicios:</b>
                Q. {{ number_format($total_ingresos - $total_egresos, 2) }}</div>
        </div>
    </body>

</html>