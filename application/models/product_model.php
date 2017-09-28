<?php


class ProductModel
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     * TBL_PRODUCTS
     * TBL_PRODUCTS_FILES
     * TBL_PRODUCT_CATEGORIES
     * TBL_PRODUCT_OPTIONS
     * TBL_OPTIONS
     * TBL_OPTION_GROUPS
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->db->table_name =TBL_STORES;
        $this->time = time();
        $this->productImageUrl = API_URL."/data/product/img/";
        $this->productImagePath = "data/product/img/";
        $this->categoryImageUrl = API_URL."/data/category/img/";
        $this->categoryImagePath = "data/category/img/";
    }



  

 }

?>