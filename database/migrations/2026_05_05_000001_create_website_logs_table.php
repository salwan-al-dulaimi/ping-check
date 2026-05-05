<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->integer('response_time');
            $table->timestamp('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_logs');
    }
};
