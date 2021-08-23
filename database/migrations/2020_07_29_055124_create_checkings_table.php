<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checkings', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('ticket_no');
            $table->integer('users_id');
            $table->timestamps();
            $table->softdelete();
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
        Schema::dropIfExists('checkings');
    }
}
