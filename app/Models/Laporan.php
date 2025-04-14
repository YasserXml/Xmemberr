<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Barryvdh\DomPDF\PDF;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Laporan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'laporans';

    protected $fillable = [
        'kode_laporan',
        'jenis_laporan',
        'tanggal_mulai',
        'tanggal_akhir',
        'periode',
        'data_laporan',
        'catatan',
        'user_id',
    ];

    protected $casts = [
        'data_laporan' => 'array',
        'tanggal_mulai' => 'date',
        'tanggal_akhir' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exportPdf()
{
    $jenis = $this->jenis_laporan;
    // Data sudah otomatis diubah ke array karena casting di model
    $data = $this->data_laporan;
    
    $view = match($jenis) {
        'transaksi' => 'pdf.laporan-transaksi',
        'barang' => 'pdf.laporan-barang',
        'barang_masuk' => 'pdf.laporan-barang-masuk',
        'barang_keluar' => 'pdf.laporan-barang-keluar',
        default => 'pdf.laporan-default'
    };
    
    // Gunakan facade PDF dengan benar
    $pdf = FacadePdf::loadView($view, [
        'laporan' => $this,
        'data' => $data,
    ]);
    
    return $pdf->download("laporan-{$this->kode_laporan}.pdf");
}
}