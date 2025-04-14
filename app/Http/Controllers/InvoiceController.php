<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function show(Transaksi $transaksi)
    {
        // Load relasi yang diperlukan
        $transaksi->load(['user', 'member', 'faktur']);

        // Tambahkan parameter autoPrint untuk menandai bahwa invoice harus dicetak otomatis
        return view('invoices.show', compact('transaksi', 'autoPrint'));
    }

    public function print(Transaksi $transaksi)
    {
        $transaksi->load(['user', 'member', 'faktur']);
        $autoPrint = true;

        // Jangan generate PDF, langsung tampilkan view HTML
        return view('invoices.show', compact('transaksi', 'autoPrint'));
    }

    public function printFrame(Transaksi $transaksi)
    {
        return view('invoices.print-frame', compact('transaksi'));
    }

    public function download(Transaksi $transaksi)
    {
        $transaksi->load(['user', 'member', 'faktur']);
    
        // Format nama file: no_transaksi-tanggal.pdf
        $fileName = $transaksi->no_transaksi . '-' . $transaksi->tanggal_transaksi->format('Y-m-d') . '.pdf';
        
        // Use a dedicated PDF view template
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', [
            'transaksi' => $transaksi
        ])->setPaper('a4', 'portrait');
        
        // Use these options to improve rendering
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);
    
        return $pdf->download($fileName);
    }
}
