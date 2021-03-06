<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('descript');
            $table->string('name');
            $table->string('img')->nullable();
            $table->string('reported_descript')->nullable();
            $table->longText('hunters')->nullable();
            $table->integer('budget');
            $table->integer('bonus')->nullable();
            $table->integer('category');
            $table->boolean('done')->nullable();
            $table->boolean('chosen')->default(0);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rewards');
    }
}
