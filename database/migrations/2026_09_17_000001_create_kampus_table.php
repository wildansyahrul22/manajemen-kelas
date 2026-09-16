<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampus', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->timestamps();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('kampus_id')
                ->nullable()
                ->after('id')
                ->constrained('kampus')
                ->restrictOnDelete();
        });

        // Every kelas so far belongs to the first campus, so the column can be required.
        if (DB::table('kelas')->exists()) {
            $kampusId = DB::table('kampus')->insertGetId([
                'nama' => 'Universitas Indraprasta PGRI',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('kelas')->update(['kampus_id' => $kampusId]);
        }

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('kampus_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kampus_id');
        });

        Schema::dropIfExists('kampus');
    }
};
