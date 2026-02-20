<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: sans-serif;
                font-size: 12px;
            }

            h1 {
                font-size: 16px;
                margin-bottom: 10px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #ccc;
                padding: 5px;
            }

            th {
                background: #f0f0f0;
            }
        </style>
    </head>

    <body>

        <img src="{{ asset('img/logo.png') }}" alt="Logo" width="100">
        <h1 style="text-align: center;">Listado de Productos Disponibles {{ config('app.name') }}</h1>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Stock</th>
                    <th>Costo</th>
                    <th>Precio Mínimo</th>
                    <th>Pendiente de generar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $p->pro_nombre }}</td>
                        <td>{{ $p->pro_stock }}</td>
                        <td style="text-align: right;">Q. {{ number_format($p->pro_precio_costo, 2) }}</td>
                        <td style="text-align: right;">Q. {{ number_format($p->pro_precio_venta_min, 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">
                            Q. {{ number_format($p->pro_stock * $p->pro_precio_venta_min, 2) }}
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold; font-size: 14px;">Total:</td>
                    <td style="text-align: right; font-weight: bold; font-size: 14px;">
                        Q. {{ number_format($products->sum('pro_stock') * $products->sum('pro_precio_venta_min'), 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

    </body>

</html>