<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_time', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('user_task_id');
            $table->unsignedInteger('task_id');

            $table->foreign('user_task_id')->references('id')->on('user_task')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->datetime('date_start')->nullable();
            $table->datetime('date_stop')->nullable();
            $table->integer('time')->nullable();
            $table->string('section', 20)->notNullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('task_time');
    }
}
