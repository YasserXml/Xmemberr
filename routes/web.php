<?php

use App\Http\Controllers\BarangPdfController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth:web'])->prefix('admin')->group(function () {
    Route::get('/transaksi/{transaksi}/invoice', [InvoiceController::class, 'show'])
        ->name('transaksi.invoice');
    
    Route::get('/transaksi/{transaksi}/invoice/download', [InvoiceController::class, 'download'])
        ->name('transaksi.invoice.download');

    Route::get('/transaksi/{transaksi}/invoice/print', [InvoiceController::class, 'print'])
        ->name('transaksi.invoice.print');

        Route::get('/transaksi/{transaksi}/download', [InvoiceController::class, 'download'])->name('transaksi.download');
});


Route::get('/barang/preview-pdf', [BarangPdfController::class, 'previewPdf'])->name('barang.preview-pdf');
