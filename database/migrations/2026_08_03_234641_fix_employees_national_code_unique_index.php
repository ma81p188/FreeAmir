<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the existing unique index.
        DB::statement('
            DROP INDEX employees_company_id_national_code_unique
            ON employees
        ');

        // Allow multiple NULL national_code values,
        // but enforce uniqueness when national_code has a value.
        DB::statement('
            CREATE UNIQUE INDEX employees_company_id_national_code_unique
            ON employees (company_id, national_code)
            WHERE national_code IS NOT NULL
        ');
    }

    public function down(): void
    {
        // Remove the filtered unique index.
        DB::statement('
            DROP INDEX employees_company_id_national_code_unique
            ON employees
        ');

        // Restore the original unique index.
        DB::statement('
            CREATE UNIQUE INDEX employees_company_id_national_code_unique
            ON employees (company_id, national_code)
        ');
    }
};