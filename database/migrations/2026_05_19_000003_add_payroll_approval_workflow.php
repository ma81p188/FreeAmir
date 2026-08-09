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
         * SQL Server does not support Laravel enum()->change().
         *
         * Remove existing CHECK constraints related to payrolls.status.
         */
        DB::statement("
            DECLARE @sql NVARCHAR(MAX) = '';

            SELECT @sql = @sql +
                'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) +
                '].[' + OBJECT_NAME(parent_object_id) +
                '] DROP CONSTRAINT [' + name + '];'
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('payrolls')
              AND definition LIKE '%status%';

            IF @sql <> ''
                EXEC sp_executesql @sql;
        ");

        /*
         * Keep status as NVARCHAR on SQL Server.
         */
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('draft')
                ->change();
        });

        /*
         * Add the new allowed status values.
         */
        DB::statement("
            ALTER TABLE payrolls
            ADD CONSTRAINT payrolls_status_check_20260519
            CHECK (
                status IN (
                    'draft',
                    'pending_manager_approval',
                    'approved',
                    'paid'
                )
            )
        ");

        /*
         * Create payroll status history table.
         */
        Schema::create('payroll_status_histories', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('payroll_id');

            $table->string('from_status', 50);

            $table->string('to_status', 50);

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->noActionOnDelete();

            $table->timestamp('changed_at')
                ->useCurrent();

            $table->text('note')
                ->nullable();

            $table->timestamps();

            $table->foreign('payroll_id')
                ->references('id')
                ->on('payrolls')
                ->noActionOnDelete();
        });
    }

    public function down(): void
    {
        /*
         * Drop history table first.
         */
        Schema::dropIfExists('payroll_status_histories');

        /*
         * Convert the new status back to draft
         * before removing it from the CHECK constraint.
         */
        DB::table('payrolls')
            ->where('status', 'pending_manager_approval')
            ->update([
                'status' => 'draft'
            ]);

        /*
         * Remove current CHECK constraint.
         */
        DB::statement("
            ALTER TABLE payrolls
            DROP CONSTRAINT payrolls_status_check_20260519
        ");

        /*
         * Keep status as NVARCHAR.
         */
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('draft')
                ->change();
        });

        /*
         * Restore previous allowed values.
         */
        DB::statement("
            ALTER TABLE payrolls
            ADD CONSTRAINT payrolls_status_check_rollback_20260519
            CHECK (
                status IN (
                    'draft',
                    'approved',
                    'paid'
                )
            )
        ");
    }
};