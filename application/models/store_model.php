<?php


class StoreModel
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     * TBL_STORES
     * TBL_STORES_FILES
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->db->table_name =TBL_STORES;
        $this->time = time();
        $this->storeImageUrl = API_URL."/data/store/img/";
        $this->storeImagePath = "data/store/img/";



  

 }

?>