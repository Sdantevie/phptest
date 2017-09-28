<?php
/**
 * Class 
 */
class Coupon extends Controller
{
   private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->coupon_model = $this->loadModel('Coupon',$this->params);
           
    }



     public function create()
    {
     
        //return $this->coupon_model->create();

    }

     public function read()
    {
     
        //return $this->coupon_model->read();

    }

     public function readall()
    {
     
        //return $this->coupon_model->readall();

    }

     public function update()
    {
     
        //return $this->coupon_model->update();

    }

    
     public function delete()
    {
     
        //return $this->coupon_model->delete();

    }




}
