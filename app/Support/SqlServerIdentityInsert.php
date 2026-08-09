<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

trait SqlServerIdentityInsert
{
    protected function withIdentityInsert(string $table, callable $callback): mixed
    {
        if (DB::connection()->getDriverName() !== 'sqlsrv') {
            return $callback();
        }

        DB::statement("SET IDENTITY_INSERT [{$table}] ON");

        try {
            return $callback();
        } finally {
            DB::statement("SET IDENTITY_INSERT [{$table}] OFF");
        }
    }
}
