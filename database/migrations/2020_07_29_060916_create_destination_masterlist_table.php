<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDestinationMasterlistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('destination_masterlist', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('payee_cd');
            $table->string('payee_name');
            $table->string('destination');
            $table->string('attention_to');
            $table->string('destination_class');
            $table->string('purpose');
            $table->timestamps();
            $table->softdelete();
            $table->integer('pdl');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('destination_masterlist');
    }
}
