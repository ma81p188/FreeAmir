<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Main migration
    |--------------------------------------------------------------------------
    */

    public function up(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        foreach ($this->schemaChanges() as $table => $columns) {
            foreach ($columns as $column => $definition) {
                $this->convertToTinyInteger(
                    $table,
                    $column,
                    $definition['map'],
                    $definition['integer_default'],
                    $definition['nullable'] ?? false
                );
            }
        }
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        foreach ($this->schemaChanges() as $table => $columns) {
            foreach ($columns as $column => $definition) {
                $this->restoreToString(
                    $table,
                    $column,
                    $definition['map'],
                    $definition['string_default'],
                    $definition['nullable'] ?? false
                );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Schema definitions
    |--------------------------------------------------------------------------
    */

    private function schemaChanges(): array
    {
        return [

            'subjects' => [
                'type' => [
                    'map' => [
                        'debtor' => 1,
                        'creditor' => 2,
                        'both' => 3,
                    ],
                    'integer_default' => 3,
                    'string_default' => 'both',
                ],
            ],

            'invoices' => [

                'invoice_type' => [
                    'map' => [
                        'buy' => 1,
                        'sell' => 2,
                        'return_buy' => 3,
                        'return_sell' => 4,
                        'void' => 5,
                    ],
                    'integer_default' => 2,
                    'string_default' => 'sell',
                ],

                'status' => [
                    'map' => $this->invoiceStatuses(),
                    'integer_default' => 1,
                    'string_default' => 'pending',
                ],
            ],

            'ancillary_costs' => [

                'type' => [
                    'map' => $this->ancillaryCostTypes(),
                    'integer_default' => 6,
                    'string_default' => 'Other',
                ],

                'status' => [
                    'map' => $this->invoiceStatuses(),
                    'integer_default' => 1,
                    'string_default' => 'pending',
                ],
            ],

            'ancillary_cost_items' => [
                'type' => [
                    'map' => $this->ancillaryCostTypes(),
                    'integer_default' => 6,
                    'string_default' => 'Other',
                ],
            ],

            'employees' => [

                'nationality' => [
                    'map' => [
                        'iranian' => 1,
                        'foreign' => 2,
                    ],
                    'integer_default' => 1,
                    'string_default' => 'iranian',
                ],

                'gender' => [
                    'map' => [
                        'male' => 1,
                        'female' => 2,
                    ],
                    'integer_default' => null,
                    'string_default' => null,
                    'nullable' => true,
                ],

                'marital_status' => [
                    'map' => [
                        'single' => 1,
                        'married' => 2,
                        'divorced' => 3,
                        'widowed' => 4,
                    ],
                    'integer_default' => null,
                    'string_default' => null,
                    'nullable' => true,
                ],

                'duty_status' => [
                    'map' => [
                        'liable' => 1,
                        'completed' => 2,
                        'in_progress' => 3,
                        'exempt' => 4,
                    ],
                    'integer_default' => null,
                    'string_default' => null,
                    'nullable' => true,
                ],

                'insurance_type' => [
                    'map' => [
                        'social_security' => 1,
                        'other' => 2,
                    ],
                    'integer_default' => null,
                    'string_default' => null,
                    'nullable' => true,
                ],

                'education_level' => [
                    'map' => [
                        'below_diploma' => 1,
                        'diploma' => 2,
                        'associate' => 3,
                        'bachelor' => 4,
                        'master' => 5,
                        'phd' => 6,
                    ],
                    'integer_default' => null,
                    'string_default' => null,
                    'nullable' => true,
                ],

                'employment_type' => [
                    'map' => [
                        'permanent' => 1,
                        'contract' => 2,
                        'other' => 3,
                    ],
                    'integer_default' => null,
                    'string_default' => null,
                    'nullable' => true,
                ],
            ],

            'payroll_elements' => [

                'system_code' => [
                    'map' => $this->payrollElementSystemCodes(),
                    'integer_default' => 15,
                    'string_default' => 'OTHER',
                ],

                'category' => [
                    'map' => [
                        'earning' => 1,
                        'deduction' => 2,
                    ],
                    'integer_default' => 1,
                    'string_default' => 'earning',
                ],

                'calc_type' => [
                    'map' => $this->payrollElementCalcTypes(),
                    'integer_default' => 1,
                    'string_default' => 'fixed',
                ],
            ],

            'tax_slabs' => [
                'calc_type' => [
                    'map' => $this->payrollElementCalcTypes(),
                    'integer_default' => 1,
                    'string_default' => 'fixed',
                ],
            ],

            'payrolls' => [
                'status' => [
                    'map' => $this->payrollStatuses(),
                    'integer_default' => 1,
                    'string_default' => 'draft',
                ],
            ],

            'payroll_status_histories' => [

                'from_status' => [
                    'map' => $this->payrollStatuses(),
                    'integer_default' => 1,
                    'string_default' => 'draft',
                ],

                'to_status' => [
                    'map' => $this->payrollStatuses(),
                    'integer_default' => 1,
                    'string_default' => 'draft',
                ],
            ],

            'personnel_requests' => [

                'request_type' => [
                    'map' => $this->personnelRequestTypes(),
                    'integer_default' => 10,
                    'string_default' => 'OTHER',
                ],

                'status' => [
                    'map' => [
                        'pending' => 1,
                        'approved' => 2,
                        'rejected' => 3,
                    ],
                    'integer_default' => 1,
                    'string_default' => 'pending',
                ],
            ],

            'work_shifts' => [
                'thursday_status' => [
                    'map' => [
                        'holiday' => 1,
                        'full_day' => 2,
                        'half_day' => 3,
                    ],
                    'integer_default' => 3,
                    'string_default' => 'half_day',
                ],
            ],

            'customers' => [
                'type' => [
                    'map' => [
                        'individual' => 1,
                        'legal_entity' => 2,
                        'civil_partnership' => 3,
                        'foreign_national' => 4,
                    ],
                    'integer_default' => 1,
                    'string_default' => 'individual',
                ],
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Convert string / enum -> integer
    |--------------------------------------------------------------------------
    */

    private function convertToTinyInteger(
        string $table,
        string $column,
        array $map,
        ?int $default,
        bool $nullable = false
    ): void {

        if (!Schema::hasTable($table) || !$this->hasColumn($table, $column)) {
            return;
        }

        if ($this->isIntegerColumn($table, $column)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Save indexes
        |--------------------------------------------------------------------------
        */

        $indexes = $this->getIndexesForColumn($table, $column);

        /*
        |--------------------------------------------------------------------------
        | Remove constraints
        |--------------------------------------------------------------------------
        */

        $this->dropColumnConstraints($table, $column);

        /*
        |--------------------------------------------------------------------------
        | Remove indexes
        |--------------------------------------------------------------------------
        */

        $this->dropIndexes($table, $indexes);

        /*
        |--------------------------------------------------------------------------
        | Change to VARCHAR first
        |--------------------------------------------------------------------------
        */

        $this->alterColumn(
            $table,
            $column,
            $nullable
                ? 'VARCHAR(50) NULL'
                : 'VARCHAR(50) NOT NULL'
        );

        /*
        |--------------------------------------------------------------------------
        | Convert existing values
        |--------------------------------------------------------------------------
        */

        foreach ($map as $stringValue => $integerValue) {

            DB::table($table)
                ->where($column, $stringValue)
                ->update([
                    $column => (string) $integerValue,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize invalid values
        |--------------------------------------------------------------------------
        */

        $validValues = array_map(
            'strval',
            array_values($map)
        );

        if ($nullable) {

            DB::table($table)
                ->whereNotNull($column)
                ->whereNotIn($column, $validValues)
                ->update([
                    $column => null,
                ]);

        } else {

            DB::table($table)
                ->where(function ($query) use ($column, $validValues) {

                    $query
                        ->whereNull($column)
                        ->orWhereNotIn($column, $validValues);

                })
                ->update([
                    $column => (string) $default,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Convert to TINYINT
        |--------------------------------------------------------------------------
        */

        $this->alterColumn(
            $table,
            $column,
            $nullable
                ? 'TINYINT NULL'
                : 'TINYINT NOT NULL'
        );

        /*
        |--------------------------------------------------------------------------
        | Restore default
        |--------------------------------------------------------------------------
        */

        if ($default !== null && !$nullable) {

            $this->addDefaultConstraint(
                $table,
                $column,
                (string) $default
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Restore indexes
        |--------------------------------------------------------------------------
        */

        $this->restoreIndexes($table, $indexes);
    }


    /*
    |--------------------------------------------------------------------------
    | Convert integer -> string
    |--------------------------------------------------------------------------
    */

    private function restoreToString(
        string $table,
        string $column,
        array $map,
        ?string $default,
        bool $nullable = false
    ): void {

        if (!Schema::hasTable($table) || !$this->hasColumn($table, $column)) {
            return;
        }

        if ($this->isStringColumn($table, $column)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Save indexes
        |--------------------------------------------------------------------------
        */

        $indexes = $this->getIndexesForColumn($table, $column);

        /*
        |--------------------------------------------------------------------------
        | Drop constraints
        |--------------------------------------------------------------------------
        */

        $this->dropColumnConstraints($table, $column);

        /*
        |--------------------------------------------------------------------------
        | Drop indexes
        |--------------------------------------------------------------------------
        */

        $this->dropIndexes($table, $indexes);

        /*
        |--------------------------------------------------------------------------
        | Change to VARCHAR
        |--------------------------------------------------------------------------
        */

        $this->alterColumn(
            $table,
            $column,
            $nullable
                ? 'VARCHAR(50) NULL'
                : 'VARCHAR(50) NOT NULL'
        );

        /*
        |--------------------------------------------------------------------------
        | Reverse values
        |--------------------------------------------------------------------------
        */

        $reverseMap = array_flip($map);

        foreach ($reverseMap as $integerValue => $stringValue) {

            DB::table($table)
                ->where($column, (string) $integerValue)
                ->update([
                    $column => $stringValue,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize invalid values
        |--------------------------------------------------------------------------
        */

        $validValues = array_keys($map);

        if ($nullable) {

            DB::table($table)
                ->whereNotNull($column)
                ->whereNotIn($column, $validValues)
                ->update([
                    $column => null,
                ]);

        } else {

            DB::table($table)
                ->where(function ($query) use ($column, $validValues) {

                    $query
                        ->whereNull($column)
                        ->orWhereNotIn($column, $validValues);

                })
                ->update([
                    $column => $default,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Restore default
        |--------------------------------------------------------------------------
        */

        if ($default !== null && !$nullable) {

            $this->addDefaultConstraint(
                $table,
                $column,
                $this->quote($default)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Restore indexes
        |--------------------------------------------------------------------------
        */

        $this->restoreIndexes($table, $indexes);
    }


    /*
    |--------------------------------------------------------------------------
    | SQL Server ALTER COLUMN
    |--------------------------------------------------------------------------
    */

    private function alterColumn(
        string $table,
        string $column,
        string $definition
    ): void {

        DB::statement(
            sprintf(
                'ALTER TABLE %s ALTER COLUMN %s %s',
                $this->quoteIdentifier($table),
                $this->quoteIdentifier($column),
                $definition
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Drop CHECK / DEFAULT constraints
    |--------------------------------------------------------------------------
    */

    private function dropColumnConstraints(
        string $table,
        string $column
    ): void {

        $constraints = DB::select(
            '
            SELECT
                dc.name AS constraint_name
            FROM sys.default_constraints dc
            INNER JOIN sys.columns c
                ON dc.parent_object_id = c.object_id
                AND dc.parent_column_id = c.column_id
            INNER JOIN sys.tables t
                ON t.object_id = c.object_id
            WHERE t.name = ?
              AND c.name = ?

            UNION

            SELECT
                cc.name AS constraint_name
            FROM sys.check_constraints cc
            INNER JOIN sys.columns c
                ON cc.parent_object_id = c.object_id
            INNER JOIN sys.tables t
                ON t.object_id = c.object_id
            WHERE t.name = ?
              AND cc.definition LIKE ?
            ',
            [
                $table,
                $column,
                $table,
                '%' . $column . '%',
            ]
        );

        foreach ($constraints as $constraint) {

            DB::statement(
                sprintf(
                    'ALTER TABLE %s DROP CONSTRAINT %s',
                    $this->quoteIdentifier($table),
                    $this->quoteIdentifier($constraint->constraint_name)
                )
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Add DEFAULT constraint
    |--------------------------------------------------------------------------
    */

    private function addDefaultConstraint(
        string $table,
        string $column,
        string $value
    ): void {

        $constraintName =
            'DF_' .
            $table .
            '_' .
            $column .
            '_' .
            substr(md5(uniqid('', true)), 0, 8);

        DB::statement(
            sprintf(
                'ALTER TABLE %s ADD CONSTRAINT %s DEFAULT %s FOR %s',
                $this->quoteIdentifier($table),
                $this->quoteIdentifier($constraintName),
                $value,
                $this->quoteIdentifier($column)
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Index handling
    |--------------------------------------------------------------------------
    */

    private function getIndexesForColumn(
        string $table,
        string $column
    ): array {

        return DB::select(
            '
            SELECT
                i.name AS index_name,
                i.is_unique,
                i.is_primary_key,
                STUFF(
                    (
                        SELECT
                            \',\' + c2.name
                        FROM sys.index_columns ic2
                        INNER JOIN sys.columns c2
                            ON ic2.object_id = c2.object_id
                            AND ic2.column_id = c2.column_id
                        WHERE ic2.object_id = i.object_id
                          AND ic2.index_id = i.index_id
                        ORDER BY ic2.key_ordinal
                        FOR XML PATH(\'\')
                    ),
                    1,
                    1,
                    \'\'
                ) AS columns_list
            FROM sys.indexes i
            INNER JOIN sys.index_columns ic
                ON i.object_id = ic.object_id
                AND i.index_id = ic.index_id
            INNER JOIN sys.columns c
                ON ic.object_id = c.object_id
                AND ic.column_id = c.column_id
            INNER JOIN sys.tables t
                ON i.object_id = t.object_id
            WHERE t.name = ?
              AND c.name = ?
              AND i.is_primary_key = 0
            GROUP BY
                i.name,
                i.is_unique,
                i.is_primary_key,
                i.object_id,
                i.index_id
            ',
            [
                $table,
                $column,
            ]
        );
    }


    private function dropIndexes(
        string $table,
        array $indexes
    ): void {

        foreach ($indexes as $index) {

            DB::statement(
                sprintf(
                    'DROP INDEX %s ON %s',
                    $this->quoteIdentifier($index->index_name),
                    $this->quoteIdentifier($table)
                )
            );
        }
    }


    private function restoreIndexes(
        string $table,
        array $indexes
    ): void {

        foreach ($indexes as $index) {

            $unique = $index->is_unique ? 'UNIQUE ' : '';

            DB::statement(
                sprintf(
                    'CREATE %sINDEX %s ON %s (%s)',
                    $unique,
                    $this->quoteIdentifier($index->index_name),
                    $this->quoteIdentifier($table),
                    $this->quoteIndexColumns($index->columns_list)
                )
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Column detection
    |--------------------------------------------------------------------------
    */

    private function hasColumn(
        string $table,
        string $column
    ): bool {

        $result = DB::selectOne(
            '
            SELECT TOP 1 COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            ',
            [
                'dbo',
                $table,
                $column,
            ]
        );

        return $result !== null;
    }


    private function columnType(
        string $table,
        string $column
    ): ?string {

        $result = DB::selectOne(
            '
            SELECT TOP 1 DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            ',
            [
                'dbo',
                $table,
                $column,
            ]
        );

        return $result
            ? strtolower($result->DATA_TYPE)
            : null;
    }


    private function isIntegerColumn(
        string $table,
        string $column
    ): bool {

        return in_array(
            $this->columnType($table, $column),
            [
                'tinyint',
                'smallint',
                'int',
                'bigint',
            ],
            true
        );
    }


    private function isStringColumn(
        string $table,
        string $column
    ): bool {

        return in_array(
            $this->columnType($table, $column),
            [
                'varchar',
                'nvarchar',
                'char',
                'nchar',
                'text',
                'ntext',
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function quote(?string $value): string
    {
        return DB::getPdo()->quote($value ?? '');
    }


    private function quoteIdentifier(string $value): string
    {
        return '[' . str_replace(']', ']]', $value) . ']';
    }


    private function quoteIndexColumns(string $columns): string
    {
        return collect(explode(',', $columns))
            ->map(function ($column) {
                return $this->quoteIdentifier(trim($column));
            })
            ->implode(', ');
    }


    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }


    /*
    |--------------------------------------------------------------------------
    | Enum maps
    |--------------------------------------------------------------------------
    */

    private function invoiceStatuses(): array
    {
        return [
            'pending' => 1,
            'pre_invoice' => 2,
            'approved' => 3,
            'unapproved' => 4,
            'approved_inactive' => 5,
            'rejected' => 6,
            'ready_to_approve' => 7,
            'partially_paid' => 8,
            'paid' => 9,
        ];
    }


    private function ancillaryCostTypes(): array
    {
        return [
            'Shipping' => 1,
            'Insurance' => 2,
            'Customs' => 3,
            'Taxes' => 4,
            'Loading' => 5,
            'Other' => 6,
        ];
    }


    private function payrollElementSystemCodes(): array
    {
        return [
            'CHILD_ALLOWANCE' => 1,
            'HOUSING_ALLOWANCE' => 2,
            'FOOD_ALLOWANCE' => 3,
            'MARRIAGE_ALLOWANCE' => 4,
            'OVERTIME' => 5,
            'AUTO_OVERTIME' => 6,
            'FRIDAY_PAY' => 7,
            'HOLIDAY_PAY' => 8,
            'MISSION_PAY' => 9,
            'INSURANCE_EMP' => 10,
            'INSURANCE_EMP2' => 11,
            'UNEMPLOYMENT_INS' => 12,
            'INCOME_TAX' => 13,
            'ABSENCE_DEDUCTION' => 14,
            'OTHER' => 15,
            'UNDERTIME' => 16,
        ];
    }


    private function payrollElementCalcTypes(): array
    {
        return [
            'fixed' => 1,
            'formula' => 2,
            'percentage' => 3,
            'daily' => 4,
        ];
    }


    private function payrollStatuses(): array
    {
        return [
            'draft' => 1,
            'pending_manager_approval' => 2,
            'approved' => 3,
            'paid' => 4,
        ];
    }


    private function personnelRequestTypes(): array
    {
        return [
            'LEAVE_HOURLY' => 1,
            'LEAVE_DAILY' => 2,
            'SICK_LEAVE' => 3,
            'LEAVE_WITHOUT_PAY' => 4,
            'LEAVE_WITHOUT_PAY_HOURLY' => 5,
            'MISSION_HOURLY' => 6,
            'MISSION_DAILY' => 7,
            'OVERTIME_ORDER' => 8,
            'REMOTE_WORK' => 9,
            'OTHER' => 10,
        ];
    }
};
