<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Models\B2C\Paket;
use App\Services\Response;

class PaketB2CController extends Controller
{
    /**
     * Returns all paket data
     */
    public function index()
    {
        $paket = Paket::with(['pricing'])
            ->get();

        if (empty($paket)) {
            throw new ApplicationException("paket.empty");
        }

        return Response::success($paket);
    }
}
