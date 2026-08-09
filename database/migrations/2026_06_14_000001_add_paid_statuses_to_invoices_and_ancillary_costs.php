```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * ============================================================
         * INVOICES.STATUS
         * ============================================================
         */

        /*
         * Remove existing CHECK constraint for invoices.status.
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
         * Recreate CHECK constraint with partially_paid and paid.
         */
        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check_20260614
            CHECK (
                status IN (
                    'pending',
                    'pre_invoice',
                    'approved',
                    'unapproved',
                    'approved_inactive',
                    'rejected',
                    'ready_to_approve',
                    'partially_paid',
                    'paid'
                )
            )
        ");


        /*
         * ============================================================
         * ANCILLARY_COSTS.STATUS
         * ============================================================
         */

        /*
         * Remove existing CHECK constraint for ancillary_costs.status.
         */
        DB::statement("
            DECLARE @sql NVARCHAR(MAX) = '';

            SELECT @sql = @sql +
                'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) +
                '].[' + OBJECT_NAME(parent_object_id) +
                '] DROP CONSTRAINT [' + name + '];'
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('ancillary_costs')
              AND definition LIKE '%status%';

            IF @sql <> ''
                EXEC sp_executesql @sql;
        ");

        /*
         * Keep status as NVARCHAR on SQL Server.
         */
        Schema::table('ancillary_costs', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        /*
         * Recreate CHECK constraint with partially_paid and paid.
         */
        DB::statement("
            ALTER TABLE ancillary_costs
            ADD CONSTRAINT ancillary_costs_status_check_20260614
            CHECK (
                status IN (
                    'pending',
                    'approved',
                    'unapproved',
                    'approved_inactive',
                    'partially_paid',
                    'paid'
                )
            )
        ");
    }

    public function down(): void
    {
        /*
         * Convert new statuses back to approved
         * before removing them from the CHECK constraints.
         */
        DB::table('invoices')
            ->whereIn('status', ['partially_paid', 'paid'])
            ->update([
                'status' => 'approved'
            ]);

        DB::table('ancillary_costs')
            ->whereIn('status', ['partially_paid', 'paid'])
            ->update([
                'status' => 'approved'
            ]);


        /*
         * ============================================================
         * INVOICES.STATUS ROLLBACK
         * ============================================================
         */

        DB::statement("
            ALTER TABLE invoices
            DROP CONSTRAINT invoices_status_check_20260614
        ");

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT invoices_status_check_rollback_20260614
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


        /*
         * ============================================================
         * ANCILLARY_COSTS.STATUS ROLLBACK
         * ============================================================
         */

        DB::statement("
            ALTER TABLE ancillary_costs
            DROP CONSTRAINT ancillary_costs_status_check_20260614
        ");

        Schema::table('ancillary_costs', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('pending')
                ->change();
        });

        DB::statement("
            ALTER TABLE ancillary_costs
            ADD CONSTRAINT ancillary_costs_status_check_rollback_20260614
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