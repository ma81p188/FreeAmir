<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DefaultCompany
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $activeCompanyId = $request->cookie('active-company-id');

        if ($activeCompanyId) {
            $company = Company::query()
                ->whereKey($activeCompanyId)
                ->whereHas('users', function ($query) {
                    $query->where('users.id', Auth::id());
                })
                ->first();

            if ($company) {
                $this->setCompanyConfig($company);

                return $next($request);
            }

            // Active company is invalid or user does not have access to it.
            Cookie::queue(Cookie::forget('active-company-id'));
        }

        $this->setDefaultCompany();

        return $next($request);
    }

    private function setDefaultCompany(): void
    {
        if (! Auth::check()) {
            return;
        }

        $currentFiscalYear = toEnglish(jdate('Y'));

        $company = Auth::user()
            ->companies()
            ->where('fiscal_year', $currentFiscalYear)
            ->first();

        if (! $company) {
            // Clear stale company configuration.
            config([
                'active-company-id' => null,
                'active-company-name' => null,
                'active-company-fiscal-year' => null,
            ]);

            return;
        }

        Cookie::queue(
            Cookie::make(
                'active-company-id',
                $company->id,
                362 * 24 * 60
            )
        );

        $this->setCompanyConfig($company);
    }

    private function setCompanyConfig(Company $company): void
    {
        config([
            'active-company-id' => $company->id,
            'active-company-name' => $company->name,
            'active-company-fiscal-year' => $company->fiscal_year,
        ]);
    }
}