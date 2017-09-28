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

  
  private static function _configureTemplate($to_name,$html,$tpl,$avatar_num)
  {    
      $avatar = array('c_10'=>AVATAR_ONE,'c_1'=>AVATAR_TWO);
      $replace = array("[[CompanyAvatar]]","[[ContactEmail]]","[[LOGO]]","[[CompanyName]]", "[[CompanyUrl]]", "[[ContactNumbers]]", "[[ContactAddress]]", "[[FullName]]", "[[BODY]]");
      $with = array($avatar[$avatar_num],APP_EMAIL,APP_LOGO,APP_NAME,APP_URL,APP_NUMBER,APP_ADDRESS,$to_name, $html);
      $template = file_get_contents('data/email/'.$tpl);
      $body = str_replace($replace, $with, $template);
      return $body;
  }
    
  private static function _configureSMTP()
  {
    // create PHPMailer object (this is easily possible as we auto-load the according class(es) via composer)
    $smtp = false;
    $mail = new PHPMailer;
    if ($smtp == true) {
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = "mail.adonica.ng";
        $mail->Port = 25;
        $mail->Username = 'hello@adonica.ng';
        $mail->Password = '07034437977';
    }else{
        $mail->IsMail();
    }
    return $mail;
  }

  public static function sendVerificationEmail($user)
    { 
        // create PHPMailer object (this is easily possible as we auto-load the according class(es) via composer)
        $mail = self::_configureSMTP();
        // build the email
        $mail->From = APP_EMAIL;
        $mail->FromName = APP_NAME;
        $mail->AddAddress($user->UserEmail);
        $mail->Subject = EMAIL_VERIFICATION_SUBJECT;

       $to_name = !empty($user->UserFirstName)? $user->UserFirstName : $user->UserName;

         // FOR HTML ##############################
        $html = '<b>Verification By Link</b> <br>'.EMAIL_VERIFICATION_CONTENT_HTML . EMAIL_VERIFICATION_URL. '/' . urlencode($user->UserID) . '/' . urlencode($user->UserVerificationHash).' <br> If the link is not clickable, click on: <a href="'.EMAIL_VERIFICATION_URL. '/' . urlencode($user->UserID) . '/' . urlencode($user->UserVerificationHash).'">Verify Account</a> or copy and paste the link into your browser address bar and submit.<br><br><b>Verification By Code</b><br> Use code:  '.$user->UserVerificationCode.' to verify your account.';
        ###########################################

         $mail->Body = self::_configureTemplate($to_name,$html,'general.tpl.html','c_10');

         $mail->AltBody = "Hello ".$to_name.",\n Verification By Link: \n".EMAIL_VERIFICATION_CONTENT . EMAIL_VERIFICATION_URL. '/' . urlencode($user->UserID) . '/' . urlencode($user->UserVerificationHash)." If the link is not clickable, copy and paste the link into your browser address bar and submit. \n\nVerification By Code: \n Use code: ".$user->UserVerificationCode.' to verify your account.';

        // final sending and check
        $result = $mail->Send();
        return $result;
    }



 
     public static function sendWelcomeEmail($user)
    { 

        // create PHPMailer object (this is easily possible as we auto-load the according class(es) via composer)
        $mail = self::_configureSMTP();
        // build the email
        $mail->From = APP_EMAIL;
        $mail->FromName = APP_NAME;
        $mail->AddAddress($user->UserEmail);
        $mail->Subject = 'Well done, buddy!';

       $to_name = !empty($user->UserFirstName)? $user->UserFirstName : $user->UserName;

         // FOR HTML ##############################
        $html = 'We`re really excited you`ve decided to give us a try. In case you have any questions, feel free to reach out to us at '.APP_EMAIL;
        ###########################################

        $mail->Body = $mail->Body = self::_configureTemplate($to_name,$html,'welcome.tpl.html','c_10');

         $mail->AltBody = "Hello ".$to_name.", \n".$html;

        // final sending and check
        $result = $mail->Send();
        return $result;

    }


  

      public static function sendNewsletterSubscription($user)
    { 
        // create PHPMailer object (this is easily possible as we auto-load the according class(es) via composer)
        $mail = self::_configureSMTP();
        // build the email
        $mail->From = APP_EMAIL;
        $mail->FromName = APP_NAME;
        $mail->AddAddress($user->MailingListEmail);
        $mail->Subject = 'Confirm your NewLetter Request';

       $to_name = $user->MailingListEmail;

         // FOR HTML ##############################
        $html = 'Please confirm your newsletter request by clicking on the link: '. APP_URL. '/subscribe_newsletter/' . urlencode($user->MailingListID) . '/' . urlencode($user->MailingListSlug).' <br> If the link is not clickable, click on: <a href="'.APP_URL. '/subscribe_newsletter/'. urlencode($user->MailingListID) . '/' . urlencode($user->MailingListSlug).'">Confirm request</a> or copy and paste the link into your browser address bar and submit.';
        ###########################################

         $mail->Body = self::_configureTemplate($to_name,$html,'multiple.tpl.html','c_10');

         $mail->AltBody = "Hello ".$to_name.",\n Please confirm your newsletter request by clicking on the link:".APP_URL. '/subscribe_newsletter/'. urlencode($user->MailingListID) . '/' . urlencode($user->MailingListSlug).' If the link is not clickable, copy and paste the link into your browser address bar and submit.';

        // final sending and check
        $result = $mail->Send();
        return $result;
    }



    public static function sendNewsletterWelcome($user)
    { 

        // create PHPMailer object (this is easily possible as we auto-load the according class(es) via composer)
        $mail = self::_configureSMTP();
        // build the email
        $mail->From = APP_EMAIL;
        $mail->FromName = APP_NAME;
        $mail->AddAddress($user->MailingListEmail);
        $mail->Subject = 'Well done, buddy!';

       $to_name = $user->MailingListEmail;

         // FOR HTML ##############################
        $html = 'We`re really excited you`ve decided to receive our news letter. In case you have any questions, feel free to reach out to us at '.APP_EMAIL.' or you can unsubscribe by simply clicking this link: '. APP_URL. '/unsubscribe_newsletter/' . urlencode($user->MailingListID) . '/' . urlencode($user->MailingListSlug).' <br> If the link is not clickable, click on: <a href="'.APP_URL. '/unsubscribe_newsletter/'. urlencode($user->MailingListID) . '/' . urlencode($user->MailingListSlug).'">unsubscribe</a> or copy and paste the link into your browser address bar and submit.';
        ###########################################

        $mail->Body = $mail->Body = self::_configureTemplate($to_name,$html,'multiple.tpl.html','c_10');

         $mail->AltBody = "Hello ".$to_name.", \n".$html;

        // final sending and check
        $result = $mail->Send();
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
        // create PHPMailer object here. This is easily possible as we auto-load the according class(es) via composer
        $mail = self::_configureSMTP();
        // build the email
        $mail->From = APP_EMAIL;
        $mail->FromName = APP_NAME;
        $mail->AddAddress($user->UserEmail);
        $mail->Subject = 'OTP Request';

        $to_name = !empty($user->UserFirstName)? $user->UserFirstName : $user->UserName;

         // FOR HTML ##############################
       $html = 'Your rquested OTP is: ' . $otp.' Please use pin within 15 minutes.';
        ###########################################

        $mail->Body = $mail->Body = self::_configureTemplate($to_name,$html,'multiple.tpl.html','c_1');

         $mail->AltBody = "Hello ".$to_name.", \n".$html;

        // final sending and check
        $result = $mail->Send();
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
        // create PHPMailer object (this is easily possible as we auto-load the according class(es) via composer)
        $mail = self::_configureSMTP();
        // fill mail with data
        $mail->From = APP_EMAIL;
        $mail->FromName = APP_NAME;
        $mail->Subject = $subject;
      
        $to_name = !empty($user->UserFirstName)? $user->UserFirstName : $user->UserName;


       // FOR HTML ##############################
        $html = $message;

        // FOR PLAIN TEXT ##########################
        $text_body =  "Hello ".$to_name.", \n".$html;
        ############################################

        $mail->Body    = self::_configureTemplate($to_name,$html,'multiple.tpl.html','c_1');
        $mail->AltBody = $text_body;
        $mail->AddAddress($user->UserEmail);



        if(isset($attachment)){
        $mail->addAttachment($attachment);
         }
        // final sending and check
        $result = $mail->Send();
        return $result;
    }





     /**
     * send customized email
     * @param object $user
     * @param string $subject
     * @param string $message
     * @return bool success status
     */
    public static function sendMultipleMail($emails,$subject,$message,$attachment=false)
    {
        // create PHPMailer object (this is easily possible as we auto-load the according class(es) via composer)
        $mail = self::_configureSMTP();
        // fill mail with data
        $mail->From = APP_EMAIL;
        $mail->FromName = APP_NAME;
        $mail->Subject = $subject;
      

       foreach ($emails as $email) {

        $to_name = $email;
         // FOR HTML ##############################
        $html = $message;

        // FOR PLAIN TEXT ##########################
        $text_body =  "Hello ".$to_name.", \n".$html;
        ############################################

        $mail->Body  = self::_configureTemplate($to_name,$html,'multiple.tpl.html','c_1');
        $mail->AltBody = $text_body;
        $mail->AddAddress($email);
       
        if(isset($attachment)){
        $mail->addAttachment($attachment);
         }
        // final sending and check
        $result = $mail->Send();
         // Clear all addresses and attachments for next loop
        $mail->clearAddresses();
        $mail->clearAttachments();
        
        }
        

        return $result;
    }

   
}


?>
