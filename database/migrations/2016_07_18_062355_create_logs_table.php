<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('from_ip')->index();
            $table->bigInteger('to_ip')->index();
            $table->bigInteger('port')->index();
            $table->bigInteger('protocol')->index();
            $table->bigInteger('from_8_subnet')->index();
            $table->bigInteger('from_16_subnet')->index();
            $table->bigInteger('from_24_subnet')->index();
            $table->bigInteger('to_8_subnet')->index();
            $table->bigInteger('to_16_subnet')->index();
            $table->bigInteger('to_24_subnet')->index();
            $table->bigInteger('hits')->nullable()->default(null);
            $table->bigInteger('parent')->nullable()->default(null);
            $table->tinyInteger('tolerance')->nullable()->default(null)->index();
            $table->Integer('weight');
            $table->Float('calculated_level');

            $table->index(['from_ip', 'to_ip', 'port', 'protocol', 'from_8_subnet', 'from_16_subnet', 'from_24_subnet', 'to_8_subnet', 'to_16_subnet', 'to_24_subnet'], 'sll');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('logs');
    }
}
