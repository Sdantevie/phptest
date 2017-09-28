<?php
/**
 * Class 
 */
class Store extends Controller
{
    private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->store_model = $this->loadModel('Store',$this->params);
           
    }

     public function create()
    {
     
        //return $this->store_model->create();

    }

     public function read()
    {
     
        //return $this->store_model->read();

    }

     public function readall()
    {
     
        //return $this->store_model->readall();

    }

     public function update()
    {
     
        //return $this->store_model->update();

    }

    
     public function delete()
    {
     
        //return $this->store_model->delete();

    }




}
