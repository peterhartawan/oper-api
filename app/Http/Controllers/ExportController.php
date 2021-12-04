<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Order;
use App\Exports\OrdersExport;
use App\Services\Response;
use App\Constants\Constant;
use Illuminate\Support\Facades\Redirect;

class ExportController extends Controller
{
   /**
     * Export to xlsx
     */
    public function exportExcel(Request $request)
    {
        $month = $request->month;
        $createexcel =  Excel::store(new OrdersExport($month), 'public/file');
   
        return ;
       
    }
    /**
     * Export to csv
     */
    public function exportCSV()
    {
        return Excel::download(new OrdersExport, 'list.csv');
    }
}