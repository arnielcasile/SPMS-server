<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('employee_number');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name');
            $table->string('photo');
            $table->string('email');
            $table->string('position');
            $table->string('status');
            $table->string('section');
            $table->string('section_code');
            $table->integer('area_id');
            $table->integer('user_type_id');
            $table->timestamps();
            $table->softdelete();
            $table->string('process');
            $table->integer('approver');
            $table->integer('support');
            $table->integer('receiver');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
