<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function exportPdf(Laporan $laporan)
    {
        $jenis = $laporan->jenis_laporan;
        // Data sudah otomatis diubah ke array karena casting di model
        $data = $laporan->data_laporan;
        
        $view = match($jenis) {
            'transaksi' => 'pdf.laporan-transaksi',
            'barang' => 'pdf.laporan-barang',
            'barang_masuk' => 'pdf.laporan-barang-masuk',
            'barang_keluar' => 'pdf.laporan-barang-keluar',
            default => 'pdf.laporan-default'
        };
        
        // Gunakan facade PDF dengan benar
        $pdf = FacadePdf::loadView($view, [
            'laporan' => $laporan,
            'data' => $data,
        ]);
        
        // Tambahkan header dan footer (opsional)
        $pdf->setOption('header-html', view('pdf.header')->render());
        $pdf->setOption('footer-html', view('pdf.footer')->render());
        $pdf->setOption('margin-top', 30);
        $pdf->setOption('margin-bottom', 20);
        
        // Set ukuran kertas
        $pdf->setPaper('a4');
        
        return $pdf->download("laporan-{$laporan->kode_laporan}.pdf");
    }
}
