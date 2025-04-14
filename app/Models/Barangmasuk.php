<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangmasuk extends Model
{
    use HasFactory;

    protected $table = 'barangmasuks';

    protected $fillable = [
        'no_referensi',
        'barang_id',
        'jumlah_barang_masuk',
        'harga_beli',
        'total_harga',
        'tanggal_masuk_barang',
        'user_id',
    ];

    protected $casts = [
        'tanggal_masuk_barang' => 'date',
        'harga_beli' => 'integer',
        'total_harga' => 'integer',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
}
