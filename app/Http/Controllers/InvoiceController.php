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
        
        return view('invoices.show', compact('transaksi'));
    }
    
    public function download(Transaksi $transaksi)
    {
        $transaksi->load(['user', 'member', 'faktur']);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoice.pdf', compact('transaksi'));
        
        return $pdf->download('invoice-'.$transaksi->no_transaksi.'.pdf');
    }
}
