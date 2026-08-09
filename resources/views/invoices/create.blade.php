<x-app-layout :title="$isServiceBuy ? __('Create') . ' ' . __('Service Buy Invoice') : __('Create Invoice')">
    <div>
        @if ($invoice_type === 'buy' && ! $isServiceBuy)
            <x-card class="mb-4" class_body="p-4">
                <form action="{{ route('invoices.import-buy-csv') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="grow">
                        <label class="label"><span class="label-text">{{ __('Import draft buy invoice from CSV') }}</span></label>
                        <input type="file" name="csv_file" accept=".csv,text/csv" required class="file-input file-input-bordered file-input-sm w-full" />
                        <p class="mt-1 text-xs text-base-content/60">product_id (or id), quantity, EndBuy_Price (or unit_price), customer_id (or customerId), date</p>
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm">{{ __('Import CSV') }}</button>
                </form>
            </x-card>
        @endif
        <form action="{{ route('invoices.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <h2 class="card-title">
                    @if ($isReturnServiceBuy)
                        {{ __('Add') . ' ' . __('Return Service Buy Invoice') }}
                    @else
                        {{ __('Add') . ' ' . ($isServiceBuy ? __('Service Buy Invoice') : __($invoice_type)) }}
                    @endif
                </h2>
                <x-show-message-bags />

                @php($invoice = $invoice ?? new \App\Models\Invoice())
                @switch($invoice_type)
                    @case('sell')
                        @include('invoices.forms.sell')
                    @break

                    @case('buy')
                        @if ($isServiceBuy)
                            @include('invoices.forms.buy_service')
                        @else
                            @include('invoices.forms.buy')
                        @endif
                    @break

                    @case('return_sell')
                        @include('invoices.forms.return_sell')
                    @break

                    @case('return_buy')
                        @if ($isReturnServiceBuy)
                            @php($isServiceBuy = true)
                        @endif
                        @include('invoices.forms.return_buy')
                    @break

                    @default
                        <p>{{ __('Invalid invoice type') }}</p>
                @endswitch
            </div>
        </form>
    </div>

    @pushOnce('scripts')
        <script type="module">
            jalaliDatepicker.startWatch({'persianDigits': true});
        </script>
    @endPushOnce

</x-app-layout>
