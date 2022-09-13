<?php

namespace App\Constants;

use App\Constants\Constant;

class RoleAccess
{
    // Role Based Access Control
    // Format
    // [CONTROLLER] => [ARRAY OF METHOD NAME]
    // [ARRAY OF METHOD NAME]
    // all  : role can access all feature
    const DEFAULT_LARAVEL_METHOD = ["index", "show", "store", "update", "destroy"];

    const SUPERADMIN = [
        "AttendanceController" => ["show", "reporting"],
        "AuthController" => "all",
        "ChangeEmailController" => "all",
        "ClientEnterpriseController" => "all",
        "PasswordResetController" => "all",
        "RequestOTPController" => "all",
        "StaticContentController" => "all",
        "TaskController" => "all",
        "TaskTemplateController" => "all",
        "UserController" => "all",
        "VendorController" => "all",
        "RoleController" => "all",
        "InspectorController" => "all",
        "PlacesController" => "all",
        "ImportController" => "all",
        "FaqController" => "all",
        "DriverController" => ["index", "resendpin"],
        "EmployeeController" => ["resendpin"],
        "DashboardController" => ["index", "grafik"],
        "OrderController" => ["cancel", "show_cancel", "show", "open", "inprogress", "complete", "show_open", "show_inprogress", "show_complete", "showByTrxId", "template", "totalorderweek", "totalordermonth", "totalordertoday"],
        "TrackingController" => "all",
        "ExportController" => "all",
        "VehicleBrandController" => "all",
        "VehicleTypeController" => "all",
        "EmployeePositionController" => "all",
        "EventLogController" => "all",
        "MobileCheckUpdateController" => "all",
        "DriverRequestController" => "all"
    ];

    const VENDOR = [
        "AuthController" => "all",
        "AttendanceController" => ["show", "reporting"],
        "UserController" => ["index", "change_password", "update", "me", "suspend", "activate"],
        "PasswordResetController" => "all",
        "ClientEnterpriseController" => ['index', 'type', 'show'],
        "VendorController" => ['show'],
        "DispatcherController" => "all",
        "DriverController" => "all",
        "StaticContentController" => ["show", "slug"],
        "PlacesController" => "all",
        "RoleController" => ["access"],
        "DashboardController" => ["index", "grafik"],
        "FaqController" => ["index"],
        "OrderController" => ["cancel", "show_cancel", "show", "open", "inprogress", "complete", "show_open", "show_inprogress", "show_complete", "showByTrxId", "template", "totalorderweek", "totalordermonth", "totalordertoday"],
        "TrackingController" => ["listTrackingTask", "listTrackingAttendance"],
        "EmployeeController" => "all",
        "VehicleTypeController" => "all",
        "EmployeePositionController" => "all",
        "TaskTemplateController" => "all",
        "VehicleBrandController" => "all",
        "DriverRequestController" => "all"
    ];

    const VENDOR_SUB = [
        "AuthController" => "all",
        "DriverController" => ["index"],
        "UserController" => ["index", "change_password", "update", "me"],
        "RoleController" => ["access"],
        "AttendanceController" => ["show", "reporting"],
    ];

    const ENTERPRISE = [
        "AuthController" => "all",
        "UserController" => ["index", "change_password", "update", "me"],
        "DriverController" => ["index"],
        "DispatcherController" => ["index"],
        "PasswordResetController" => "all",
        "ImportController" => ["importOrder"],
        "OrderController" => ["cancel", "show_cancel", "cancelorder", "store", "index", "show", "update", "open", "inprogress", "complete", "show_open", "show_inprogress", "show_complete", "showByTrxId", "template", "totalorderweek", "totalordermonth", "totalordertoday"],
        "StaticContentController" => ["show", "slug"],
        "RoleController" => ["access"],
        "TaskTemplateController" => ["index", "show", "tasktemplatereporting"],
        "FaqController" => ["index"],
        "VehicleTypeController" => "all",
        "DashboardController" => ["index", "grafik"],
        "PlacesController" => ['index'],
        "VehicleBrandController" => "all",
        "TrackingController" => ["listTrackingTask"],
        "AttendanceController" => ["show", "reporting"],
    ];

    const DISPATCHER_ENTERPRISE_REGULAR = [
        "AuthController" => "all",
        "UserController" => ["index", "change_password", "update", "me"],
        "PasswordResetController" => "all",
        "OrderController" => ["cancel", "show_cancel", "cancelorder", "assign", "index", "show", "open", "inprogress", "complete", "show_inprogress", "assign", "show_open", "show_complete", "showByTrxId", "template", "totalorderweek", "totalordermonth", "totalordertoday"],
        "StaticContentController" => ["show", "slug"],
        "RoleController" => ["access"],
        "TaskTemplateController" => ["index", "show", "tasktemplatereporting"],
        "DriverController" => ["index", "available_for_order", "orderdriver", "totalAccount"],
        "FaqController" => ["index"],
        "DashboardController" => ["index", "grafik"],
        "PlacesController" => ['index'],
        "VehicleTypeController" => "all",
        "VehicleBrandController" => "all",
        "TrackingController" => ["listTrackingTask"],
        "WebNotificationController" => "all",
        "ImportController" => ["importOrder"],
        "AttendanceController" => ["show"],
        "DriverRequestController" => "all"
    ];

    const DISPATCHER_ENTERPRISE_PLUS = [
        "AuthController" => "all",
        "UserController" => ["index", "change_password", "update", "me"],
        "PasswordResetController" => "all",
        "ClientEnterpriseController" => ['show'],
        "DriverController" => ["index", "available_for_order", "orderdriver", "totalAccount"],
        "StaticContentController" => ["show", "slug"],
        "OrderController" => ["cancel", "show_cancel", "cancelorder", "store", "assign", "index", "update", "show", "open", "inprogress", "complete", "show_inprogress", "show_open", "show_complete", "showByTrxId", "template", "orderdriver", "totalorderweek", "totalordermonth", "totalordertoday", "unavailableDates"],
        "StaticContentController" => ["show"],
        "TaskTemplateController" => ["index", "show", "tasktemplatereporting"],
        "RoleController" => ["access"],
        "DashboardController" => ["index", "grafik"],
        "FaqController" => ["index"],
        "VehicleTypeController" => "all",
        "PlacesController" => "all",
        "VehicleBrandController" => "all",
        "TrackingController" => ["listTrackingTask", "listTrackingAttendance", "listTrackingTaskWithDriver"],
        "WebNotificationController" => "all",
        "ImportController" => ["importOrder"],
        "AttendanceController" => ["show", "reporting"],
        "DriverRequestController" => "all",
        "OrderB2CController" => ["showByLink", "getLatest", "getFormData", "cancelOrder", "getInvoiceData"],
        "RatingB2CController" => ["store", "getRatingByDriverId", "getRatingByLink"],
        "OTPB2CController" => ["store", "verify", "isPhoneSucceedOTP"],
        "CustomerB2CController" => ["getCustomerByPhone"],
        "KuponController" => ["claim", "getKuponByCustomerId", "getKuponById"],
        "OtopickupController" => ["tracking"],
    ];

    const DISPATCHER_ONDEMAND = [
        "AuthController" => "all",
        "UserController" => ["index", "change_password", "update", "me"],
        "PasswordResetController" => "all",
        "OrderController" => ["cancel", "show_cancel", "store", "assign", "index", "show", "open", "inprogress", "complete", "show_inprogress", "show_open", "show_complete", "showByTrxId", "template", "totalorderweek", "totalordermonth", "totalordertoday"],
        "StaticContentController" => ["show", "slug"],
        "TaskTemplateController" => ["index"],
        "RoleController" => ["access"],
        "DriverController" => ["available_for_order"],
        "FaqController" => ["index"],
        "DriverRequestController" => "all"
    ];

    const DRIVER = [
        "AuthController" => "all",
        "AttendanceController" => ["clock_in", "clock_out", "last_status", "reporting"],
        "UserController" => ["index", "change_password", "update", "me"],
        "PasswordResetController" => "all",
        "StaticContentController" => ["show", "slug"],
        "RoleController" => ["access"],
        "TaskTemplateController" => ["index", "show"],
        "OrderController" => ["index", "history", "history_detail", "task", "skip_task", "show"],
        "OrderB2CController" => ["beginTracking", "arrived"],
        "FaqController" => ["index"],
        "TrackingController" => ["store"],
        "MobileNotificationController" => "all",
        "MobileCheckUpdateController" => "all",
        "PolisController" => "all"
    ];

    const EMPLOYEE = [
        "EmployeeController" => ["index", "inprogress", "complete", "totalAccount"],
        "AuthController" => "all",
        "UserController" => ["index", "change_password", "update", "me"],
        "PasswordResetController" => "all",
        "AttendanceController" => ["clock_in", "clock_out", "last_status", "reporting"],
        "StaticContentController" => ["show", "slug"],
        "OrderController" => ["index", "history", "history_detail", "task", "skip_task", "show"],
        "TrackingController" => ["store"],
        "MobileNotificationController" => "all",
        "MobileCheckUpdateController" => "all"
    ];

    static function getConstants()
    {
        $oClass = new \ReflectionClass('\\App\\Constants\\RoleAccess');
        return $oClass->getConstants();

    }
}
