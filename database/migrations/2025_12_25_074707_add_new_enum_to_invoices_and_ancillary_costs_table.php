```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
         * Invoices
         *
         * SQL Server does not support Laravel enum()->change()
         * correctly. Use NVARCHAR + CHECK CONSTRAINT instead.
         */

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check
            CHECK (
                status IN (
                    'pending',
                    'approved',
                    'unapproved',
                    'approved_inactive'
                )
            )
        ");


        /*
         * Ancillary Costs
         */

        Schema::table('ancillary_costs', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        DB::statement("
            ALTER TABLE ancillary_costs
            ADD CONSTRAINT ancillary_costs_status_check
            CHECK (
                status IN (
                    'pending',
                    'approved',
                    'unapproved',
                    'approved_inactive'
                )
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
         * Remove CHECK constraints first.
         */

        DB::statement("
            ALTER TABLE invoices
            DROP CONSTRAINT invoices_status_check
        ");

        DB::statement("
            ALTER TABLE ancillary_costs
            DROP CONSTRAINT ancillary_costs_status_check
        ");


        /*
         * Restore previous allowed values.
         *
         * The column remains NVARCHAR because SQL Server
         * does not use Laravel ENUM here.
         */

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        Schema::table('ancillary_costs', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        /*
         * Add previous CHECK constraints.
         */

        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check
            CHECK (
                status IN (
                    'pending',
                    'approved',
                    'unapproved'
                )
            )
        ");

        DB::statement("
            ALTER TABLE ancillary_costs
            ADD CONSTRAINT ancillary_costs_status_check
            CHECK (
                status IN (
                    'pending',
                    'approved',
                    'unapproved'
                )
            )
        ");
    }
};