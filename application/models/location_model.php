<?php


class LocationModel
{
    
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     * TBL_COUNTRIES
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->time = time();
    }


  

 }

?>