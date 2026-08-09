```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Remove existing CHECK constraints related to invoice_type.
         *
         * SQL Server does not support Laravel enum()->change()
         * because Laravel generates CHECK inline with ALTER COLUMN.
         */
        DB::statement("
            DECLARE @sql NVARCHAR(MAX) = '';

            SELECT @sql = @sql +
                'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) +
                '].[' + OBJECT_NAME(parent_object_id) +
                '] DROP CONSTRAINT [' + name + '];'
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('invoices')
              AND definition LIKE '%invoice_type%';

            IF @sql <> ''
                EXEC sp_executesql @sql;
        ");

        /*
         * Keep invoice_type as NVARCHAR on SQL Server.
         */
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_type', 255)
                ->default('sell')
                ->change();
        });

        /*
         * Recreate CHECK constraint with the new 'void' value.
         */
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_invoice_type_check_20260514
            CHECK (
                invoice_type IN (
                    'buy',
                    'sell',
                    'return_buy',
                    'return_sell',
                    'void'
                )
            )
        ");
    }

    public function down(): void
    {
        /*
         * Existing invoices with 'void' cannot remain
         * after removing 'void' from the allowed values.
         *
         * Convert them back to 'sell'.
         */
        DB::table('invoices')
            ->where('invoice_type', 'void')
            ->update([
                'invoice_type' => 'sell'
            ]);

        /*
         * Remove current CHECK constraint.
         */
        DB::statement("
            ALTER TABLE invoices
            DROP CONSTRAINT invoices_invoice_type_check_20260514
        ");

        /*
         * Keep invoice_type as NVARCHAR and restore
         * the previous allowed values.
         */
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_type', 255)
                ->default('sell')
                ->change();
        });

        /*
         * Recreate CHECK constraint without 'void'.
         */
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_invoice_type_check_rollback_20260514
            CHECK (
                invoice_type IN (
                    'buy',
                    'sell',
                    'return_buy',
                    'return_sell'
                )
            )
        ");
    }
};