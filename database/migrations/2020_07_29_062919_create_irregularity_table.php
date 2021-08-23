<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIrregularityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('irregularity', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('ticket_no');
            $table->string('control_no');
            $table->integer('users_id');
            $table->string('irregularity_type');
            $table->string('actual_qty');
            $table->string('discrepancy');
            $table->string('remarks');
            $table->integer('process_masterlists_id');
            $table->timestamps();
            $table->softdelete();
            $table->string('dr_control_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('irregularity');
    }
}
