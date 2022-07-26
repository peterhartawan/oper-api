<?php
namespace App\Constants;

class Constant
{
    # FILE SIZE
    const MAX_IMAGE_SIZE = 1048; //in KB

    #DATE FORMAT
    const DATE_FORMAT = "Y-m-d H:i:s";

    #BOOLEAN
    const BOOLEAN_TRUE  = 1;
    const BOOLEAN_FALSE = 0;

    #OPTION
    const OPTION_ENABLE  = 1;
    const OPTION_DISABLE = 0;

    #STATUS
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 2;
    const STATUS_SUSPENDED  = 3;
    const STATUS_DELETED  = 4;

    #GENDER
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

    #TIME
    const TIME_ONE_SECOND = 1;
    const TIME_THREE_SECONDS = 3;
    const TIME_ONE_HOUR = 3600;
    const TIME_SIX_HOURS = 21600;
    const TIME_ONE_DAY = 86400;
    const TIME_ONE_WEEK = 604800;
    const TIME_TWO_WEEKS = 1209600;

    const TOKEN_AUTH_EXPIRED = 180; #days
    const TOKEN_REFRESH_EXPIRED = 300; #days
    const TOKEN_RESET_LIFETIME = 7; #minutes
    const TOKEN_ACTIVATION_LIFETIME = 14; #days

    # OTP
    const OTP_RESET_LIFETIME = 30; #minutes
    const OTP_LENGTH= 10; #minutes

    # OTP SEND DELAY
    const OTP_DEFAULT_DELAY = 120; //second
    const OTP_EXTEND_DELAY = 900;

    # CHANGE EMAIL LIFETIME
    const CHANGE_EMAIL_LIFETIME = 3; #days

    # USER TYPE
    const ROLE_SUPERADMIN = 1;
    const ROLE_VENDOR = 2;
    const ROLE_VENDOR_SUB = 9;
    const ROLE_ENTERPRISE= 3;
    const ROLE_DISPATCHER_ENTERPRISE_REGULER = 4;
    const ROLE_DISPATCHER_ENTERPRISE_PLUS = 5;
    const ROLE_DISPATCHER_ONDEMAND = 6;
    const ROLE_DRIVER = 7;
    const ROLE_EMPLOYEE = 8;

    # ENTERPRISE TYPE
    const ENTERPRISE_TYPE_REGULAR = 1;
    const ENTERPRISE_TYPE_PLUS = 2;
    const ENTERPRISE_TYPE_ONDEMAND = 3;

    # DRIVER TYPE
    const DRIVER_TYPE_PKWT = 1;
    const DRIVER_TYPE_PKWT_BACKUP = 2;
    const DRIVER_TYPE_FREELANCE = 3;

    #ORDER OPER STATUS
    const ORDER_OPEN = 1;
    const ORDER_INPROGRESS   = 2;
    const ORDER_COMPLETED  = 3;
    const ORDER_MOVED  = 4;
    const ORDER_CANCELED  = 5;
    const ORDER_DELETED  = 6;

    #ORDER OPER STATUS
    const ORDER_TASK_NOT_STARTED = 0;
    const ORDER_TASK_INPROGRESS = 1;
    const ORDER_TASK_COMPLETED   = 2;
    const ORDER_TASK_SKIPPED = 3;

    # ORDER OPER TYPE
    const ORDER_TYPE_ENTERPRISE = 1;
    const ORDER_TYPE_ENTERPRISE_PLUS = 2;
    const ORDER_TYPE_ENTERPRISE_ONDEMAND = 3;
    const ORDER_TYPE_ENTERPRISE_PRIVATE = 4;
    const ORDER_TYPE_ONDEMAND = 5;
    const ORDER_TYPE_EMPLOYEE = 6;

    # PLACES PLACE TYPE
    const PLACES_TYPE_ORIGIN = 1;
    const PLACES_TYPE_DESTINATION = 2;

    #PRODUCTION ENV
    const ENV_PRODUCTION = "production";
    const ENV_STAGING = "staging";
    const ENV_DEVELOPMENT = "development";

    # LIMIT PAGINATION
    const LIMIT_PAGINATION = 10;

    #DELAY
    const DELAY_TASK = 5;
    const DELAY_ATTENDANCE = 30;

    #QONTAK TEMPLATE ID
    const QONTAK_TEMPLATE_ID_OTP = "5f3c160c-b33d-4dff-a2be-ff6d80782d83";
    const QONTAK_TEMPLATE_ID_ORDER_CREATED = "2c5869d1-4bb1-4d0e-aef2-f438ae09cd5e";
    const QONTAK_TEMPLATE_NOTIF_DISPATCHER_ADMIN = "306a22f8-60fb-4e89-b257-1b608d25e6dc";
    const QONTAK_TEMPLATE_ID_DRIVER_ASSIGNED = "ba4a4fdc-d395-4fcf-b299-9d2a4c8d0db6";
    const QONTAK_TEMPLATE_ORDER_BEGAN = "9b03f683-3746-40fa-8452-9163a7d40770";
    const QONTAK_TEMPLATE_REMINDER_30MIN = "bbf3d465-ae45-4c98-b17c-b41fc2ef5988";
    const QONTAK_TEMPLATE_REMINDER_OVERTIME = "d166115b-1c9a-4a9a-a370-fdcf2b0d2cf5";
    const QONTAK_TEMPLATE_PAYMENT = "e6941db5-0db2-4636-80c2-6ad12c285357";
    const QONTAK_TEMPLATE_VERIFIED = "84434d83-1107-4a2a-9f66-deda164d7839";
    const QONTAK_TEMPLATE_RATING = "55d79506-0106-4920-9e3d-81448ca5bfa1";

    static function getConstants() {
        $oClass = new \ReflectionClass('\\App\\Constants\\Constant');
        return $oClass->getConstants();
    }
}
