<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategoris';

    protected $fillable = [
        'nama_kategori',
    ];

    /**
     * Get the products associated with the category.
     */
    public function barangs()
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }

    public function barangmasuks()
    {
        return $this->hasMany(Barangmasuk::class);
    }
}
