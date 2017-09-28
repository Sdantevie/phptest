<?php

/**
 * CaptchaModel
 *
 *
 */
use Gregwar\Captcha\CaptchaBuilder;

class CaptchaModel
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     */
    public function __construct()
    {
    }

  
    /**
     * Generates the captcha, "returns" a real image,
     * this is why there is header('Content-type: image/jpeg')
     * Note: This is a very special method, as this is echoes out binary data.
     * Eventually this is something to refactor
     */
    public function generateCaptcha()
    {
        // create a captcha with the CaptchaBuilder lib
        $builder = new CaptchaBuilder;
        $builder->build();

        // Session::init();
        // Session::set('captcha', $builder->getPhrase());
        // write the captcha character into session
        $_SESSION['captcha'] = $builder->getPhrase();
        // render an image showing the characters (=the captcha)
        header('Content-type: image/jpeg');
        $builder->output();
    }


     public function generateCaptchaText()
    {
        // create a captcha with the CaptchaBuilder lib
        $builder = new CaptchaBuilder;
        $builder->build();
        return $builder->getPhrase();
       
    }

   

  
}
