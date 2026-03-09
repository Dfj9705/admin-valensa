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
        <h1 style="text-align: center;">Catálogo de Productos Disponibles {{ config('app.name') }}</h1>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = 0;
                    $contador = 0;
                @endphp
                @foreach($products as $i => $p)
                    @if($p->pro_stock > 0)
                        @php
                            $total += $p->pro_stock * $p->pro_precio_venta_min;
                            $contador++;
                        @endphp
                        <tr>
                            <td>{{ $contador }}</td>
                            <td style="text-align: center;">
                                @if(isset($p->pro_imagenes[0]))
                                    <img src="{{ asset('storage/' . $p->pro_imagenes[0]) }}" alt="{{ $p->pro_nombre }}" width="70">
                                @else
                                    <span>No hay imagen</span>
                                @endif
                            </td>
                            <td>{{ $p->pro_nombre }}</td>
                            <td style="text-align: right;">Q. {{ number_format($p->pro_precio_venta_max, 2) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

    </body>

</html>