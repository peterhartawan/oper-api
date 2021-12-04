<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Inspector;
use App\Services\Response;
use App\Services\Validate;
use DB;
use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Http\Helpers\EventLog;

class FaqController extends Controller
{

    public function index(Request $request)
    {    
        $user = auth()->guard('api')->user();
        $faq = Faq::OrderBy('id'); 
        $rolefilter  = $request->query('idrole');

        switch ($user->idrole) {
            
            case Constant::ROLE_SUPERADMIN:
                $faq->whereNotNull('idrole');

                if (!empty($rolefilter)) {
                    $faq = $faq->where('idrole', $rolefilter);
                }

            break;

            default:
                $faq->where('idrole', $user->idrole);  
            break;
        }

        return Response::success($faq->paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION));
    }

     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function show($id)
    {
        $faq = Faq::where('id',$id)
                ->first();

        return Response::success($faq);
    }

    /**
     * Create Faq
     *
     * @param  [string] question
     * @param  [string] answer
     * @param  [int] idrole
    */
    public function store(Request $request)
    {

        Validate::request($request->all(),[
            'question'  => 'required|min:3|max:250|string',
            'answer'    => 'required|min:2|string',
            'idrole'    => 'required|int|exists:role|between:'.Constant::ROLE_VENDOR.', '.Constant::ROLE_ENTERPRISE
        ]);

        try {
            $faq = Faq::create([                
                'question'      => $request->question,                
                'answer'        => $request->answer,
                'idrole'        => $request->idrole,
                'created_by'    => auth()->guard('api')->user()->id,
            ]); 

            $dataraw = '';
            $reason  = 'Create Faq #';
            $trxid   = $faq->id;
            $model   = 'faq';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($faq);

        } catch (Exception $e) {
            throw new ApplicationException("faq.failure_save_faq");
        }
    }

        /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Faq  $Faq
     * @return \Illuminate\Http\Response
     */
    public function edit(Faq $faq)
    {
        //
    }

    /**
     * Update Inspector
     *
     * @param  [string] question
     * 
     * @param  [string] answer
    */
    public function update(Request $request, $id)
    {

        Validate::request($request->all(), [
            'question' => 'required|min:3|max:250|string',
            'answer'   => 'required|min:2|string',
            'idrole'   => 'required|int|exists:role|between:'.Constant::ROLE_VENDOR.', '.Constant::ROLE_ENTERPRISE
        ]);      
        
        try {
            $faq = Faq::where('id', $id)
                ->update([
                    'question'      => $request->question,                  
                    'answer'        => $request->answer,
                    'idrole'        => $request->idrole,
                ]);    

            $dataraw = '';
            $reason  = 'Update Faq #';
            $trxid   = $id;
            $model   = 'faq';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            throw new ApplicationException("faq.failure_save_faq");
        }

    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $faq = Faq::where('id',$id)->delete();

        if ($faq > 0){
            $dataraw = '';
            $reason  = 'delete Faq #';
            $trxid   = $id;
            $model   = 'faq';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("faq.failure_delete_faq", ['id' => $id]);
        }

    }


}