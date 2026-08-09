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
         * Add auto overtime to attendance logs.
         */
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('auto_overtime')
                ->default(0)
                ->after('overtime');
        });

        /*
         * Add auto overtime to monthly attendances.
         */
        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('auto_overtime')
                ->default(0)
                ->after('overtime');
        });


        /*
         * SQL Server does not support Laravel enum()->change().
         *
         * Remove existing CHECK constraints related to system_code.
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
         * Keep system_code as NVARCHAR on SQL Server.
         */
        Schema::table('payroll_elements', function (Blueprint $table) {
            $table->string('system_code', 255)
                ->default('OTHER')
                ->change();
        });


        /*
         * Recreate CHECK constraint with AUTO_OVERTIME.
         */
        DB::statement("
            ALTER TABLE payroll_elements
            ADD CONSTRAINT payroll_elements_system_code_check_20260422
            CHECK (
                system_code IN (
                    'CHILD_ALLOWANCE',
                    'HOUSING_ALLOWANCE',
                    'FOOD_ALLOWANCE',
                    'MARRIAGE_ALLOWANCE',
                    'OVERTIME',
                    'AUTO_OVERTIME',
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

    public function down(): void
    {
        /*
         * Remove auto overtime from attendance logs.
         */
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn('auto_overtime');
        });

        /*
         * Remove auto overtime from monthly attendances.
         */
        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->dropColumn('auto_overtime');
        });


        /*
         * Remove current CHECK constraint.
         */
        DB::statement("
            ALTER TABLE payroll_elements
            DROP CONSTRAINT payroll_elements_system_code_check_20260422
        ");


        /*
         * Restore previous CHECK constraint without AUTO_OVERTIME.
         */
        DB::statement("
            ALTER TABLE payroll_elements
            ADD CONSTRAINT payroll_elements_system_code_check_rollback_20260422
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
};