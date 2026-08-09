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
         * Remove existing CHECK constraints related to invoices.status.
         * SQL Server does not support Laravel enum()->change()
         * because it generates CHECK inline with ALTER COLUMN.
         */
        DB::statement("
            DECLARE @sql NVARCHAR(MAX) = '';

            SELECT @sql = @sql +
                'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) +
                '].[' + OBJECT_NAME(parent_object_id) +
                '] DROP CONSTRAINT [' + name + '];'
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('invoices')
              AND definition LIKE '%status%';

            IF @sql <> ''
                EXEC sp_executesql @sql;
        ");

        /*
         * Keep status as NVARCHAR on SQL Server.
         */
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        /*
         * Recreate the CHECK constraint.
         */
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check_20260214
            CHECK (
                status IN (
                    'pending',
                    'pre_invoice',
                    'approved',
                    'unapproved',
                    'approved_inactive',
                    'rejected',
                    'ready_to_approve'
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
         * Existing records with 'pending' cannot remain
         * if the value is removed from the CHECK constraint.
         */
        DB::table('invoices')
            ->where('status', 'pending')
            ->update([
                'status' => 'pre_invoice'
            ]);

        /*
         * Remove current CHECK constraints.
         */
        DB::statement("
            DECLARE @sql NVARCHAR(MAX) = '';

            SELECT @sql = @sql +
                'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) +
                '].[' + OBJECT_NAME(parent_object_id) +
                '] DROP CONSTRAINT [' + name + '];'
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('invoices')
              AND definition LIKE '%status%';

            IF @sql <> ''
                EXEC sp_executesql @sql;
        ");

        /*
         * Keep status as NVARCHAR and change default to pre_invoice.
         */
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pre_invoice')
                ->change();
        });

        /*
         * Recreate CHECK constraint without 'pending'.
         */
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check_rollback_20260214
            CHECK (
                status IN (
                    'pre_invoice',
                    'approved',
                    'unapproved',
                    'approved_inactive',
                    'rejected',
                    'ready_to_approve'
                )
            )
        ");
    }
};