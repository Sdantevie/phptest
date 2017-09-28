<?php

/**
 * Configuration
 *
 * For more info about constants please @see http://php.net/manual/en/function.define.php
 * If you want to know why we use "define" instead of "const" @see http://stackoverflow.com/q/2447791/1114320
 */

/**
 * Configuration for: Error reporting
 * Useful to show every little problem during development, but only show hard errors in production
 */
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);
//date_default_timezone_set('Etc/UTC');
date_default_timezone_set('Africa/Lagos');

define('FALLBACK_TIMEZONE','Etc/UTC');

header("Access-Control-Allow-Origin: *");
//header("Access-Control-Allow-Origin: http://www.eshop.com");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Content-Type: application/json; charset=UTF-8;");
header('Access-Control-Allow-Headers: X-Requested-With, X-PINGOTHER, Content-Type');



define('APP_EXT','.co');
define('APP_NAME','Eshop');
define('COMPANY','Eshop');
define('APP_URL','https://www.eshop'.APP_EXT);
define('API_URL','http://api.eshop'.APP_EXT);
define('APP_EMAIL','support@eshop.co');
define('APP_NUMBER','+(234) 8037130448');
define('APP_ADDRESS','Ihundaa Plaza (1st Mechanic) junction, Choba extension, East-West Road, Rivers State, Nigeria.');
define('APP_TITLE','Welcome to Nigeria`s Number One Online Shopping Mall.');
define('APP_DESCRIPTION','We have the best prices and we deliver on time. Browse through our store for a range of products in Fashion, Electronics and everyday Home and Kitchen Products');
define('APP_POSTER',APP_URL.'/public/img/3.jpg');
define('APP_LOGO',APP_URL.'/public/img/logo.png');
define('AVATAR_ONE',APP_URL.'/public/img/c_10.png');
define('AVATAR_TWO',APP_URL.'/public/img/c_1.png');
define('APP_ID','marketdroplet');
define('APP_KEY','ea96b3420dc5e962c2ced1a5e962c2894');
define('SMS_API', 'http://smsmobile24.com/components/com_spc/smsapi.php');
define('SMS_USERNAME', '');
define('SMS_PASSWORD', '');





/**
 * Configuration for: Folders
 * Here you define where your folders are. Unless you have renamed them, there's no need to change this.
 */
define('LIBS_PATH', 'application/libs/');
define('CONTROLLER_PATH', 'application/controllers/');
define('MODELS_PATH', 'application/models/');
define('DATA_PATH', 'data/');
define('DATABASE_PATH', 'data/database/');


// the hash cost factor, PHP's internal default is 10. You can leave this line
// commented out until you need another factor then 10.
define("HASH_COST_FACTOR", "10");


##################################### DATABASE SETTINGS FOR MYSQL ###############################
define('DB_TYPE', 'mysql');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'eshop');
define('DB_USER', 'root');
define('DB_PASS', '');

// define('DB_TYPE', 'mysql');
// define('DB_HOST', '127.0.0.1');
// define('DB_NAME', 'eshopc_eshop');
// define('DB_USER', 'eshopc_eshop');
// define('DB_PASS', '07034437977');

//module
define("TBL_USERS", "d_users");
define("TBL_USER_SESSION", "d_user_session");
define("TBL_USER_OTP", "d_user_otp");
define("TBL_USER_FILES", "d_user_files");
define("TBL_USER_REFERRALS", "d_user_referral");
define("TBL_USER_WALLET_HISTORY", "d_user_wallet_history");
define("TBL_USER_WISHLIST", "d_user_wishlist");
define("TBL_USER_BANKS", "d_user_banks");

//module
define("TBL_STORES", "d_stores");
define("TBL_STORES_FILES", "d_stores_files");
//moduel
define("TBL_PRODUCTS", "d_products");
define("TBL_PRODUCT_CATEGORIES", "d_product_categories");
define("TBL_PRODUCT_FILES", "d_product_files");
define("TBL_PRODUCT_OPTIONS", "d_product_options");
define("TBL_OPTIONS", "d_options");
define("TBL_OPTION_GROUPS", "d_option_groups");
//moduel
define("TBL_ORDERS", "d_orders");
define("TBL_ORDER_DETAILS", "d_order_details");

//module
define("TBL_MAILING_LIST", "d_mailing_list");
//module
define("TBL_PROMOTION", "d_promotion");
//module
define("TBL_COUPONS", "d_coupons");
//module
define("TBL_COUNTRIES", "d_countries");
define("TBL_STATES", "d_states");
//module
define("TBL_BANKS", "d_banks");
//module
define("TBL_SETTINGS", "d_settings");




define('COOKIE_RUNTIME', 1209600);
// the domain where the cookie is valid for, for local development ".127.0.0.1" and ".localhost" will work
// IMPORTANT: always put a dot in front of the domain, like ".mydomain.com" !
//define('COOKIE_DOMAIN', '.');


/**
* Configuration
* is not yet set
*
**/

define('OTP_TYPES', serialize(array(
						'signup',
						'password_reset',
						'phone_verification',
						'login',
						'withdraw')));


define('BLOCK_PERIOD','+3 days');
define('SESSION_EXPIRE_AFTER','+5 hours');


define("EMAIL_PASSWORD_RESET_URL", APP_URL ."/account/verify_password_reset");

define("EMAIL_PASSWORD_RESET_SUBJECT", "Password reset for ".APP_NAME);
define("EMAIL_PASSWORD_RESET_CONTENT", "Please click on this link to reset your password: ");

define("EMAIL_VERIFICATION_URL", APP_URL."/verify");

define("EMAIL_VERIFICATION_SUBJECT", "Activate your ".APP_NAME." account");

define("EMAIL_VERIFICATION_CONTENT", "I’m so glad you decided to join ".APP_NAME.". Verify the email address linked with your account by clicking on the link: ");

define("EMAIL_VERIFICATION_CONTENT_HTML", "I’m so glad you decided to join ".APP_NAME.". Verify the email address linked with your account by clicking on the link below: <br>");



/**
 * Configuration for: Error messages and notices
 *
 * In this project, the error messages, notices etc are all-together called "feedback".
 */
define("FEEDBACK_UNKNOWN_ERROR", "An unknown error occurred! Please try again later!. Contact us if error continues.");
