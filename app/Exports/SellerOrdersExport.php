<?php

namespace App\Exports;

use App\Exports\Sheets\OrdersListsSheet;
use App\Exports\Sheets\OrdersProductsSheet;
use App\Exports\Sheets\SellerOrdersListsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SellerOrdersExport implements Export, WithMultipleSheets
{
    public $orders;
    public $request;

    public function __construct($orders)
    {
        $this->orders  = $orders;
        $this->seller  = '$seller';
        $this->request = request();

    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new SellerOrdersListsSheet($this->orders);

        if (isset($this->request->filters['products'])) {
            $sheets[] = new OrdersProductsSheet($this->orders);
        }

        return $sheets;
    }
}
