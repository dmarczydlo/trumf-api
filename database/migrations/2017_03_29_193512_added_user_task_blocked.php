<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedUserTaskBlocked extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        Schema::table('user_task', function ($table) {
            $table->boolean('graphic_block')->default(false);
            $table->boolean('graver_block')->default(false);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('user_task', function ($table) {
            $table->dropColumn('graphic_block','graver_block');
        });
    }
}
