<?php
namespace App\Exports;

use App\Exports\ReadMeSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserReport implements WithMultipleSheets
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

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new ReadMeSheet($this->idrole, $this->iduser, $this->identerprise);
        $sheets[] = new DataInput($this->idrole, $this->iduser, $this->identerprise);
        return $sheets;
    }
}