<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class ReportesController extends Controller
{
    public function productos(Request $request)
    {
        $tableQuery = app(\App\Filament\Resources\ProductoResource::class)
            ->getEloquentQuery();

        $products = $tableQuery->get();

        $html = view('pdf.productos', compact('products'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->SetTitle('Listado de Productos');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('productos.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}
