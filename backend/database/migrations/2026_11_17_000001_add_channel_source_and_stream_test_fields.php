<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('source', 20)->default('custom');
            $table->text('tested_stream_url')->nullable();
            $table->timestamp('stream_tested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['source', 'tested_stream_url', 'stream_tested_at']);
        });
    }
};
