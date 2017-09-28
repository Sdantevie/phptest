<?php 
/**
 *
 * The Mailer class is meant to simplify the task of sending
 * emails to users. Note: this email system will not work
 * if your server is not setup to send mail.
 *
 * If you are running Windows and want a mail server, check
 * out this website to see a list of freeware programs:
 * <http://www.snapfiles.com/freeware/server/fwmailserver.html>
 *
 * Written by: David Oti. Fl Studio mentor
 * Last Updated: November 19, 2016
 */
 
class Mailer
{

    private $time;

     /**
     * Constructor
     */
    public function __construct()
    {
        $this->time = time();
    }



  // public static function sendVerificationEmail($user)
  //   { 
  //       $to =  $user->email;
  //       $subject = EMAIL_VERIFICATION_SUBJECT;   
  //       $to_name = !empty($user->username)? $user->username : $user->email;
  //       // FOR HTML ##############################
  //       $html = EMAIL_VERIFICATION_CONTENT_HTML . EMAIL_VERIFICATION_URL . '/' . urlencode($user->id) . '/' . urlencode($user->activation_hash);
  //       $replace = array("{APP_NAME}", "{APP_URL}", "{APP_NUMBER}", "{APP_ADDRESS}", "{TO_NAME}", "{BODY}", "{COMPANY}");
  //       $with = array(APP_NAME,APP_URL,APP_NUMBER,APP_ADDRESS,$to_name, $html,COMPANY);

  //       $template = file_get_contents('data/email/template.html');
  //       $body = str_replace($replace, $with, $template);

  //       // Always set content-type when sending HTML email
  //       $headers = "MIME-Version: 1.0" . "\r\n";
  //       $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  //       // More headers
  //       $headers .= 'From: <'.APP_NAME.'>' . "\r\n";
  //       $headers .= 'Cc: info@gistpay.com' . "\r\n";

  //       $result = mail($to,$subject,$body,$headers);

    
  //       //$mail->AltBody = "Hello ".$to_name.", \n".EMAIL_VERIFICATION_CONTENT . EMAIL_VERIFICATION_URL . '/' . urlencode($user->id) . '/' . urlencode($user->activation_hash);

  //       // final sending and check
  //       return $result;
  //   }

     public static function sendVerificationEmail($user)
    { 
        $to =  $user->email;
        $subject = EMAIL_VERIFICATION_SUBJECT;   
       $to_name = !empty($user->fullname)? $user->fullname : $user->username;
       
        $body = "Hello ".$to_name.", \n".EMAIL_VERIFICATION_CONTENT . EMAIL_VERIFICATION_URL . '/' . urlencode($user->id) . '/' . urlencode($user->activation_hash);
        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // More headers
        $headers .= 'From: <'.APP_NAME.'>' . "\r\n";

        try {
          $result = mail($to,$subject,$body,$headers);
        } catch (Exception $e) {
            throw new Exception($e, 1);
            
        }
        // final sending and check
        return $result;
    }

 
     public static function sendWelcomeEmail($user)
    { 
        $to =  $user->email;
        $subject = 'Well done, buddy!';   
        $to_name = !empty($user->fullname)? $user->fullname : $user->username;
       
        $body = "Hello ".$to_name.", \n"." Now you can start using ".APP_NAME." to provide and get help.
           If you have any questions, feel free to ask.";
        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // More headers
        $headers .= 'From: <'.APP_NAME.'>' . "\r\n";
        try {
          $result = mail($to,$subject,$body,$headers);
        } catch (Exception $e) {
            throw new Exception($e, 1);
            
        }
        // final sending and check
        return $result;

        // final sending and check
        return $result;
    }




  /**
     * send the OTP
     * @param object $user
     * @param string $otp
     * @return bool success status
     */
    public static function sendOTPMail($user,$otp)
    {
       
        $to =  $user->email;
        $subject = 'OTP Request';   
        $to_name = !empty($user->fullname)? $user->fullname : $user->username;
       
        $body = "Hello ".$to_name.", \n".' Your rquested OTP is: ' . $otp.' Please use pin within 15 minutes.';
        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // More headers
        $headers .= 'From: <'.APP_NAME.'>' . "\r\n";
        
        try {
          $result = mail($to,$subject,$body,$headers);
        } catch (Exception $e) {
            throw new Exception($e, 1);
            
        }
        // final sending and check
        return $result;
    }


     
     /**
     * send customized email
     * @param object $user
     * @param string $subject
     * @param string $message
     * @return bool success status
     */
    public static function sendMail($user,$subject,$message,$attachment=false)
    {

        $to =  $user->email;
        $subject = $subject; 
        $to_name = !empty($user->fullname)? $user->fullname : $user->email;
       
        $body = "Hello ".$to_name.", \n".$message;
        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // More headers
        $headers .= 'From: <'.APP_NAME.'>' . "\r\n";
        
          try {
          $result = mail($to,$subject,$body,$headers);
        } catch (Exception $e) {
            throw new Exception($e, 1);
            
        }
        // final sending and check
        return $result;
    }



   
}


?>
