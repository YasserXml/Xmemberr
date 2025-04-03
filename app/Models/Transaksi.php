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
        'member_id',
        'user_id',
        'tanggal_transaksi',
        'items',
        'total_harga',
        'diskon',
        'grand_total',
        'total_bayar',
        'kembalian',
        'status_pembayaran',
        'metode_pembayaran',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'items' => 'array',
        'total_harga' => 'integer',
        'diskon' => 'integer',
        'grand_total' => 'integer',
        'total_bayar' => 'integer',
        'kembalian' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function barangKeluars()
    {
        return $this->hasMany(BarangKeluar::class);
    }

    public function fakturs()
    {
        return $this->hasOne(Faktur::class);
    }
}
