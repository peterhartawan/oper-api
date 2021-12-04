<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\DriversImport;
use App\Imports\OrderImport;
use App\Imports\DriversImportFormatted;
use App\Imports\VendorImportFormatted;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Response;
use Illuminate\Support\Facades\Session;
use App\Exceptions\ApplicationException;

class ImportController extends Controller
{
    /**
     * Mukti Format
     */
    public function importDriver(Request $request)
    {
        $this->validate($request, [
            'idvendor' => 'required|integer|exists:vendor',
            'file' => 'required|mimes:xls,xlsx'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            try {
                $importModel = new DriversImport($request);
                Excel::import($importModel, $file);
                return Response::success($importModel->getResult());
            }
            catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $failures = $e->failures();
                return Response::error('Upload failed.');
            }
        }
    }

    /**
     * General Format
     */
    public function importDriverFormatted(Request $request)
    {
        $this->validate($request, [
            'idvendor' => 'required|integer|exists:vendor',
            'file' => 'required|mimes:xls,xlsx'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            try {
                $importModel = new DriversImportFormatted($request);
                Excel::import($importModel, $file);
                return Response::success($importModel->getResult());
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $failures = $e->failures();
                return Response::error('Upload failed.');
            }
        }
    }
    
    public function importVendorFormatted(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:xls,xlsx'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            try {
                $importModel = new VendorImportFormatted($request);
                Excel::import($importModel, $file);
                return Response::success($importModel->getResult());
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $failures = $e->failures();
                return Response::error('Upload failed.');
            }
        }
    }

    public function importOrder(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:xls,xlsx'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            try {
                $importModel = new OrderImport($request);
                Excel::import($importModel, $file);

                if(Session::get('jum_insert') == "kosong" ){                    
                    throw new ApplicationException('orders.failed_to_save',['id' => Session::get('status_importorder')]);

                }else{
                    return Response::success(Session::get('status_importorder'));
                }
                


            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $failures = $e->failures();
                return Response::error(Session::get('status_importorder'));
            }
        }
    }


}
