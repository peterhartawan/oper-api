<?php

namespace App\Http\Controllers;

use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Models\B2C\CustomerB2C;
use App\Models\B2C\KategoriKupon;
use App\Models\B2C\Promo;
use App\Services\QontakHandler;
use App\Services\Response;
use App\Services\Validate;
use Illuminate\Http\Request;
use DB;

class PromoController extends Controller
{
    public function blastGenerated(Request $request)
    {
        // Validate request
        Validate::request($request->all(), [
            'kupon'                 => 'array|required',
            'kupon.*.nama'          => 'required',
            'kupon.*.wa'            => 'required',
            'kupon.*.gender'        => 'required',
            'kupon.*.email'         => 'required',
            'kupon.*.namaKategori'  => 'required',
            'kupon.*.potongan'      => 'required',
            'kupon.*.credits'       => 'required',
            'kupon.*.hariBerlaku'   => 'required',
            'kupon.*.kodeKupon'     => 'required'
        ]);


        DB::beginTransaction();

        try {
            // $qontakHandler = new QontakHandler();

            // Loop over the request data
            foreach ($request->kupon as $kupon) {

                // var_dump($kupon);
                // Create customer data if phone not exist
                CustomerB2C::firstOrCreate(
                    ['phone' => $kupon["wa"]],
                    [
                        'phone'     => $kupon["wa"],
                        'email'     => $kupon["email"],
                        'fullname'  => $kupon["nama"],
                        'gender'    => $kupon["gender"],
                    ]
                );

                // Create category if name not exist, then get ID
                $kategoriKupon = KategoriKupon::firstOrCreate(
                    ['nama' => $kupon["namaKategori"]],
                    ['nama' => $kupon["namaKategori"]]
                );

                // Create promo based on category
                $idKategori = $kategoriKupon->id;

                Promo::firstOrCreate(
                    [
                        'kategori_id'   => $idKategori,
                        'kode'          => $kupon["kodeKupon"],
                        'potongan_fixed' => $kupon["potongan"],
                        'limit_klaim'   => 1,
                        'jumlah_klaim'  => $kupon["credits"],
                        'hari_berlaku'  => $kupon["hariBerlaku"]
                    ]
                );

                // $qontakHandler->sendImageMessage(
                //     "62" . $kupon["wa"],
                //     "Blast Kupon",
                //     Constant::QONTAK_TEST_IMAGE,
                //     "logo_oper",
                //     "https://rest.oper.co.id/storage/images/test/logo_oper.jpg",
                //     [
                //         [
                //             "key" => "1",
                //             "value" => "kode",
                //             "value_text" => $kupon["kodeKupon"]
                //         ],
                //     ]
                // );
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("promo.not_found");
        }

        return Response::success($request->all());
    }
}
