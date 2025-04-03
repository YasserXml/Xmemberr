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
        Schema::create('barangkeluars', function (Blueprint $table) {
            $table->id();
            $table->string('no_referensi')->unique();
            $table->integer('jumlah_barang_keluar');
            $table->integer('harga_jual');
            $table->integer('total_harga');
            $table->date('tanggal_keluar')->nullable();
            $table->foreignId('barang_id')->after('tanggal_keluar')->constrained('barangs');
            $table->foreignId('transaksi_id')->after('barang_id')->constrained('transaksis');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangkeluars');
    }
};
