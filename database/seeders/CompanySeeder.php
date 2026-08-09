<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Support\SqlServerIdentityInsert;
use Illuminate\Database\Seeder;
use RuntimeException;

class CompanySeeder extends Seeder
{
    use SqlServerIdentityInsert;

    public function run(): void
    {
        $companyId = (int) getActiveCompany();
        $fiscalYear = jdate('Y', tr_num: 'en');

        if ($companyId === 1) {
            $company = $this->withIdentityInsert('companies', function () use ($companyId, $fiscalYear) {
                return Company::updateOrCreate(
                    ['id' => $companyId],
                    [
                        'name' => 'نام شرکت',
                        'fiscal_year' => $fiscalYear,
                    ]
                );
            });

            $users = User::all();

            foreach ($users as $user) {
                $user->companies()->syncWithoutDetaching([
                    $company->id,
                ]);
            }
        } else {
            $company = Company::find($companyId);
        }

        if (! $company) {
            throw new RuntimeException(
                "Company with ID {$companyId} does not exist."
            );
        }
    }
}
