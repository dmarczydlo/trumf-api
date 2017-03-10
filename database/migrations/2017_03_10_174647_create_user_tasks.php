<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_task', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned()->references('id')->inTable('users')->onDelete('CASCADE');
            $table->integer('task_id')->unsigned()->references('id')->inTable('tasks')->onDelete('CASCADE');
            $table->datetime('task_start')->nullable();
            $table->datetime('task_stop')->nullable();
            $table->integer('status')->notNullable();
            $table->integer('accept')->default(0)->notNullable();
            $table->date('schedule_day')->notNullable();
            //graphic, graver
            $table->string('section', 20)->notNullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_task');
    }
}
