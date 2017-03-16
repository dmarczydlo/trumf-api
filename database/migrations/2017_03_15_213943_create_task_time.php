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
            $table->integer('user_task_id')->unsigned()->references('id')->inTable('user_task')->onDelete('CASCADE');
            $table->integer('task_id')->unsigned()->references('id')->inTable('tasks')->onDelete('CASCADE');
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
