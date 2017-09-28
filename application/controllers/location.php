<?php
/**
 * Class 
 */
class Location extends Controller
{
   private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->location_model = $this->loadModel('Location',$this->params);
           
    }



     public function create()
    {
     
        //return $this->location_model->create();

    }

     public function read()
    {
     
        //return $this->location_model->read();

    }

     public function readall()
    {
     
        //return $this->location_model->readall();

    }

     public function update()
    {
     
        //return $this->location_model->update();

    }

    
     public function delete()
    {
     
        //return $this->location_model->delete();

    }




}
