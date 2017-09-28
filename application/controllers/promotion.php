<?php
/**
 * Class 
 */
class Promotion extends Controller
{
   private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->promotion_model = $this->loadModel('Promotion',$this->params);
           
    }



     public function create()
    {
     
        //return $this->promotion_model->create();

    }

     public function read()
    {
     
        //return $this->promotion_model->read();

    }

     public function readall()
    {
     
        //return $this->promotion_model->readall();

    }

     public function update()
    {
     
        //return $this->promotion_model->update();

    }

    
     public function delete()
    {
     
        //return $this->promotion_model->delete();

    }




}
