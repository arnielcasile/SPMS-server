<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDrMakingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dr_makings', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('dr_control');
            $table->integer('users_id');
            $table->integer('pcase');
            $table->integer('box');
            $table->integer('bag');
            $table->timestamps();
            $table->softdelete();
            $table->integer('pallet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dr_makings');
    }
}
