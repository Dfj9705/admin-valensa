<!doctype html>
<html>

    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: sans-serif;
                font-size: 11px;
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
                background: #f5f5f5;
                text-align: left;
            }

            .right {
                text-align: right;
            }

            .red {
                color: #b42318;
                font-weight: bold;
            }

            .muted {
                color: #666;
            }

            .logo {
                width: 60px;
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body>
        <img class="logo" src="{{ asset('img/logo.png') }}" alt="Logo">
        <h2>Reporte de Ventas - Saldos Pendientes</h2>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th class="right">Estado</th>
                    <th class="right">Total</th>
                    <th class="right">Pagado</th>
                    <th class="right">Pendiente</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $color = '';
                @endphp
                @foreach($ventas as $key => $v)
                    @php
                        if ($v['estado'] == 'confirmed') {
                            $color = 'blue';
                        } elseif ($v['estado'] == 'certified') {
                            $color = 'green';
                        } else {
                            $color = 'red';
                        }
                    @endphp
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $v['fecha'] }}</td>
                        <td>{{ $v['cliente'] }}</td>
                        <td class="right" style="color: {{ $color }}"> {{ $v['estado'] }}</td>
                        <td class="right">Q {{ number_format($v['total'], 2, '.', ',') }}</td>
                        <td class="right">Q {{ number_format($v['pagado'], 2, '.', ',') }}</td>
                        <td class="right red">Q {{ number_format($v['pendiente'], 2, '.', ',') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="right">Totales:</th>
                    <th class="right">Q {{ number_format($totTotal, 2, '.', ',') }}</th>
                    <th class="right">Q {{ number_format($totPagado, 2, '.', ',') }}</th>
                    <th class="right red">Q {{ number_format($totPendiente, 2, '.', ',') }}</th>
                </tr>
            </tfoot>
        </table>
    </body>

</html>