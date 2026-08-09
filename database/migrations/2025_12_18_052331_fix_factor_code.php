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
        /*
         * products.code has a unique index on
         * (company_id, code).
         * SQL Server does not allow changing the column type
         * while the index depends on it.
         */

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


        /*
         * services.code also has a unique index on
         * (company_id, code).
         */

        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique('services_company_id_code_unique');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('code', 20)->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'code'],
                'services_company_id_code_unique'
            );
        });


        /*
         * Change invoice subtraction column.
         */
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtraction', 10, 2)
                ->default(0)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
         * Restore products.code to integer.
         */

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


        /*
         * Restore services.code to integer.
         */

        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique('services_company_id_code_unique');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->integer('code')->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'code'],
                'services_company_id_code_unique'
            );
        });
    }
};