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
         * Remove existing CHECK constraints on invoices.status.
         *
         * SQL Server may have an existing CHECK constraint
         * created by a previous migration.
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
         * Change status to NVARCHAR.
         *
         * Do not use enum()->change() on SQL Server.
         */
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        /*
         * Add the new allowed values.
         */
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check_20260202
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
         * Remove the current CHECK constraint.
         */
        DB::statement("
            ALTER TABLE invoices
            DROP CONSTRAINT invoices_status_check_20260202
        ");

        /*
         * Restore the previous allowed values.
         */
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check_rollback
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
};