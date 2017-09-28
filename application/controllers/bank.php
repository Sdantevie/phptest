<?php
/**
 * Class 
 */
class Bank extends Controller
{
    private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
   function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->bank_model = $this->loadModel('Bank',$this->params);
           
    }



     public function create()
    {
     
        //return $this->bank_model->create();

    }

     public function read()
    {
    
        //return $this->bank_model->read();

    }

     public function readall()
    {
     
        //return $this->bank_model->readall();

    }

     public function update()
    {
     
        //return $this->bank_model->update();

    }

    
     public function delete()
    {
     
        //return $this->bank_model->delete();

    }




}
