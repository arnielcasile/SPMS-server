<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMasterDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_data', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('warehouse_class');
            $table->string('delivery_form');
            $table->string('item_no');
            $table->string('item_rev');
            $table->integer('delivery_qty');
            $table->string('stock_address');
            $table->string('manufacturing_no');
            $table->date('delivery_inst_date');
            $table->string('destination_code');
            $table->string('item_name');
            $table->string('product_no');
            $table->string('ticket_no');
            $table->date('ticket_issue_date');
            $table->timestamps('ticket_issue_time');
            $table->string('storage_location');
            $table->date('delivery_due_date');
            $table->string('order_download_no');
            $table->integer('process_masterlist_id');
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
        Schema::dropIfExists('master_data');
    }
}
