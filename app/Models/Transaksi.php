<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transaksis';

    protected $fillable = [
        'no_transaksi',
        'tanggal_transaksi',
        'member_id',
        'user_id',
        'items',
        'total_harga',
        'total_bayar',
        'kembalian',
        'metode_pembayaran',
        'status_pembayaran',
    ];
    
    // Pastikan items disimpan sebagai JSON
    protected $casts = [
        'items' => 'array',
        'tanggal_transaksi' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
    
    public function faktur()
    {
        return $this->hasOne(Faktur::class, 'transaksi_id');
    }
    
    public function barangKeluars()
    {
        return $this->hasMany(BarangKeluar::class);
    }
}
