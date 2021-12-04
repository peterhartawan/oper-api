<?php

namespace App\Imports;

use Illuminate\Http\Request;
use App\User;
use App\Imports\DataInputOrder;
use App\Constants\Constant;
use DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class OrderImport implements WithMultipleSheets, WithChunkReading
{
    private $request;
    private $result;

    function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        $this->result = new DataInputOrder($this->request);

        return [
            'Data Input' => $this->result
        ];
    }

    public function chunkSize(): int
    {
        return 300;
    }

    public function getResult()
    {
        return $this->result;
    }
}
