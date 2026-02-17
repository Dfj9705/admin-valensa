<style>
    * {
        box-sizing: border-box;
    }

    .row {
        width: 100%;
        clear: both;
        margin-bottom: 18px;
        margin-left: 8px;
    }

    .col {
        float: left;
        box-sizing: border-box;
    }

    .center {
        text-align: center;
    }

    .box {
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 18px;
        background-color: #fff;
    }

    p {
        margin: 0;
        line-height: 1.3;
        font-size: 12px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background-color: #000;
        color: #fff;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: center;
    }
</style>

<!-- ENCABEZADO -->
<div class="row">

    <!-- Logo -->
    <div class="col" style="width:15%">
        <img src="{{ public_path('/img/logo.png') }}" style="width:100%">
    </div>

    <!-- Emisor -->
    <div class="col" style="width:85%">
        <div class="box center">
            <p>RECIBO</p>
            <p><strong>VALENSA</strong></p>
            <br>
        </div>
    </div>
</div>


<!-- CLIENTE -->
<div class="row">
    <div class="col" style="width:63%">
        <div class="box">
            <p><strong>Nombre:</strong> {{ $venta->cliente->cli_nombre }}</p>
            <p><strong>NIT:</strong> {{ $venta->cliente->cli_nit }}</p>
            <p><strong>Dirección:</strong> {{ $venta->cliente->cli_direccion ?? 'CIUDAD' }}</p>
            <p><strong>Teléfono:</strong> {{ $venta->cliente->cli_telefono ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="col" style="width:35%">
        <div class="box center">
            <p><strong>Fecha de creación</strong></p>
            <p>{{ $venta->created_at->format('d/m/Y H:i:s') }}</p>
            <p><strong>Moneda</strong></p>
            <p>GTQ</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col" style="width:98%">
        <div class="box">
            <table style="width: 100%; text-align: center;">
                <thead>
                    <tr>
                        <th>Cantidad</th>
                        <th>Descripción</th>
                        <th>Precio Unitario</th>
                        <th>Descuento</th>
                        <th>SubTotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subtotal = 0;
                        $totalDescuentos = 0;
                    @endphp
                    @foreach ($venta->productos as $item)
                        <tr>
                            <td>{{ $item->qty }}</td>
                            <td>{{ $item->description_snapshot }}</td>
                            <td>Q. {{ number_format($item->unit_price, 2) }}</td>
                            <td>Q. {{ number_format($item->discount, 2) }}</td>
                            <td>Q. {{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @php
                            $subtotal += $item->line_total;
                            $totalDescuentos += $item->discount;
                        @endphp
                    @endforeach
                    <tr>
                        <td colspan="5" style="text-align: right;">
                            <p style="font-weight: bold;">SUBTOTAL</p>
                            <p>{{ number_format($subtotal, 2) }}</p>
                            <p style="font-weight: bold;">TOTAL DESCUENTOS</p>
                            <p>-{{ number_format($totalDescuentos, 2) }}</p>
                            <p style="font-weight: bold;">TOTAL</p>
                            <p>{{ number_format($subtotal - $totalDescuentos, 2) }}</p>
                        </td>
                    </tr>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>