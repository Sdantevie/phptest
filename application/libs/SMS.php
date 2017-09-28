<?php

class SMS {

public static $username = SMS_USERNAME;
public static $password= SMS_PASSWORD;
public static $sender= APP_NAME;

/**
     * send the OTP
     * @param object $user
     * @param string $otp
     * @return bool success status
     */
    public static function sendOTPMessage($user,$otp)
    {
       
        $to_name = $user->UserFirstName;
        $to_phone = $user->UserPhone;
        

         // FOR HTML ##############################
        $message = 'Your rquested OTP is: ' . $otp.' Please use pin within 1 hour.';
        $body = "Hello ".$to_name.", \n".$message;
        $api = SMS_API.'?username='.SMS_USERNAME.'&password='.SMS_PASSWORD.'&sender='.urlencode(APP_NAME).'&recipient='.$to_phone.'&message='.urlencode($body).'';
        $fp = fopen($api,'r');
        $result = fread($fp, 1024);
        
        return $result;
    }




 public static function sendMessage($user,$message='')
    {
        $to_name = $user->fullname;
        $to_phone = $user->phone;
        
        $body = "Hello ".$to_name.", \n".$message;
        $api = SMS_API.'?username='.SMS_USERNAME.'&password='.SMS_PASSWORD.'&sender='.urlencode(APP_NAME).'&recipient='.$to_phone.'&message='.urlencode($body).'';
        $fp = fopen($api,'r');
        $result = fread($fp, 1024);
        
        return $result;
    }



     

}

?>