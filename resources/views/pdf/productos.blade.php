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
                    <th>SKU</th>
                    <th>Stock</th>
                    <th>Costo</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $p->pro_nombre }}</td>
                        <td>{{ $p->pro_sku }}</td>
                        <td>{{ $p->pro_stock }}</td>
                        <td>{{ number_format($p->pro_precio_costo, 2) }}</td>
                        <td>{{ number_format($p->pro_precio_venta_min, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </body>

</html>