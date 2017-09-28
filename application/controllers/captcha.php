<?php

/**
 * Captcha Controller
 * Controls the Captcha processes
 */

class Captcha extends Controller
{
    private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
       
    }

    /**
     * Generate a captcha, write the characters into $_SESSION['captcha'] and returns a real image which will be used
     * like this: <img src="......./login/showCaptcha" />
     * IMPORTANT: As this action is called via <img ...> AFTER the real application has finished executing (!), the
     * SESSION["captcha"] has no content when the application is loaded. The SESSION["captcha"] gets filled at the
     * moment the end-user requests the <img .. >
     * If you don't know what this means: Don't worry, simply leave everything like it is ;)
     */
    function showCaptcha()
    {
        $captcha_model = $this->loadModel('Captcha');
        $captcha_model->generateCaptcha();
    }

    function showCaptchaText()
    {
        $captcha_model = $this->loadModel('Captcha');
        $captcha_model->generateCaptchaText();
    }
}
