<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHumanlotClientsTable extends Migration
{
    public function up()
    {
        Schema::create('humanlot_clients', function (Blueprint $table) {
            $table->id();
            $table->string('app_id');
            $table->string('secret');
            $table->string('base_url');
            $table->string('status')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('humanlot_clients');
    }
}
