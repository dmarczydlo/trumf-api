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
            $table->integer('task_id')->unsigned()->references('id')->inTable('tasks')->onDelete('CASCADE');
            $table->datetime('task_start')->nullable();
            $table->datetime('task_stop')->nullable();
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
