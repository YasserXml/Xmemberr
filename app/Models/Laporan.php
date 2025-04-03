<?php

namespace App\Models;

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
        'tanggal_mulai' => 'date',
        'tanggal_akhir' => 'date',
        'data_laporan' => 'array',
    ];

    /**
     * Get the user that created the report.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
