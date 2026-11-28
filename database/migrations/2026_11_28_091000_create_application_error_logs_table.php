<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 20)->default('error');
            $table->string('source', 120);
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_error_logs');
    }
};