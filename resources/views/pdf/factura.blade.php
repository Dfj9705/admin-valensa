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
    <div class="col" style="width:8%">
        <img src="{{ public_path('img/logo.jpeg') }}" style="width:100%">
    </div>

    <!-- Emisor -->
    <div class="col center" style="width:55%">
        <p><strong>{{ $venta->emisor->emi_nombre_emisor }}</strong></p>
        <p><strong>{{ $venta->emisor->emi_nombre_comercial }}</strong></p>
        <p>NIT: {{ $venta->emisor->emi_nit }}</p>
        <p>{{ $venta->emisor->emi_direccion }}</p>
    </div>
    <div class="col" style="width:35%">
        <div class="box center">
            <p>DOCUMENTO TRIBUTARIO</p>
            <p>ELECTRÓNICO</p>
            <p>
                <strong>
                    {{ $venta->ven_tipo_dte === 'FCAM' ? 'FACTURA CAMBIARIA' : 'FACTURA' }}
                </strong>
            </p>
            <br>
            <p>SERIE: {{ $venta->ven_fel_serie }}</p>
            <p>NUMERO: {{ $venta->ven_fel_numero }}</p>
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
            <p><strong>Fecha de emisión</strong></p>
            <p>{{ $venta->ven_fel_fecha_hora_emision }}</p>
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
                    @foreach ($venta->productos as $producto)
                        <tr>
                            <td>{{ $producto->qty }}</td>
                            <td>{{ $producto->description_snapshot }}</td>
                            <td>{{ number_format($producto->unit_price, 2) }}</td>
                            <td>{{ number_format($producto->discount, 2) }}</td>
                            <td>{{ number_format($producto->line_total, 2) }}</td>
                        </tr>
                        @php
                            $subtotal += $producto->line_total;
                            $totalDescuentos += $producto->discount;
                        @endphp
                    @endforeach
                    <tr>
                        <td colspan="4"><span style="font-weight: bold;">{{ $venta->emisor->emi_frase_texto }}</span>
                        </td>
                        <td>
                            <p style="font-weight: bold;">SUBTOTAL</p>
                            <p>{{ number_format($subtotal, 2) }}</p>
                            <p style="font-weight: bold;">TOTAL DESCUENTOS</p>
                            <p>-{{ number_format($totalDescuentos, 2) }}</p>
                            <p style="font-weight: bold;">TOTAL</p>
                            <p>{{ number_format($subtotal - $totalDescuentos, 2) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4"><span style="font-weight: bold;">RESUMEN DE IMPUESTOS</span></td>
                        <td>
                            <p style="font-weight: bold;">IVA 12%</p>
                            <p>{{ number_format($venta->ven_tax, 2) }}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


{{-- VENCIMIENTOS DE FACTURA CAMBIARIA --}}
@if ($venta->ven_tipo_dte === 'FCAM')
    <div class="row">
        <div class="col" style="width:98%">
            <div class="box">
                <p style="font-weight:bold; margin-bottom:10px;">
                    DETALLE DE ABONOS - FACTURA CAMBIARIA
                </p>

                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th style="width:20%;">No. de abono</th>
                            <th style="width:40%;">Fecha de vencimiento</th>
                            <th style="width:40%;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($venta->vencimientos as $vencimiento)
                            <tr>
                                <td>{{ $vencimiento->vve_numero }}</td>
                                <td>
                                    {{ $vencimiento->vve_fecha_vencimiento->format('d/m/Y') }}
                                </td>
                                <td>
                                    Q {{ number_format($vencimiento->vve_monto, 2) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <strong>TOTAL DE ABONOS</strong>
                            </td>
                            <td>
                                <strong>
                                    Q {{ number_format($venta->vencimientos->sum('vve_monto'), 2) }}
                                </strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif


<div class="row">
    <div class="col" style="width:98%">
        <div class="box">
            <p><span style="font-weight: bold;">No. de Autorización:</span> {{ $venta->ven_fel_uuid }}</p>
            <p><span style="font-weight: bold;">Certificador:</span> {{ $venta->ven_fel_nombre_certificador }}</p>
            <p><span style="font-weight: bold;">Nit:</span> {{ $venta->ven_fel_nit_certificador }}</p>
            <p><span style="font-weight: bold;">Fecha de certificación:</span>
                {{ $venta->ven_fel_fecha_hora_certificacion }}
            </p>
        </div>
    </div>
</div>


<div style="text-align: center; margin-top: 8px;">
    <img
        src="data:image/png;base64,{{ $venta->ven_fel_qr }}"
        style="width: 85px; height: 85px;"
        alt="Código QR FEL"
    >
</div>

