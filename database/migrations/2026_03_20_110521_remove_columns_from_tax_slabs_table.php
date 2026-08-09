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
         * Remove indexes before dropping their dependent columns.
         */
        Schema::table('tax_slabs', function (Blueprint $table) {
            $table->dropUnique('uq_tax_slab');
            $table->dropIndex('idx_tax_year');
            $table->dropColumn([
                'year',
                'slab_order',
                'income_from',
                'annual_exemption'
            ]);
        });

        /*
         * Add tax calculation fields to payrolls.
         */
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('tax_base_amount', 18, 2)
                ->default(0)
                ->after('total_deductions');

            $table->decimal('income_tax_amount', 18, 2)
                ->default(0)
                ->after('tax_base_amount');
        });


        /*
         * SQL Server does not support Laravel enum()->change().
         *
         * Remove existing CHECK constraints related to calc_type.
         */
        DB::statement("
            DECLARE @sql NVARCHAR(MAX) = '';

            SELECT @sql = @sql +
                'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) +
                '].[' + OBJECT_NAME(parent_object_id) +
                '] DROP CONSTRAINT [' + name + '];'
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('payroll_elements')
              AND definition LIKE '%calc_type%';

            IF @sql <> ''
                EXEC sp_executesql @sql;
        ");


        /*
         * Keep calc_type as NVARCHAR on SQL Server.
         */
        Schema::table('payroll_elements', function (Blueprint $table) {
            $table->string('calc_type', 255)
                ->change();
        });


        /*
         * Recreate the enum restriction using CHECK CONSTRAINT.
         */
        DB::statement("
            ALTER TABLE payroll_elements
            ADD CONSTRAINT payroll_elements_calc_type_check_20260320
            CHECK (
                calc_type IN (
                    'fixed',
                    'formula',
                    'percentage',
                    'daily'
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
         * Restore tax_slabs columns.
         */
        Schema::table('tax_slabs', function (Blueprint $table) {
            $table->smallInteger('year')
                ->unsigned()
                ->after('company_id');

            $table->tinyInteger('slab_order')
                ->unsigned()
                ->after('year');

            $table->decimal('income_from', 18, 2)
                ->after('slab_order');

            $table->decimal('annual_exemption', 18, 2)
                ->nullable()
                ->after('tax_rate');

            $table->unique(
                ['year', 'slab_order'],
                'uq_tax_slab'
            );

            $table->index(
                'year',
                'idx_tax_year'
            );
        });


        /*
         * Remove payroll tax calculation fields.
         */
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'tax_base_amount',
                'income_tax_amount'
            ]);
        });


        /*
         * Remove current calc_type CHECK constraint.
         */
        DB::statement("
            ALTER TABLE payroll_elements
            DROP CONSTRAINT payroll_elements_calc_type_check_20260320
        ");


        /*
         * Restore previous allowed values.
         */
        DB::statement("
            ALTER TABLE payroll_elements
            ADD CONSTRAINT payroll_elements_calc_type_check_rollback_20260320
            CHECK (
                calc_type IN (
                    'fixed',
                    'formula',
                    'percentage'
                )
            )
        ");
    }
};