<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePickingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('picking', function (Blueprint $table) {
            $table->Increments('id');
            $table->date('picking_date');
            $table->integer('picker_count');
            $table->string('area_code');
            $table->timestamps();
            $table->softdelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('picking');
    }
}
