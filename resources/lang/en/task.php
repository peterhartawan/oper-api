<?php

return [
    'failure_save_task' => [
        'code'          => 41700,
        'status_code'   => 422,
        'message'       => "Unable to save task. Please try again."],
    'failure_delete_task' => [
        'code'          => 41701,
        'status_code'   => 422,
        'message'       => "Unable to delete task with ID :id. Please try again."],
    'cannot_skip_task' => [
        'code'          => 41702,
        'status_code'   => 422,
        'message'       => "Task can not be skipped."],
    'invalid_task_status' => [
        'code'          => 41703,
        'status_code'   => 422,
        'message'       => "Unable to save task. Invalid task status."],
    'cannot_skip_last_task' => [
        'code'          => 41704,
        'status_code'   => 422,
        'message'       => "Last Task can not be skipped."],
];