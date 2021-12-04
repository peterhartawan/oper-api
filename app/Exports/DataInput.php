<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use App\Models\Driver;
use App\Models\Places;
use App\Models\VehicleBrand;
use App\Models\TaskTemplate;
use App\Constants\Constant;

class DataInput implements WithTitle, WithHeadings, FromArray
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

    public function headings(): array {
        $header = [		
            'NO', 'ID TEMPLATE', 'DATE BOOKING','TIME BOOKING','MESSAGE','USER NAME',
            'USER PHONE NUMBER','ID ORIGIN LOCATION','ID DESTINATION LOCATION','VEHICLE BRAND','VEHICLE TYPE',
            'VEHICLE TRANSMISSION','VEHICLE LICENSE','VEHICLE OWNER','VEHICLE YEARS'
        ];
        
        return $header;
    }

    public function array(): array
    {
        if ($this->idrole == Constant::ROLE_ENTERPRISE){
            $templatestask  = TaskTemplate::query()->where('client_enterprise_identerprise', $this->identerprise )->first();
            $places         = Places::query()->where('places.status', Constant::STATUS_ACTIVE )->where('places.identerprise', $this->identerprise)->first();
        } else {
            $templatestask  = TaskTemplate::first();
            $places         = Places::query()->where('places.status', Constant::STATUS_ACTIVE )->first();
        }

        $vehiclebrand       = VehicleBrand::first();

        if ($templatestask) {
            $idtemplate = $templatestask->task_template_id;
        }else{
            $idtemplate = "";
        }

        if ($places) {
            $latitude = $places->latitude;
            $longitude = $places->longitude;
        }else{
            $latitude = "";
            $longitude = "";
        }

        if ($vehiclebrand) {
            $brand = $vehiclebrand->id;
        }else{
            $brand = "";
        }

        return [
            ['1',$idtemplate,'2019/12/02','14:00:00','Tolong jemput mobil','Rista','879743',$latitude,$longitude,$brand,'Avanza','AT','N 1212 BT','Velda','2019'] 
        ];

    }
    /**
     * @return string
     */
    public function title(): string
    {
        return 'Data Input';
    }
}