<?php

return [
    'failure_create_order' => [
        'code'          => 41100,
        'status_code'   => 422,
        'message'       => "Unable to create order. Please try again."],
    'failure_update_order' => [
        'code'          => 41101,
        'status_code'   => 422,
        'message'       => "Unable to update order. Please try again."],
    'failure_delete_order' => [
        'code'          => 41102,
        'status_code'   => 422,
        'message'       => "Unable to delete order. Please try again."],
    'failure_update_task_order' => [
        'code'          => 41103,
        'status_code'   => 422,
        'message'       => "Unable to update order task. Please try again."],
    'failure_required_image' => [
        'code'          => 41104,
        'status_code'   => 422,
        'message'       => "Unable to update order task. Attachment required."],
    'failure_assign_driver' => [
        'code'          => 41103,
        'status_code'   => 422,
        'message'       => "Unable to assign order. the driver is in order."],
    'failure_assign_driver_client' => [
        'code'          => 41104,
        'status_code'   => 422,
        'message'       => "Unable to assign order. Drivers of PKWT and clients are not the same ."],
    'failure_cancel_order' => [
            'code'          => 41105,
            'status_code'   => 422,
            'message'       => "Unable to cancel order. Order has been processed."],
    'failure_assign_double' => [
        'code'          => 41105,
        'status_code'   => 422,
        'message'       => "Unable to assign order. Order has been processed."],
    'complete_order'    => "Your Order is Complete",
    'failure_assign_order' => [
        'code'          => 41107,
        'status_code'   => 422,
        'message'       => "Unable to assign order. Order status is not open."],
    'empty_task'        => 'No available task at this moment.',
    'enterprise_not_set' => [
        'code'          => 41108,
        'status_code'   => 422,
        'message'       => "Unable to create order. You are not related to any enterprise."],
    'driver_not_in_order' => [
        'code'          => 41109,
        'status_code'   => 422,
        'message'       => "Unable to tracking order. the driver not in order."],
    'failure_month' => [
        'code'          => 41110,
        'status_code'   => 422,
        'message'       => "Unable to export order. Please fill in the month."],
    'failure_date_select' => [
        'code'          => 41110,
        'status_code'   => 422,
        'message'       => "Unable to export order. Please fill selected date."],
    'failure_update_order' => [
        'code'          => 41111,
        'status_code'   => 422,
        'message'       => "Unable to update order. Client ID Not the same."],
    'invalid_open_order' => [
        'code'          => 41112,
        'status_code'   => 422,
        'message'       => "Order status is not open."],
    'invalid_creating_trx_id' => [
        'code'          => 41113,
        'status_code'   => 422,
        'message'       => "An error occured. Could not create TrxID."],
    'failure_attendance_driver' => [
        'code'          => 41114,
        'status_code'   => 422,
        'message'       => "Unable to export attendance. because the data is empty."],
    'failed_to_save' => [
        'code'          => 41115,
        'status_code'   => 422,
        'message'       => "Failed to save bulk order :id."],
];