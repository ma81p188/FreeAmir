<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Services\ProductImportService;
use App\Services\ProductService;
use App\Services\WarehouseDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly WarehouseDashboardService $warehouseDashboardService
    ) {}

    public function report(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'group_name' => ['nullable', 'string'],
            'min_quantity' => ['nullable', 'numeric'],
            'need_order' => ['nullable', 'boolean'],
            'cols_submitted' => ['nullable'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
        ]);

        $data = $this->warehouseDashboardService->report($validated);

        $config = [
            'format' => 'A4',
            'orientation' => $data['portrait'] ? 'P' : 'L',
            'directionality' => 'rtl',
            'margin_top' => 28,
            'margin_bottom' => 18,
            'margin_header' => 6,
            'margin_footer' => 6,
            'defaultPageNumStyle' => 'persian',
        ];

        return PDF::loadView('warehouse.report-pdf', $data, [], $config)->stream('warehouse-report.pdf');
    }

    public function index()
    {
        $query = Product::query()
            ->with('productGroup')
            ->orderBy('code');

        /*
    |--------------------------------------------------------------------------
    | Search by Product Name
    |--------------------------------------------------------------------------
    | Normalize:
    | ي -> ی
    | ك -> ک
    | Multiple spaces -> single space
    |
    | Example:
    | "وينستون   لايت"
    | will match:
    | "وینستون لایت"
    |--------------------------------------------------------------------------
    */
        if (request()->filled('name')) {
            $name = trim(request('name'));

            // Normalize Persian/Arabic characters in search input
            $name = str_replace(
                ['ي', 'ى', 'ك'],
                ['ی', 'ی', 'ک'],
                $name
            );

            // Normalize multiple spaces
            $name = preg_replace('/\s+/u', ' ', $name);

            /*
         * Normalize database value as well:
         * ي -> ی
         * ى -> ی
         * ك -> ک
         * Multiple spaces are not easily normalized with SQL REPLACE,
         * but common extra-space cases are handled by the LIKE search.
         */
            $query->whereRaw(
                "REPLACE(
                REPLACE(
                    REPLACE(name, N'ي', N'ی'),
                    N'ى', N'ی'
                ),
                N'ك', N'ک'
            ) COLLATE Persian_100_CI_AI LIKE ?",
                ["%{$name}%"]
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Search by Product Code
    |--------------------------------------------------------------------------
    */
        if (request()->filled('code')) {
            $code = trim(request('code'));

            $query->where(
                'code',
                'like',
                '%' . $code . '%'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Search by Product Group Name
    |--------------------------------------------------------------------------
    */
        if (request()->filled('group_name')) {
            $groupName = trim(request('group_name'));

            // Normalize Persian/Arabic characters
            $groupName = str_replace(
                ['ي', 'ى', 'ك'],
                ['ی', 'ی', 'ک'],
                $groupName
            );

            // Normalize multiple spaces
            $groupName = preg_replace('/\s+/u', ' ', $groupName);

            $query->whereHas('productGroup', function ($groupQuery) use ($groupName) {
                $groupQuery->whereRaw(
                    "REPLACE(
                    REPLACE(
                        REPLACE(name, N'ي', N'ی'),
                        N'ى', N'ی'
                    ),
                    N'ك', N'ک'
                ) COLLATE Persian_100_CI_AI LIKE ?",
                    ["%{$groupName}%"]
                );
            });
        }

        /*
    |--------------------------------------------------------------------------
    | Minimum Quantity
    |--------------------------------------------------------------------------
    */
        if (
            request()->filled('min_quantity') &&
            is_numeric(request('min_quantity'))
        ) {
            $query->where(
                'quantity',
                '>=',
                (float) request('min_quantity')
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Need Order
    |--------------------------------------------------------------------------
    */
        if (request()->boolean('need_order')) {
            $query
                ->where('quantity_warning', '>', 0)
                ->whereColumn(
                    'quantity',
                    '<=',
                    'quantity_warning'
                );
        }

        /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
        $products = $query
            ->paginate(12)
            ->withQueryString();

        /*
    |--------------------------------------------------------------------------
    | Calculate Product Statistics
    |--------------------------------------------------------------------------
    */
        $products->transform(function ($product) {

            $product->needs_order =
                (float) ($product->quantity_warning ?? 0) > 0 &&
                (float) $product->quantity <=
                (float) $product->quantity_warning;

            $product->unapprovedQuantity =
                $this->productService->unapprovedQuantity($product);

            $product->totalSellCount =
                $this->productService->totalSellCount($product);

            if (auth()->user()->can('reports.journal')) {

                $product->totalSell =
                    $this->productService->totalSell($product);

                $product->salesProfit =
                    $product->totalSell +
                    $this->productService->totalCOGS($product);
            }

            return $product;
        });

        return view('products.index', [
            'products' => $products,
            'csvColumns' => $this->exportColumnMapping(),
            'reportColumns' => $this->reportColumnMapping(),
        ]);
    }

    public function create()
    {
        $groups = ProductGroup::select('id', 'name')->limit(20)->get();

        return view('products.create', compact('groups'));
    }

    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->getValidatedData();

        $product = $this->productService->create($validatedData);

        return redirect()->route('products.index')->with('success', __('Product created successfully.'));
    }

    public function edit(Product $product)
    {
        $productGroupIdsForSelect = ProductGroup::select('id', 'name')->limit(20)->pluck('id');
        $oldGroup = $product->productGroup;
        $groups = ProductGroup::whereIn('id', $productGroupIdsForSelect->push($oldGroup->id)->unique())->get();

        return view('products.edit', compact('product', 'groups'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validatedData = $request->getValidatedData();

        $this->productService->update($product, $validatedData);

        return redirect()->route('products.index')->with('success', __('Product updated successfully.'));
    }

    public function show(Product $product)
    {
        $product->load('productGroup', 'productWebsites');

        $product->lastCOG = $this->productService->lastApprovedBuyInvoiceItemCOG($product) ?? 0;
        $product->salesProfit = $this->productService->totalSell($product) + $this->productService->totalCOGS($product);

        $historyItems = $product->invoiceItems()
            ->with(['invoice.customer', 'invoice.ancillaryCosts.items' => function ($query) use ($product) {
                $query->where('product_id', $product->id);
            }])
            ->tap(function ($q) {
                foreach (['date', 'invoice_type', 'number'] as $col) {
                    $q->orderByDesc(
                        Invoice::select($col)->whereColumn('invoices.id', 'invoice_items.invoice_id')
                    );
                }
            })
            ->paginate(20);

        return view('products.show', compact('product', 'historyItems'));
    }

    public function destroy(Product $product)
    {
        $this->productService->delete($product);

        return redirect()->route('products.index')->with('success', __('Product deleted successfully.'));
    }

    public function export(Request $request): StreamedResponse
    {
        $columnMapping = $this->selectedExportColumns($request);

        $selectedOptionalColumns = array_values(array_intersect(
            array_keys($this->reportColumnMapping()),
            array_keys($columnMapping),
        ));

        $reportRows = $this->warehouseDashboardService->report([
            'cols_submitted' => true,
            'columns' => $selectedOptionalColumns,
        ])['rows']->keyBy(fn(array $row) => (string) $row['code']);

        $filename = 'products_' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($columnMapping, $reportRows) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM so Excel reads translated headers and Persian text correctly.
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, array_values($columnMapping));

            Product::with('productGroup', 'incomeSubject', 'cogsSubject', 'inventorySubject', 'salesReturnsSubject')
                ->orderBy('code')
                ->chunk(200, function ($products) use ($file, $columnMapping, $reportRows) {
                    foreach ($products as $product) {
                        $reportRow = $reportRows->get((string) $product->code, []);
                        $row = array_merge($reportRow, [
                            'name' => $product->name,
                            'category' => $product->productGroup?->name,
                            'code' => $product->code,
                            'stock' => $product->quantity,
                            'selling_price' => $product->selling_price,
                            'cost_of_goods' => $product->average_cost,
                            'income_subject_code' => $product->incomeSubject?->code,
                            'cogs_subject_code' => $product->cogsSubject?->code,
                            'inventory_subject_code' => $product->inventorySubject?->code,
                            'sales_returns_subject_code' => $product->salesReturnsSubject?->code,
                            'sstid' => $product->sstid,
                            'location' => $product->location,
                            'quantity_warning' => $product->quantity_warning,
                            'oversell' => $product->oversell,
                            'discount_formula' => $product->discount_formula,
                            'description' => $product->description,
                            'vat' => $product->vat,
                        ]);

                        fputcsv($file, array_map(
                            fn(string $column) => $row[$column] ?? null,
                            array_keys($columnMapping),
                        ));
                    }
                });

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importForm(): View
    {
        return view('products.import');
    }

    public function import(Request $request, ProductImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $importService->import($request->file('file'), getActiveCompany());

        return redirect()->route('products.index')->with('success', __('Import complete: :imported products imported, :updated updated, :groups groups created.', [
            'imported' => $result['imported'],
            'updated' => $result['updated'],
            'groups' => $result['groups_created'],
        ]));
    }

    public function searchProductGroup(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|max:100',
        ]);

        $q = $validated['q'];
        $productGroups = ProductGroup::where('name', 'like', "%{$q}%")->select('id', 'name')->limit(20)->get();

        if ($productGroups->isEmpty()) {
            return response()->json([]);
        }

        $grouped = [
            0 => $productGroups->map(fn($pg) => [
                'id' => $pg->id,
                'groupId' => 0,
                'groupName' => 'General',
                'text' => $pg->name,
                'type' => 'product group',
                'raw_data' => $pg->toArray(),
            ])->values()->all(),
        ];

        return response()->json([
            [
                'id' => 'group_product_groups',
                'headerGroup' => 'product group',
                'options' => (object) $grouped,
            ],
        ]);
    }

    private function exportColumnMapping(): array
    {
        return [
            'name' => __('Product name'),
            ...$this->reportColumnMapping(),
            'income_subject_code' => __('Revenue subject code'),
            'cogs_subject_code' => __('COGS subject code'),
            'inventory_subject_code' => __('Inventory subject code'),
            'sales_returns_subject_code' => __('Sales returns subject code'),
            'sstid' => __('Product SSTID'),
            'location' => __('Location in warehouse'),
            'quantity_warning' => __('Quantity warning'),
            'oversell' => __('Oversell'),
            'discount_formula' => __('Discount formula'),
            'description' => __('Description'),
            'vat' => __('VAT'),
        ];
    }

    private function reportColumnMapping(): array
    {
        return [
            'inbound' => __('Inbound'),
            'outbound' => __('Outbound'),
            'stock' => __('Stock'),
            'category' => __('Category'),
            'code' => __('Product code'),
            'selling_price' => __('Sale price'),
            'cost_of_goods' => __('Cost of goods'),
            'last_item_cost' => __('Last item cost'),
            'sales_profit' => __('Sales profit'),
            'revenue_account' => __('Revenue account amount'),
            'cogs_account' => __('COGS account amount'),
            'inventory_account' => __('Inventory account amount'),
            'sales_return_account' => __('Sales return account amount'),
        ];
    }

    private function selectedExportColumns(Request $request): array
    {
        $availableColumns = $this->exportColumnMapping();
        $validated = $request->validate([
            'cols_submitted' => ['nullable', 'boolean'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string', Rule::in(array_keys($availableColumns))],
        ]);

        if (! $request->boolean('cols_submitted')) {
            return $availableColumns;
        }

        $selectedColumns = array_unique(['name', ...(array) ($validated['columns'] ?? [])]);

        return array_intersect_key($availableColumns, array_flip($selectedColumns));
    }
}
