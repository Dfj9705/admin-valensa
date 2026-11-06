
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: sans-serif;
            font-size: 9px;
            color: #333;
        }

        h2 {
            text-align: center;
            margin-bottom: 5px;
        }

        h3 {
            margin-top: 20px;
        }

        .periodo {
            text-align: center;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background: #eeeeee;
        }

        .right {
            text-align: right;
        }

        .total {
            background: #eeeeee;
            font-weight: bold;
        }

        .saldo {
            font-weight: bold;
        }

        .nota {
            font-size: 8px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<h2>Reporte de saldos bancarios</h2>

<div class="periodo">
    Del {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
    al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
</div>

@foreach($cuentas->groupBy('cue_moneda') as $moneda => $grupo)

    <h3>Moneda: {{ $moneda }}</h3>

    <table>
        <thead>
            <tr>
                <th>Banco / Cuenta</th>
                <th class="right">Saldo inicial</th>
                <th class="right">Ventas (+)</th>
                <th class="right">Otros ingresos (+)</th>
                <th class="right">Gastos (-)</th>
                <th class="right">Otros egresos (-)</th>
                <th class="right">Saldo final</th>
            </tr>
        </thead>

        <tbody>
            @foreach($grupo as $cuenta)
                <tr>
                    <td>
                        <strong>{{ $cuenta->banco?->ban_nombre }}</strong>
                        <br>
                        {{ $cuenta->cue_nombre }}
                        <br>
                        {{ $cuenta->cue_numero }}
                    </td>

                    @if($cuenta->reporte_disponible)
                        <td class="right">
                            {{ number_format($cuenta->saldo_inicial_periodo, 2) }}
                        </td>

                        <td class="right">
                            {{ number_format($cuenta->ingresos_ventas, 2) }}
                        </td>

                        <td class="right">
                            {{ number_format($cuenta->otros_ingresos, 2) }}
                        </td>

                        <td class="right">
                            {{ number_format($cuenta->egresos_gastos, 2) }}
                        </td>

                        <td class="right">
                            {{ number_format($cuenta->otros_egresos, 2) }}
                        </td>

                        <td class="right saldo">
                            {{ number_format($cuenta->saldo_final, 2) }}
                        </td>
                    @else
                        <td colspan="6">
                            Cuenta sin fecha de inicio configurada.
                        </td>
                    @endif
                </tr>
            @endforeach

            <tr class="total">
                <td>TOTAL {{ $moneda }}</td>

                <td class="right">
                    {{ number_format($grupo->sum('saldo_inicial_periodo'), 2) }}
                </td>

                <td class="right">
                    {{ number_format($grupo->sum('ingresos_ventas'), 2) }}
                </td>

                <td class="right">
                    {{ number_format($grupo->sum('otros_ingresos'), 2) }}
                </td>

                <td class="right">
                    {{ number_format($grupo->sum('egresos_gastos'), 2) }}
                </td>

                <td class="right">
                    {{ number_format($grupo->sum('otros_egresos'), 2) }}
                </td>

                <td class="right">
                    {{ number_format($grupo->sum('saldo_final'), 2) }}
                </td>
            </tr>
        </tbody>
    </table>

@endforeach

<p class="nota">
    Los saldos se calculan a partir de la fecha de inicio
    contable de cada cuenta bancaria.
    Las cuentas sin fecha de inicio no se incluyen en los totales.
</p>

</body>
</html>
