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

        'nama_barang',
        'kode_barang',
        'harga_jual',
        'stok_minimum',
        'satuan',
        'kategori_id',
        'tipe_transaksi',
    ];

    protected $casts = [
        'tanggal_masuk_barang' => 'date',
        'harga_beli' => 'integer',
        'total_harga' => 'integer',
    ];

    public function filterDataToSave(array $data)
    {
        return [
            'no_referensi' => $data['no_referensi'],
            'barang_id' => $data['barang_id'],
            'jumlah_barang_masuk' => $data['jumlah_barang_masuk'],
            'harga_beli' => $data['harga_beli'],
            'total_harga' => $data['total_harga'],
            'tanggal_masuk_barang' => $data['tanggal_masuk_barang'],
            'user_id' => $data['user_id'],
        ];
    }

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
