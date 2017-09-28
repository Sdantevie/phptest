<?php
/**
 * Class 
 */
class Product extends Controller
{
    private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->product_model = $this->loadModel('Product',$this->params);
           
    }

     public function create()
    {
     
        //return $this->product_model->create();

    }

     public function read()
    {
     
        //return $this->product_model->read();

    }

     public function readall()
    {
     
        //return $this->product_model->readall();

    }

     public function update()
    {
     
        //return $this->product_model->update();

    }

    
     public function delete()
    {
     
        //return $this->product_model->delete();

    }




}
