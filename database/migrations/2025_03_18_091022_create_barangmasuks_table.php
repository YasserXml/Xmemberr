<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barangmasuks', function (Blueprint $table) {
            $table->id();
            $table->string('no_referensi')->unique();
            $table->foreignId('barang_id')->constrained('barang');
            $table->integer('jumlah_barang_masuk');
            $table->integer('harga_beli');
            $table->integer('total_harga');
            $table->date('tanggal_masuk_barang')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('kategori_id')->nullable()->after('user_id')->constrained('kategoris')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangmasuks');
    }
};
