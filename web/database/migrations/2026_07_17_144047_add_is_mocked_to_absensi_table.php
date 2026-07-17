<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            // Flag lokasi palsu (Fake GPS) saat absen masuk & pulang.
            $table->boolean('is_mocked_masuk')->default(false)->after('lng_masuk');
            $table->boolean('is_mocked_pulang')->default(false)->after('lng_pulang');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['is_mocked_masuk', 'is_mocked_pulang']);
        });
    }
};