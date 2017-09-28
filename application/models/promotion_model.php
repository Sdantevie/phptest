<?php


class PromotionModel
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     * TBL_PROMOTION
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->db->table_name =TBL_PROMOTION;
        $this->time = time();
        $this->promotionImageUrl = API_URL."/data/promotion/img/";
        $this->promotionImagePath = "data/promotion/img/";
    }



  

 }

?>