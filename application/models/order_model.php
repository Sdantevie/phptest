<?php


class OrderModel
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     * TBL_ORDERS
     * TBL_ORDER_DETAILS
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->db->table_name =TBL_ORDERS;
        $this->time = time();
        $this->orderImageUrl = API_URL."/data/order/img/";
        $this->orderImagePath = "data/order/img/";
    }





 //  private function _check_slug($parameter){
 //    $key = '';
 //    $i = 0;
 //    if($this->db->exists('slug', $parameter) == 1){
 //      $i++;
 //      $key = $i.Utility::get_key(2);
 //        while($this->db->exists('slug', $parameter.$key) == 1){
 //          $i++;
 //          $key = $i.Utility::get_key(2);
 //         }
 //    }
 //    $slug = ($i == 0) ? $parameter : $parameter.$key;
 //    return $slug;
 //    }
  
  

 }

?>