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
    const QONTAK_TEST = "4df09905-fddb-48ab-8443-1554184c0a7b";
    const QONTAK_TEST_IMAGE = "f4c54eda-230f-48fb-a443-1232b66df2cd";

    const QONTAK_TEMPLATE_ID_OTP = "5f3c160c-b33d-4dff-a2be-ff6d80782d83";
    const QONTAK_TEMPLATE_ID_ORDER_CREATED = "e3ad64bc-6873-4cad-a928-d4fc209352ff";
    const QONTAK_TEMPLATE_NOTIF_DISPATCHER_ADMIN = "860f6742-f117-46e4-9055-0725b1a0d088";
    const QONTAK_TEMPLATE_ID_DRIVER_ASSIGNED = "ba4a4fdc-d395-4fcf-b299-9d2a4c8d0db6";
    const QONTAK_TEMPLATE_DRIVER_START_TRACKING = "9aa2e6fd-2642-4ebb-acc8-80a754555c8c";
    const QONTAK_TEMPLATE_DRIVER_ARRIVED = "72ba54bb-c814-49ab-a747-75e9ad5b8ac3";
    const QONTAK_TEMPLATE_ORDER_BEGAN = "c6d23007-4e95-4277-9868-aa8aaee0d95f";
    const QONTAK_TEMPLATE_REMINDER_30MIN = "bbf3d465-ae45-4c98-b17c-b41fc2ef5988";
    const QONTAK_TEMPLATE_REMINDER_OVERTIME = "35ba8ba1-c1e0-45ac-a27a-379c0b9f66a2";
    const QONTAK_TEMPLATE_PAYMENT = "88a8ae48-3184-4156-a14e-62c95c7d44ab";
    const QONTAK_TEMPLATE_QRIS = "fd9e99b2-fce6-4cf8-a419-463d2932f610";
    const QONTAK_TEMPLATE_VERIFIED = "84434d83-1107-4a2a-9f66-deda164d7839";
    const QONTAK_TEMPLATE_RATING = "55d79506-0106-4920-9e3d-81448ca5bfa1";
    const QONTAK_TEMPLATE_FIRST_PROMO = "5e029760-31ab-4228-a50a-adb5ef609c79";
    const QONTAK_TEMPLATE_TOMORROW_REMINDER = "60943422-15bb-486f-b476-cb8a1f8d560f";
    const QONTAK_TEMPLATE_OTOPICKUP_UPDATE = "63282c9b-de85-47ca-8ca0-09a2ca9e4f51";
    const QONTAK_TEMPLATE_BLAST_ORDER = "c2b796a7-33fb-45a2-bb1e-4e6bb970daad";
    const QONTAK_TEMPLATE_BLAST_WAITING_LIST = "89bf3627-bdc0-4be4-8f26-49603e1fbb3e";

    #OTOPICKUP SEQUENCES
    const OP_PICKUP_SEQUENCE = 1;
    const OP_CONSULT_SEQUENCE = 18;
    const OP_SERVICE_SEQUENCE = 19;
    const OP_DROPOFF_SEQUENCE = 32;

    #OTOPICKUP STATES
    const OP_STATE_PICKUP = 1;
    const OP_STATE_CONSULT = 2;
    const OP_STATE_SERVICE = 3;
    const OP_STATE_DROPOFF = 4;
    const OP_STATE_FINISH = 5;

    static function getConstants() {
        $oClass = new \ReflectionClass('\\App\\Constants\\Constant');
        return $oClass->getConstants();
    }
}
