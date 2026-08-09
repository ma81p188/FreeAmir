```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->integer('code');
            $table->string('name', 60);
            $table->string('sstid')->nullable();
            $table->unsignedBigInteger('group')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->decimal('selling_price', 10, 2);
            $table->decimal('vat')->nullable();
            $table->string('description', 200)->nullable();

            $table->foreign('group')
                ->references('id')
                ->on('service_groups')
                ->noActionOnDelete();

            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->noActionOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->noActionOnDelete();

            $table->unique(['company_id', 'code']);

            $table->timestamps();
        });

        // SQL Server requires dropping the dependent unique index
        // before changing the type of products.code.
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_company_id_code_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('code')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'code'],
                'products_company_id_code_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_company_id_code_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('code', 20)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'code'],
                'products_company_id_code_unique'
            );
        });
    }
};