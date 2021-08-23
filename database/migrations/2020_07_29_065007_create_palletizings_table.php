<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePalletizingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('palletizings', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('ticket_no');
            $table->integer('users_id');
            $table->string('dr_control');
            $table->integer('delivery_type_id');
            $table->integer('delivery_no');
            $table->timestamps();
            $table->string('process');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('palletizings');
    }
}
