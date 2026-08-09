<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="100%" align="center" valign="middle">
                <font size="6"><b>ایستگاه دخانیات</b></font>
            </td>
        </tr>
    </table>

    <table width="100%" border="1" cellpadding="4" cellspacing="0" bordercolor="#000000">
        <tr>
            <td width="50%" align="center" valign="middle" bgcolor="#eeeeee">
                <font size="4">
                    @if ($invoice->invoice_type == App\Enums\InvoiceType::BUY)
                    صورتحساب خرید کالا و خدمات
                    @elseif ($invoice->invoice_type == App\Enums\InvoiceType::SELL)
                    صورتحساب فروش کالا و خدمات
                    @elseif ($invoice->invoice_type == App\Enums\InvoiceType::RETURN_BUY)
                    صورتحساب برگشت از خرید
                    @elseif ($invoice->invoice_type == App\Enums\InvoiceType::RETURN_SELL)
                    صورتحساب برگشت از فروش
                    @elseif ($invoice->invoice_type == App\Enums\InvoiceType::VOID)
                    صورتحساب ابطال فروش
                    @endif
                </font>
            </td>
            <td width="20%" align="center" valign="middle">
                <font size="2">شماره: <b>{{ formatDocumentNumber($invoice->number) }}</b></font>
            </td>
            <td width="30%" align="center" valign="middle">
                <font size="2">تاریخ: {{ formatDate($invoice->date) }}</font>
            </td>
        </tr>
    </table>

    <table width="100%" border="1" cellpadding="4" cellspacing="0" bordercolor="#000000">
        <tr>
            <td colspan="4" align="center" bgcolor="#dddddd">
                <font size="2">
                    @if ($invoice->invoice_type == App\Enums\InvoiceType::BUY || $invoice->invoice_type == App\Enums\InvoiceType::RETURN_BUY)
                    <b>مشخصات فروشنده</b>
                    @else
                    <b>مشخصات خریدار</b>
                    @endif
                </font>
            </td>
        </tr>
        <tr>
            <td width="40%">
                <font size="1">
                    <b>نام:</b>
                    {{ $invoice->customer->name ?? '' }}<br>
                    {{ $invoice->customer->address ?? '' }}
                </font>
            </td>
            <td width="30%">
                <font size="1">
                    شماره ملی: {{ isset($invoice->customer->personal_code) ? localizeNumber($invoice->customer->personal_code) : '' }}<br>
                    تلفن: <bdo dir="ltr">{{ isset($invoice->customer->phone) ? localizeNumber($invoice->customer->phone) : '' }}</bdo>
                </font>
            </td>
            <td width="30%">
                <font size="1">
                    شماره اقتصادی: {{ isset($invoice->customer->ecnmcs_code) ? localizeNumber($invoice->customer->ecnmcs_code) : '' }}<br>
                    کد پستی: {{ isset($invoice->customer->postal_code) ? localizeNumber($invoice->customer->postal_code) : '' }}
                </font>
            </td>
        </tr>
    </table>

    <table width="100%" border="1" cellpadding="3" cellspacing="0" bordercolor="#000000">
        <tr bgcolor="#dddddd">
            <td width="5%" align="center">
                <font size="1"><b>ردیف</b></font>
            </td>
            <td width="25%" align="center">
                <font size="1"><b>کالا</b></font>
            </td>
            <td width="10%" align="center">
                <font size="1"><b>تعداد</b></font>
            </td>
            <td width="20%" align="center">
                <font size="1"><b>قیمت واحد</b></font>
            </td>
            <td width="15%" align="center">
                <font size="1"><b>تخفیف</b></font>
            </td>
            <td width="25%" align="center">
                <font size="1"><b>مبلغ کل</b></font>
            </td>
        </tr>
        @php
        $invoiceTotalPrice = 0;
        $invoiceTotalDiscount = 0;
        $invoiceTotalVat = 0;
        @endphp

        @foreach ($invoice->items as $index => $invoiceItem)
        @php
        $itemQuantity = $invoiceItem->quantity;
        $unitPrice = $invoiceItem->unit_price;
        $totalPrice = $itemQuantity * $unitPrice;
        $discountPrice = $invoiceItem->unit_discount ?? 0;
        $vatPrice = $invoiceItem->vat ?? 0;
        $total = $totalPrice - $discountPrice + $vatPrice;
        $invoiceTotalPrice += $totalPrice;
        $invoiceTotalDiscount += $discountPrice;
        $invoiceTotalVat += $vatPrice;
        @endphp
        <tr>
            <td align="center">
                <font size="1">{{ localizeNumber($index + 1) }}</font>
            </td>
            <td align="right">
                <font size="1">{{ $invoiceItem->description ?? ($invoiceItem->itemable?->name ?? '') }}</font>
            </td>
            <td align="center">
                <font size="1">{{ formatNumber((int) $itemQuantity) }}</font>
            </td>
            <td align="center">
                <font size="1">{{ formatNumber($unitPrice) }}</font>
            </td>
            <td align="center">
                <font size="1">{{ formatNumber($discountPrice) }}</font>
            </td>
            <td align="center">
                <font size="1">{{ formatNumber($total) }}</font>
            </td>
        </tr>
        @endforeach

        @for ($i = count($invoice->items); $i < 5; $i++)
            <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            </tr>
            @endfor

            <tr bgcolor="#eeeeee">
                <td colspan="5" align="right">
                    <font size="1">مبلغ به حروف: {{ App\Helpers\NumberToWordHelper::convert((int) $invoice->amount) }}</font>
                </td>
                <td align="center">
                    <font size="1"><b>{{ formatNumber($invoice->amount) }}</b></font>
                </td>
            </tr>
    </table>


    <table width="100%" border="1" cellpadding="8" cellspacing="0" bordercolor="#000000">
        <tr>
            <td width="33%" align="center" height="75">
                <font size="1">مهر و امضای خریدار</font>
            </td>
            <td width="34%" align="center" height="75">
                <font size="1">مهر و امضای فروشنده</font>
            </td>
        </tr>
    </table>

    <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <font size="1">آدرس : تهران - کهریزک - سی متری شورا - ابتدای خیابان ولیعصر پلاک {{ localizeNumber('261') }}</font><br>
                <font size="1">شماره تماس: {{ localizeNumber('09193080080') }} -- {{ localizeNumber('09193706921') }}</font>
            </td>
        </tr>
    </table>
</body>

</html>