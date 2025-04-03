<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faktur extends Model
{
    use HasFactory;

    protected $table = 'fakturs';

    protected $fillable = [
        'no_faktur',
        'transaksi_id',
        'tanggal_faktur',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_faktur' => 'date',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }
}
