<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feeder_logs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->string('staff_no')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->string('name')->nullable();
            $table->string('action')->nullable();
            $table->string('action_at')->nullable();
            $table->string('action_code')->nullable();
            $table->string('device')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feederlogs');
    }
};
