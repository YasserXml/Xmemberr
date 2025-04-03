<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangkeluar extends Model
{
    use HasFactory;

    protected $table = 'barangkeluars';

    protected $fillable = [
        'no_referensi',
        'barang_id',
        'jumlah_barang_keluar',
        'harga_jual',
        'total_harga',
        'tanggal_keluar',
        'transaksi_id',
        'user_id',
    ];

    protected $casts = [
        'tanggal_keluar' => 'date',
        'harga_jual' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
    
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
