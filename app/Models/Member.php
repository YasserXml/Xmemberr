<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'members';

    protected $fillable = [
        'kode_member',
        'nama_member',
        'email',
        'telepon',
        'alamat',
    ];

    /**
     * Get the transactions associated with the member.
     */
    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
}
