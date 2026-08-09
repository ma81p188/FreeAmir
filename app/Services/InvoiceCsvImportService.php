<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceCsvImportService
{
    /**
     * Create a draft buy invoice from a CSV file without changing inventory or accounting documents.
     */
    public function importBuyDraft(UploadedFile $file, User $user): Invoice
    {
        [$rows, $customerId, $date] = $this->parseRows($file);

        $customer = Customer::query()->find($customerId);
        if (! $customer) {
            throw ValidationException::withMessages([
                'csv_file' => [__('The customer in the CSV file does not belong to the active company.')],
            ]);
        }

        $products = Product::query()->whereIn('id', collect($rows)->pluck('product_id'))->get()->keyBy('id');
        $missingProductIds = collect($rows)->pluck('product_id')->diff($products->keys());

        if ($missingProductIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'csv_file' => [__('One or more products in the CSV file do not belong to the active company.')],
            ]);
        }

        $items = collect($rows)->map(fn (array $row) => [
            'itemable_id' => $row['product_id'],
            'itemable_type' => 'product',
            'quantity' => $row['quantity'],
            'unit' => $row['unit_price'],
            'unit_discount' => 0,
            'vat' => 0,
            'total' => $row['quantity'] * $row['unit_price'],
        ])->all();

        return DB::transaction(function () use ($user, $customer, $date, $items) {
            $number = ((int) Invoice::query()
                ->where('invoice_type', InvoiceType::BUY)
                ->lockForUpdate()
                ->max('number')) + 1;

            return InvoiceService::createInvoice($user, [
                'title' => __('Imported buy invoice'),
                'date' => $date,
                'invoice_type' => InvoiceType::BUY,
                'customer_id' => $customer->id,
                'number' => $number,
                'subtraction' => 0,
            ], $items, false)['invoice'];
        });
    }

    /**
     * @return array{array<int, array{product_id: int, quantity: float, unit_price: float}>, int, string}
     */
    private function parseRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $headerLine = fgets($handle);

        if ($headerLine === false) {
            throw ValidationException::withMessages(['csv_file' => [__('The CSV file is empty.')]]);
        }

        $delimiter = $this->detectDelimiter($headerLine);
        $header = str_getcsv(rtrim($headerLine, "\r\n"), $delimiter);

        $columns = [];
        foreach ($header as $index => $name) {
            $normalized = strtolower(trim((string) $name));
            $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $normalized);
            $columns[str_replace([' ', '-'], '_', $normalized)] = $index;
        }

        $productColumn = $this->firstColumn($columns, ['product_id', 'id']);
        $quantityColumn = $this->firstColumn($columns, ['quantity']);
        $priceColumn = $this->firstColumn($columns, ['endbuy_price', 'unit_price']);
        $customerColumn = $this->firstColumn($columns, ['customerid', 'customer_id']);
        $dateColumn = $this->firstColumn($columns, ['date']);

        if (in_array(null, [$productColumn, $quantityColumn, $priceColumn, $customerColumn, $dateColumn], true)) {
            throw ValidationException::withMessages([
                'csv_file' => [__('CSV columns must include product_id (or id), quantity, EndBuy_Price (or unit_price), customer_id (or customerId), and date.')],
            ]);
        }

        $rows = [];
        $customerId = null;
        $invoiceDate = null;
        $seenProductIds = [];
        $line = 1;

        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if ($values === [null] || $values === []) {
                continue;
            }

            $productId = $this->number($values[$productColumn] ?? null);
            $quantity = $this->number($values[$quantityColumn] ?? null);
            $unitPrice = $this->number($values[$priceColumn] ?? null);
            $rowCustomerId = $this->number($values[$customerColumn] ?? null);
            $rowDate = trim((string) ($values[$dateColumn] ?? ''));

            if ($productId === null || $quantity === null || $quantity <= 0 || $unitPrice === null || $unitPrice < 0 || $rowCustomerId === null || $rowDate === '') {
                throw ValidationException::withMessages(['csv_file' => [__('Invalid data in CSV row :row.', ['row' => $line])]]);
            }

            if (floor($productId) !== $productId || floor($rowCustomerId) !== $rowCustomerId) {
                throw ValidationException::withMessages(['csv_file' => [__('Product and customer IDs must be integers in CSV row :row.', ['row' => $line])]]);
            }

            try {
                $parsedDate = Carbon::createFromFormat('Y-m-d', $rowDate);
            } catch (\Throwable) {
                throw ValidationException::withMessages(['csv_file' => [__('Date must use Y-m-d format in CSV row :row.', ['row' => $line])]]);
            }

            if ($parsedDate->format('Y-m-d') !== $rowDate) {
                throw ValidationException::withMessages(['csv_file' => [__('Date must use Y-m-d format in CSV row :row.', ['row' => $line])]]);
            }

            if (isset($seenProductIds[(int) $productId])) {
                throw ValidationException::withMessages(['csv_file' => [__('Each product may appear only once in the CSV file.')]]);
            }

            $customerId ??= (int) $rowCustomerId;
            $invoiceDate ??= $parsedDate->toDateString();

            if ($customerId !== (int) $rowCustomerId || $invoiceDate !== $parsedDate->toDateString()) {
                throw ValidationException::withMessages(['csv_file' => [__('All CSV rows must use the same customer and date.')]]);
            }

            $seenProductIds[(int) $productId] = true;
            $rows[] = ['product_id' => (int) $productId, 'quantity' => $quantity, 'unit_price' => $unitPrice];
        }

        fclose($handle);

        if ($rows === []) {
            throw ValidationException::withMessages(['csv_file' => [__('The CSV file has no invoice rows.')]]);
        }

        return [$rows, $customerId, $invoiceDate];
    }

    private function firstColumn(array $columns, array $names): ?int
    {
        foreach ($names as $name) {
            if (array_key_exists($name, $columns)) {
                return $columns[$name];
            }
        }

        return null;
    }

    private function number(mixed $value): ?float
    {
        $value = trim((string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function detectDelimiter(string $header): string
    {
        $delimiters = [',', ';', "\t"];
        $delimiter = ',';
        $highestCount = 0;

        foreach ($delimiters as $candidate) {
            $count = substr_count($header, $candidate);
            if ($count > $highestCount) {
                $delimiter = $candidate;
                $highestCount = $count;
            }
        }

        return $delimiter;
    }
}
