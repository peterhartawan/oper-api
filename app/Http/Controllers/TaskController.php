<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Services\Response;
use App\Exceptions\ApplicationException;
use App\Constants\constant;

class TaskController extends Controller
{

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $task = Task::where('idtask', $id)
                    ->where('status', Constant::STATUS_ACTIVE)
                    ->first();

        if (empty($task))
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'task','id' => $id]);

        return Response::success($task);
    }
}
