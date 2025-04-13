<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BarangPdfController extends Controller
{
    public function previewPdf(Request $request)
    {
        // Ambil parameter dari request
        $kategoriId = $request->input('kategori_id');
        $selectedIds = $request->input('selected_ids', []);
        
        // Query barang berdasarkan parameter
        $query = Barang::with('kategori');
        
        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        } elseif (!empty($kategoriId)) {
            $query->where('kategori_id', $kategoriId);
        }
        
        $barang = $query->get();
        
        // Load view untuk PDF
        $pdf = Pdf::loadView('exports.barang', [
            'barang' => $barang,
            'tanggal' => now()->format('d/m/Y'),
        ]);
        
        // Return PDF dalam mode stream (untuk preview)
        return $pdf->stream('preview-barang.pdf');
    }
}
