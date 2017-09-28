<?php
/**
 * Class 
 */
class Order extends Controller
{
    private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->order_model = $this->loadModel('Order',$this->params);
           
    }

     public function create()
    {
     
        //return $this->order_model->create();

    }

     public function read()
    {
     
        //return $this->order_model->read();

    }

     public function readall()
    {
     
        //return $this->order_model->readall();

    }

     public function update()
    {
     
        //return $this->order_model->update();

    }

    
     public function delete()
    {
     
        //return $this->order_model->delete();

    }




}
