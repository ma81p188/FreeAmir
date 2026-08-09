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
        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->renameColumn('mission_days', 'mission');
            $table->renameColumn('paid_leave_days', 'paid_leave');
            $table->renameColumn('unpaid_leave_days', 'unpaid_leave');

            $table->unsignedSmallInteger('undertime')
                ->default(0);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedSmallInteger('leave_remain')
                ->default(0)
                ->after('device_id')
                ->comment('Remaining annual leave balance (days)');
        });


        /*
         * SQL Server does not support Laravel enum()->change()
         * because Laravel generates CHECK inline with ALTER COLUMN.
         *
         * Remove existing CHECK constraints related to system_code
         * before changing the column.
         */
        DB::statement("
            DECLARE @sql NVARCHAR(MAX) = '';

            SELECT @sql = @sql +
                'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) +
                '].[' + OBJECT_NAME(parent_object_id) +
                '] DROP CONSTRAINT [' + name + '];'
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('payroll_elements')
              AND definition LIKE '%system_code%';

            IF @sql <> ''
                EXEC sp_executesql @sql;
        ");


        /*
         * Change enum to NVARCHAR.
         */
        Schema::table('payroll_elements', function (Blueprint $table) {
            $table->string('system_code', 255)
                ->default('OTHER')
                ->change();
        });


        /*
         * Recreate the enum restriction using CHECK CONSTRAINT.
         */
        DB::statement("
            ALTER TABLE payroll_elements
            ADD CONSTRAINT payroll_elements_system_code_check_20260317
            CHECK (
                system_code IN (
                    'CHILD_ALLOWANCE',
                    'HOUSING_ALLOWANCE',
                    'FOOD_ALLOWANCE',
                    'MARRIAGE_ALLOWANCE',
                    'OVERTIME',
                    'UNDERTIME',
                    'FRIDAY_PAY',
                    'HOLIDAY_PAY',
                    'MISSION_PAY',
                    'INSURANCE_EMP',
                    'INSURANCE_EMP2',
                    'UNEMPLOYMENT_INS',
                    'INCOME_TAX',
                    'ABSENCE_DEDUCTION',
                    'OTHER'
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
         * Remove CHECK constraint.
         */
        DB::statement("
            ALTER TABLE payroll_elements
            DROP CONSTRAINT payroll_elements_system_code_check_20260317
        ");


        /*
         * Restore system_code as NVARCHAR with the previous
         * allowed values.
         *
         * Note:
         * The original migration does not define the previous
         * enum values, so the current allowed values are kept.
         */
        Schema::table('payroll_elements', function (Blueprint $table) {
            $table->string('system_code', 255)
                ->default('OTHER')
                ->change();
        });


        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->renameColumn('mission', 'mission_days');
            $table->renameColumn('paid_leave', 'paid_leave_days');
            $table->renameColumn('unpaid_leave', 'unpaid_leave_days');

            $table->dropColumn('undertime');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('leave_remain');
        });
    }
};