<?php


class BankModel
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     * TBL_BANKS
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->db->table_name =TBL_BANKS;
        $this->time = time();
    }



    
  

 }

?>