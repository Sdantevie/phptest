<?php


class SettingsModel extends Controller
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     * TBL_SETTINGS
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->db->table_name =TBL_SETTINGS;
        $this->time = time();
    }



    public function fetch_required()
     {
         $arr2 = array(); $arr3 = array(); $arr4 = array();
         $this->db->table_name =TBL_BANKS;
         $banks = $this->db->find_all('BankID','DESC');
         if($banks){
           $arr2 = $banks;
          }

        $this->db->table_name =TBL_COUNTRIES;
         $countries = $this->db->find_all('CountryID','ASC');
         if($countries){
           $arr3 = $countries;
          }

        $this->db->table_name =TBL_STATES;
         $states = $this->db->find_all('StateID','ASC');
         if($states){
           $arr4 = $states;
          }

          $arr_merge = array_merge((array) array('banks'=>$arr2),array('countries'=>$arr3),array('states'=>$arr4));
  
          return (object)  $arr_merge;

     }

    
  

 }

?>