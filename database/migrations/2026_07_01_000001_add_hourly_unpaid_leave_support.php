```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERSONNEL_REQUEST_TYPES = [
        'LEAVE_HOURLY',
        'LEAVE_DAILY',
        'SICK_LEAVE',
        'LEAVE_WITHOUT_PAY',
        'LEAVE_WITHOUT_PAY_HOURLY',
        'MISSION_HOURLY',
        'MISSION_DAILY',
        'OVERTIME_ORDER',
        'REMOTE_WORK',
        'OTHER',
    ];

    private const PREVIOUS_REQUEST_TYPES = [
        'LEAVE_HOURLY',
        'LEAVE_DAILY',
        'SICK_LEAVE',
        'LEAVE_WITHOUT_PAY',
        'MISSION_HOURLY',
        'MISSION_DAILY',
        'OVERTIME_ORDER',
        'REMOTE_WORK',
        'OTHER',
    ];

    public function up(): void
    {
        /*
         * SQL Server does not support Laravel enum()->change().
         *
         * Remove existing CHECK constraints related to request_type.
         */
        DB::statement("
            DECLARE @sql NVARCHAR(MAX) = '';

            SELECT @sql = @sql +
                'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) +
                '].[' + OBJECT_NAME(parent_object_id) +
                '] DROP CONSTRAINT [' + name + '];'
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('personnel_requests')
              AND definition LIKE '%request_type%';

            IF @sql <> ''
                EXEC sp_executesql @sql;
        ");

        /*
         * Keep request_type as NVARCHAR on SQL Server.
         */
        Schema::table('personnel_requests', function (Blueprint $table) {
            $table->string('request_type', 255)
                ->change();
        });

        /*
         * Recreate CHECK constraint with LEAVE_WITHOUT_PAY_HOURLY.
         */
        $types = implode(
            ', ',
            array_map(
                fn ($type) => "'" . $type . "'",
                self::PERSONNEL_REQUEST_TYPES
            )
        );

        DB::statement("
            ALTER TABLE personnel_requests
            ADD CONSTRAINT personnel_requests_request_type_check_20260701
            CHECK (
                request_type IN ($types)
            )
        ");

        /*
         * Add hourly unpaid leave support.
         */
        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->unsignedTinyInteger('unpaid_leave_days')
                ->default(0)
                ->after('unpaid_leave');
        });
    }

    public function down(): void
    {
        /*
         * Remove hourly unpaid leave support.
         */
        Schema::table('monthly_attendances', function (Blueprint $table) {
            $table->dropColumn('unpaid_leave_days');
        });

        /*
         * Convert the new value to the previous value
         * before removing it from the CHECK constraint.
         */
        DB::table('personnel_requests')
            ->where('request_type', 'LEAVE_WITHOUT_PAY_HOURLY')
            ->update([
                'request_type' => 'LEAVE_WITHOUT_PAY'
            ]);

        /*
         * Remove current CHECK constraint.
         */
        DB::statement("
            ALTER TABLE personnel_requests
            DROP CONSTRAINT personnel_requests_request_type_check_20260701
        ");

        /*
         * Keep request_type as NVARCHAR.
         */
        Schema::table('personnel_requests', function (Blueprint $table) {
            $table->string('request_type', 255)
                ->change();
        });

        /*
         * Restore previous allowed values.
         */
        $types = implode(
            ', ',
            array_map(
                fn ($type) => "'" . $type . "'",
                self::PREVIOUS_REQUEST_TYPES
            )
        );

        DB::statement("
            ALTER TABLE personnel_requests
            ADD CONSTRAINT personnel_requests_request_type_check_rollback_20260701
            CHECK (
                request_type IN ($types)
            )
        ");
    }
};

