<?php
/**
 * Class 
 */
class Settings extends Controller
{
    private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->settings_model = $this->loadModel('Settings',$this->params);
           
    }



     public function create()
    {
     
        //return $this->settings_model->create();

    }

     public function read()
    {
     
        //return $this->settings_model->read();

    }

     public function readall()
    {
     
        //return $this->settings_model->readall();

    }

     public function update()
    {
     
        //return $this->settings_model->update();

    }

    
     public function delete()
    {
     
        //return $this->settings_model->delete();

    }

    
     public function fetch_required()
    {
     
        return $this->settings_model->fetch_required();

    }





}
