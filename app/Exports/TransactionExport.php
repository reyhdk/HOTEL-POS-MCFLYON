<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $orders;
    protected $totalRevenue;

    public function __construct($orders, $totalRevenue)
    {
        $this->orders = $orders;
        $this->totalRevenue = $totalRevenue;
    }

    public function view(): View
    {
        return view('exports.transactions', [
            'orders' => $this->orders,
            'totalRevenue' => $this->totalRevenue
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
            3    => ['font' => ['bold' => true]], 
        ];
    }
}