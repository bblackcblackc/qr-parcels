<?php

define("DB_HOST","localhost");
define("DB_RO_LOGIN","qr_ro");
define("DB_RO_PASSWORD","bcNhsCsAWI9rlK3H");
define("DB_RW_LOGIN","qr_rw");
define("DB_RW_PASSWORD","6Kx5ddYmaQ9A6K6o");
define("DB_NAME","qr_parcels");

define("DB_USERS_TABLE","users");
define("DB_COURIERS_TABLE","couriers");
define("DB_PARCELS_TABLE","parcels");
define("DB_EVENT_TABLE","history");
define("DB_DOCUMENTS_TABLE","documents");
define("DB_OPERATIONS_TABLE","operations");
define("DB_REGISTRATIONS_TABLE","registration");
define("DB_TARIFFS_TABLE","tariffs");
define("DB_FINOPERATIONS_TABLE","fin_history");

define("USER_OK",0);
define("USER_NO_PARAMS",-1);
define("USER_DB_ERROR",-2);
define("USER_EXISTS",-3);
define("USER_NO_AUTH",-4);
define("USER_NOT_FOUND",-5);

define("PARCEL_OK",0);
define("PARCEL_NO_PARAMS",-1);
define("PARCEL_DB_ERROR",-2);
define("PARCEL_EXISTS",-3);
define("PARCEL_NOT_FOUND",-5);

define("OPERATION_NO_OPERATION",0);
define("OPERATION_PARCEL_COURIER_ASSIGN",1);
define("OPERATION_PARCEL_COURIER_TO_COURIER",2);
define("OPERATION_PARCEL_FROM_USER",3);
define("OPERATION_PARCEL_TO_USER",4);
define("OPERATION_PARCEL_TO_COURIER",5);
define("OPERATION_PARCEL_FROM_COURIER",6);
define("OPERATION_PARCEL_INFO",7);

define("MAIL_REG_FROM","no-reply@eksel.su");
define("PASS_REG_FROM","cJ1efnoSHU");
define("HOST_REG_FROM","eksel.su");
define("MAILER","ssl://smtp.yandex.ru");
define("MAILER_PORT",465);
define("MAIL_HEADERS","MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\nFrom: " . HOST_REG_FROM . " регистрация <" . MAIL_REG_FROM . ">");
define("MAIL_SUBJECT","Регистрация на сервисе " . HOST_REG_FROM);
define("MAIL_TEXT","Здравствуйте.\r\n\r\nПожалуйста, не отвечайте на это письмо.\r\n\r\nКто-то (возможно, Вы) подали заявку на регистрацию " .
					"в сервисе eksel.su. Если это были не Вы -- просто проигнорируйте это письмо. Чтобы завершить регистрацию -- " .
					"пройдите по ссылке ");

define("CALC_API_BASE_URL","http://calc.eksel.su/1/");

?>
