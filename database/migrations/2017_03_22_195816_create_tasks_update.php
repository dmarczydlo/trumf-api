<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        Schema::table('tasks', function ($table) {
            $table->integer('order_number')->notNullable();
            $table->dateTime('date_add')->notNullable();
            $table->dateTime('date_order')->notNullable();
            $table->string('client', 10)->notNullable();
            $table->string('employee', 10)->Nullable();
            $table->boolean('done')->notNullable()->default(false);
            $table->integer('graphic_time')->notNullable();
            $table->integer('graver_time')->notNullable();
            $table->integer('min_lvl')->notNullable();
            $table->integer('softlab_id')->notNullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function ($table) {
            $table->dropColumn('order_number', 'date_add', 'date_order', 'client', 'employee', 'done', 'graphic_time', 'graver_time', 'min_lvl', 'softlab_id');
        });
    }
}
