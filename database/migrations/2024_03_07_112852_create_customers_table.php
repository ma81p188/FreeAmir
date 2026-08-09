```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->string('phone', 15)->default('')->nullable();
            $table->string('cell', 15)->default('')->nullable();
            $table->string('fax', 15)->default('')->nullable();
            $table->string('address', 100)->default('')->nullable();
            $table->string('postal_code', 15)->default('')->nullable();
            $table->string('email', 64)->default('')->nullable();
            $table->string('ecnmcs_code', 20)->default('')->nullable();
            $table->string('personal_code', 15)->default('')->nullable();
            $table->string('web_page', 50)->default('')->nullable();
            $table->string('responsible', 50)->default('')->nullable();
            $table->string('connector', 50)->default('')->nullable();

            $table->unsignedBigInteger('group_id')->nullable();

            $table->text('desc')->nullable();

            $table->decimal('balance', 10, 2)
                ->default(0)
                ->nullable();

            $table->decimal('credit', 10, 2)
                ->default(0)
                ->nullable();

            $table->boolean('rep_via_email')
                ->default(false)
                ->nullable();

            $table->string('acc_name_1', 50)
                ->default('')
                ->nullable();

            $table->string('acc_no_1', 30)
                ->default('')
                ->nullable();

            $table->string('acc_bank_1', 50)
                ->default('')
                ->nullable();

            $table->string('acc_name_2', 50)
                ->default('')
                ->nullable();

            $table->string('acc_no_2', 30)
                ->default('')
                ->nullable();

            $table->string('acc_bank_2', 50)
                ->default('')
                ->nullable();

            $table->boolean('type_buyer')->default(false);
            $table->boolean('type_seller')->default(false);
            $table->boolean('type_mate')->default(false);
            $table->boolean('type_agent')->default(false);

            $table->unsignedBigInteger('introducer_id')->nullable();

            $table->string('commission', 15)
                ->default(0);

            $table->boolean('marked')
                ->default(false);

            $table->string('reason', 200)
                ->default('')
                ->nullable();

            $table->string('disc_rate', 15)
                ->default(0);

            $table->timestamps();

            // Customer Group relationship
            $table->foreign('group_id')
                ->references('id')
                ->on('customer_groups')
                ->noActionOnDelete();

            // Self-referencing relationship
            $table->foreign('introducer_id')
                ->references('id')
                ->on('customers')
                ->noActionOnDelete();

            // Company relationship
            $table->foreignId('company_id')
                ->constrained()
                ->noActionOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
