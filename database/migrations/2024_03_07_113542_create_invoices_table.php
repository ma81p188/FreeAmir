<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('date');
            $table->foreignId('creator_id')->nullable()->constrained('users')->noActionOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->noActionOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->noActionOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->noActionOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->noActionOnDelete();
            $table->decimal('addition', 16, 2);
            $table->decimal('subtraction', 16, 2);
            $table->decimal('vat', 16, 2);
            $table->decimal('cash_payment', 16, 2);
            $table->date('ship_date')->nullable();
            $table->string('ship_via', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_sell');
            $table->boolean('active')->default(0);
            $table->decimal('amount', 18, 2)->default(0);
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
        Schema::dropIfExists('invoices');
    }
}
