<?php
/**
 * Class 
 */
class Mailing extends Controller
{
   private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->mailing_model = $this->loadModel('Mailing',$this->params);
           
    }



     public function create()
    {
     
        //return $this->mailing_model->create();

    }

     public function read()
    {
     
        //return $this->mailing_model->read();

    }

     public function readall()
    {
     
        //return $this->mailing_model->readall();

    }

     public function update()
    {
     
        //return $this->mailing_model->update();

    }

    
     public function delete()
    {
     
        //return $this->mailing_model->delete();

    }




}
