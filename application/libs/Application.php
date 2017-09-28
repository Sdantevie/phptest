<?php

/**
 * Class Application
 * The heart of the app
 */
class Application
{
    /** @var null The controller part of the URL */
    private $controller;
    /** @var null The method part (of the above controller) of the URL */
    private $action;

    private $app;

    private $params;

    private $files;

    private $app_id;

    private $app_key;
    /**
     * Starts the Application
     * Takes the parts of the URL and loads the according controller & method
     * TODO: get rid of deep if/else nesting
     */
    public function __construct($app)
    {   
        $this->params = array();
        $this->files = array();
        $this->app = $app;
      try{
         $this->getParams();
        //check first if the app id and api key exists
        if(!isset($this->app[$this->app_id])) {

            throw new Exception('Error Processing Request. Invalid Application.');
         }else{
            if($this->app[$this->app_id] != $this->app_key) {
                throw new Exception('Error Processing Request. Invalid Applicationx.');
            }
         }

        // check for controller: is the controller NOT empty ?
        if ($this->controller) { 
            // check for controller: does such a controller exist ?
            if (file_exists(CONTROLLER_PATH . $this->controller . '.php')) {
                // if so, then load this file and create this controller
                // example: if controller would be "car", then this line would translate into: $this->car = new car();
                require CONTROLLER_PATH . $this->controller . '.php';
                $this->controller = new $this->controller($this->params);

                // check for method: does such a method exist in the controller ?
                if ($this->action) {

                    if (method_exists($this->controller, $this->action)) {
                        // call the method
                        $result['data'] = $this->controller->{$this->action}($this->params);
                        $result['success'] = true;
                      
                     } else {
                      throw new Exception('invalid Action.');
                    }

                } else {
                    
                    throw new Exception("Error Processing Request. Invalid Action");
                }
            // obviously mistyped controller name, therefore throw an error
            } else {

            throw new Exception("Error Processing Request. Invalid Controller");
            
              }
        // if controller is empty, simply show the main page (index/index)
          } else {
               
               throw new Exception("Error Processing Request. Invalid Controller");
            }


         } catch( Exception $e ) {
          //catch any exceptions and report the problem
          $result = array();
          $result['success'] = false;
          $result['errormsg'] = $e->getMessage();
      }


    echo  JsonHandler::encode($result);
    exit();

    }

    /**
     * Gets and splits the URL
     */
    // private function splitUrl()
    // {
    //     if (isset($_GET['url'])) {

    //         // split URL
    //         $url = rtrim($_GET['url'], '/');
    //         $url = filter_var($url, FILTER_SANITIZE_URL);
    //         $url = explode('/', $url);

    //         // Put URL parts into according properties
    //         // By the way, the syntax here if just a short form of if/else, called "Ternary Operators"
    //         // http://davidwalsh.name/php-shorthand-if-else-ternary-operators
    //         $this->controller = (isset($url[0]) ? $url[0] : null);
    //         $this->action = (isset($url[1]) ? $url[1] : null);
    //         $this->app_id = (isset($url[2]) ? $url[2] : null);
    //         $this->api_key = (isset($url[3]) ? $url[3] : null);
    //     }
    // }


    private function getParams()
    {
   
      if (!empty($_REQUEST)) {
         $this->params = isset($_REQUEST)? $_REQUEST : array();

        if(isset($_FILES)){
         $this->params = isset($_FILES)? $this->params + $_FILES : $this->params + array();
        }

        $this->controller = (isset($this->params['controller']) ? $this->params['controller'] : null);
        $this->action = (isset($this->params['action']) ? $this->params['action'] : null);
        $this->app_key = (isset($this->params['app_key']) ? $this->params['app_key'] : null);
        $this->app_id = (isset($this->params['app_id']) ? $this->params['app_id'] : null);
        
      }else{
        throw new Exception("Error Processing Request", 1);
      }
            
    }


   


}
