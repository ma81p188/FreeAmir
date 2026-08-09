<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubjectsTable extends Migration
{
    public function up()
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20);
            $table->string('name', 60);
            $table->unsignedBigInteger('parent_id')->nullable();

            $table->enum('type', [
                'debtor',
                'creditor',
                'both'
            ])->default('both');

            $table->timestamps();

            $table->foreignId('company_id')
                ->constrained()
                ->noActionOnDelete();

            $table->unique(['company_id', 'code']);

            $table->nullableMorphs('subjectable');

            $table->foreign('parent_id')
                ->references('id')
                ->on('subjects')
                ->noActionOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subjects');
    }
}