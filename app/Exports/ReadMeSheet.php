<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Illuminate\Contracts\View\View;
use App\Models\Driver;
use App\Models\Places;
use App\Models\VehicleBrand;
use App\Models\TaskTemplate;
use App\Models\Task;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromQuery;
use App\Constants\Constant;

class ReadMeSheet implements WithTitle, FromView
{
    protected $idrole;
    protected $iduser;
    protected $identerprise;

    public function __construct($idrole, $iduser, $identerprise)
    {
        $this->idrole = $idrole;
        $this->iduser = $iduser;
        $this->identerprise = $identerprise;
    }

    public function view(): View
    {
        switch ($this->idrole) {
            case Constant::ROLE_SUPERADMIN:

                $templatestask  = TaskTemplate::all();
                $places         = Places::query()
                                ->where('places.status', Constant::STATUS_ACTIVE )
                                ->get();

            break;
            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
            case Constant::ROLE_VENDOR:

                $templatestask  = TaskTemplate::query()
                                 ->where('status','=',Constant::STATUS_ACTIVE)
                                 ->where("vendor_idvendor",auth()->guard('api')->user()->vendor_idvendor)
                                 ->get();

                $places         = Places::query()
                                ->where('places.status', Constant::STATUS_ACTIVE )
                                ->get();

            break;
            case Constant::ROLE_ENTERPRISE:
            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
            case Constant::ROLE_DISPATCHER_ONDEMAND:

                $templatestask  = TaskTemplate::query()
                                    ->where('status','!=',Constant::STATUS_DELETED)
                                    ->where('client_enterprise_identerprise', auth()->guard('api')->user()->client_enterprise_identerprise)
                                    ->get();

                $places         = Places::query()
                                    ->where('places.status', Constant::STATUS_ACTIVE )
                                    ->where('places.identerprise', $this->identerprise)->get();

                break;
            default:
                break;
        }

        $templatetask2 = $templatestask->map(function ($item, $key) {
            $item['team'] = Task::select('task.*')
                            ->where('task.task_template_id', $item->task_template_id)
                            ->get()
                        ->toArray();
            return $item;
        });

        $vehiclebrand   = VehicleBrand::all();
        
        return view('templateorder', [
            'places'    => $places,
            'brands'    => $vehiclebrand,
            'templates' => $templatestask,
            'tasktemplate'=> $templatetask2
        ]);
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Read Me';
    }

}