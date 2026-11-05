
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Deudores</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 5px;
            text-align: center;
        }

        .subtitulo {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }

        .resumen {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .resumen td {
            width: 33.33%;
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .resumen .valor {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }

        table.detalle {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .detalle th {
            background: #243447;
            color: #fff;
            padding: 8px;
            text-align: left;
        }

        .detalle td {
            padding: 7px;
            border-bottom: 1px solid #ddd;
        }

        .numero {
            text-align: right;
        }

        .cliente {
            background: #e9eef4;
            padding: 9px;
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 8px;
        }

        .subtotal {
            background: #f3f4f6;
            font-weight: bold;
        }

        .total-general {
            background: #243447;
            color: white;
            padding: 12px;
            margin-top: 15px;
            text-align: right;
            font-size: 13px;
            font-weight: bold;
        }

        .pie {
            margin-top: 20px;
            font-size: 9px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>

    <h1>REPORTE DE DEUDORES</h1>

    <div class="subtitulo">
        Servicios Valensa
        <br>
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

    <table class="resumen">
        <tr>
            <td>
                Clientes deudores
                <div class="valor">{{ $totalDeudores }}</div>
            </td>

            <td>
                Ventas pendientes
                <div class="valor">{{ $totalVentas }}</div>
            </td>

            <td>
                Total por cobrar
                <div class="valor">
                    Q {{ number_format($totalPorCobrar, 2) }}
                </div>
            </td>
        </tr>
    </table>

    @forelse ($deudores as $deudor)

        <div class="cliente">
            {{ $deudor['cliente'] }}
            — {{ $deudor['cantidad_ventas'] }}
            venta(s) pendiente(s)
        </div>

        <table class="detalle">
            <thead>
                <tr>
                    <th>Venta #</th>
                    <th>Fecha</th>
                    <th class="numero">Total</th>
                    <th class="numero">Abonado</th>
                    <th class="numero">Saldo pendiente</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($deudor['ventas'] as $venta)
                    <tr>
                        <td>{{ $venta['id'] }}</td>
                        <td>{{ $venta['fecha'] }}</td>
                        <td class="numero">
                            Q {{ number_format($venta['total'], 2) }}
                        </td>
                        <td class="numero">
                            Q {{ number_format($venta['pagado'], 2) }}
                        </td>
                        <td class="numero">
                            Q {{ number_format($venta['saldo'], 2) }}
                        </td>
                    </tr>
                @endforeach

                <tr class="subtotal">
                    <td colspan="2">TOTAL CLIENTE</td>
                    <td class="numero">
                        Q {{ number_format($deudor['total'], 2) }}
                    </td>
                    <td class="numero">
                        Q {{ number_format($deudor['pagado'], 2) }}
                    </td>
                    <td class="numero">
                        Q {{ number_format($deudor['saldo'], 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

    @empty
        <p style="text-align:center; padding:20px;">
            No existen clientes con saldos pendientes.
        </p>
    @endforelse

    <div class="total-general">
        TOTAL GENERAL POR COBRAR:
        Q {{ number_format($totalPorCobrar, 2) }}
    </div>

    <div class="pie">
        Reporte generado automáticamente por el sistema.
    </div>

</body>
</html>
