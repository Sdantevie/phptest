<?php


class TransactionsModel
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->db->table_name =TBL_TRANSACTIONS;
        $this->time = time();
        $this->ecurrencyIconUrl = API_URL."/data/ecurrency/icons/";
    }



	public function create()
	{ 

	  $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){
	      
         $this->db->table_name = TBL_USERS;

            $obj1 = $this->db->find('id',strip_tags($params['id']));
            $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;
            $ecurrency_id = !empty($params['ecurrency_id'])? Sanitize::cleanAll($params['ecurrency_id']) : false;
            $type = !empty($params['type'])? Sanitize::cleanAll($params['type']) : false;
  
           if($obj1){

              $this->_verifySession($obj1->session_id,$token);
              //check if the user is active
              $this->_checkUserStatus($obj1->status);
              
              $this->_validate($params,'create',$type);
              $this->_validate_amount($params,$type,$obj1);

              $this->db->table_name =TBL_ECURRENCY;
              $obj2 = $this->db->find('id',$ecurrency_id);

               if($obj2){

                $banks = unserialize(BANK_ACCOUNTS);
                $pay_to_bank = '';
                $pay_to_acc_name = '';
                $pay_to_acc_number = '';

                if(isset($params['pay_to'])){

                  if(array_key_exists($params['pay_to'], $banks)){

                    $pay_to_bank = $banks[$params['pay_to']]['bank'];
                    $pay_to_acc_name = $banks[$params['pay_to']]['acc_name'];
                    $pay_to_acc_number = $banks[$params['pay_to']]['acc_number'];

                    }
                 }

                   $this->db->table_name =TBL_TRANSACTIONS;
                   $slug = $this->_check_slug(Utility::generateNum());

              
                try{

                // begin the transaction
                 $this->db->beginTransaction();

                  $parameter = array(
                    'status' => 1,
                    'user_id' => $obj1->id,
                    'ecurrency_id' => $ecurrency_id,
                    'e_currency' => $obj2->name,
                    'type' => Sanitize::cleanAll($params['type']),
                    'amount' => Sanitize::cleanAll($params['D_amount']),
                    'pay_to_identifier' => $obj2->identifier,
                    'identifier' => Sanitize::cleanAll($params['D_identifier']),
                    'identifier_label' => $obj2->identifier_label,
                    'buy_rate' => $obj2->buy_rate,
                    'sell_rate' => $obj2->sell_rate,
                    'comment' => Sanitize::cleanAll($params['D_comment']),
                    'virtual_currency' => $obj2->virtual_currency,
                    'currency' => $obj2->currency,
                    'currency_rate' => $obj2->currency_rate,
                    'bank_name' => isset($params['bank'])? Sanitize::cleanAll($params['bank']) :false,
                    'acc_name' => isset($params['acc_name'])?  Sanitize::cleanAll($params['acc_name']) : false,
                    'acc_number' => isset($params['acc_number'])?  Sanitize::cleanAll($params['acc_number']) : false,
                    'acc_type' => isset($params['acc_type'])?  Sanitize::cleanAll($params['acc_type']) : false,
                    'slug' => $slug,
                    'ip' => Utility::getClientIP(),
                    'pay_to_bank' => $pay_to_bank,
                    'pay_to_acc_name' => $pay_to_acc_name,
                    'pay_to_acc_number' => $pay_to_acc_number,
                    'creation_timestamp' => $this->time,
                    'modification_timestamp' => $this->time
                       );
                      $this->db->table_name =TBL_TRANSACTIONS;
                      $this->db->create($parameter);

                  $more_details = '';
                  $worth = 0;
                  if($type == 'buy'){
                    $worth = ($obj2->buy_rate * $params['D_amount'] * $obj2->currency_rate);
                    $more_details = '<br><hr/>Please send the money to: <br>
                      Bank: '.$pay_to_bank.' <br>
                      Acc Name: '.$pay_to_acc_name.'
                      Acc Number: '.$pay_to_acc_number.'
                      Acc Type: Current
                      <br><hr />
                    ';
                  }elseif($type == 'sell'){
                    $worth = ($obj2->sell_rate * $params['D_amount'] * $obj2->currency_rate);
                    $more_details = '<br><hr />Please send the '.$obj2->name.' to: <br>
                      '.$obj2->identifier.' <br><hr />

                      IMPORTANT <br>
                      If you send to any other '.$obj2->identifier_label.' aside the one generated for you on the site and also contained here, your money will be lost and not payable by us. Do not send to the '.$obj2->identifier_label.' more than once. If you need to sell again, kindly place a new order.<br><hr />

                    ';
                  }

                  $subject ='Order Created Successfully';
                  $message = "
                    <br>
                   
                    PLEASE DO NOT REPLY THIS MAIL
                    
                    <br><hr />
                    You have requested to ".$type." ".$params['D_amount']." ".$obj2->virtual_currency." of ".$obj2->name." worth about ".Number::currency($worth,'NGN')."(This amount may vary with the final value which will be calculated upon verification by our system). Invoice number for this order: ".$slug.". <br> ".$more_details." This order was generated on ". Utility::unixdatetime_to_text($this->time).". <br><br> Please note that you can modify this order while its still pending and waiting to be locked for verification.";
                  Mailer::sendMail($obj1,$subject,$message,$attachment=false);

                    $subject = 'New Order - '.$slug.' - Created';
                    $message = "Request to ".$type." ".$params['D_amount']." ".$obj2->virtual_currency." of ".$obj2->name." worth about ".Number::currency($worth,'NGN')." was submitted by ".$obj1->email.". Order No: ".$slug.". <br> This order was generated on ". Utility::unixdatetime_to_text($this->time);
                    //initiate the notification
                    $this->_mailNotifications($subject,$message,$attachment=false);

                     $this->db->commit();

                     return $slug;

                     } catch (PDOException $e) {
                    // rollback update
                    $this->db->rollback();
                    throw $e;
                   }
                }else{ throw new Exception("Error Processing Request",3);  }
            }else{ throw new Exception("Error Processing Request",3);  }

      }else{ throw new Exception("Error Processing Request",3);  }


	  }
	

  private function _mailNotifications($subject='',$message='',$attachment=false)
  {
      $this->db->table_name = TBL_SETTINGS;
         $notification_emails = $this->db->find('param','notification_emails');
         if ($notification_emails) {
           if (!empty($notification_emails->value)) {
               $emails = unserialize($notification_emails->value);
               if(is_array($emails)){
                 Mailer::sendMultipleMail($emails,$subject,$message,$attachment=false);
               }
           }
         }
  }

   public function update()
   {
    
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

        $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
        $transaction_id = isset($params['transaction_id'])? Sanitize::cleanAll($params['transaction_id']) : false;
        $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;
        $type = !empty($params['type'])? Sanitize::cleanAll($params['type']) : false;


        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {
            $this->_verifySession($obj1->session_id,$token);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            $this->_validate($params,'update',$type);
            $this->_validate_amount($params,$type,$obj1);

            $this->db->table_name =TBL_TRANSACTIONS;
            $obj2 = $this->db->find('id',$transaction_id);

            if($obj2){

              if($obj2->user_id != $obj1->id){
                throw new Exception("Sorry you do not have access to this transaction.", 1);
                
                 }

              if($obj2->status > 1){
                throw new Exception("Sorry, you can not update this transaction again. Contact us if you feel this is wrong.", 1);
                
              }

              $t = $obj2;

              $banks = unserialize(BANK_ACCOUNTS);
                $pay_to_bank = $t->pay_to_bank;
                $pay_to_acc_name = $t->pay_to_acc_name;
                $pay_to_acc_number = $t->pay_to_acc_number;

                if(isset($params['pay_to'])){

                  if(array_key_exists($params['pay_to'], $banks)){

                    $pay_to_bank = $banks[$params['pay_to']]['bank'];
                    $pay_to_acc_name = $banks[$params['pay_to']]['acc_name'];
                    $pay_to_acc_number = $banks[$params['pay_to']]['acc_number'];

                    }
                 }
                
              $this->db->table_name =TBL_ECURRENCY;
              $obj3 = $this->db->find('id',$t->ecurrency_id);

               if($obj3){

                 
                try{
                // begin the transaction
                 $this->db->beginTransaction();

                 $this->db->table_name =TBL_TRANSACTIONS;
                 //set the status to waiting to be paired
                 $parameter = array(
                    'amount' => Sanitize::cleanAll($params['D_amount']),
                    'pay_to_identifier' => $obj3->identifier,
                    'identifier_label' => $obj3->identifier_label,
                    'buy_rate' => $obj3->buy_rate,
                    'sell_rate' => $obj3->sell_rate,
                    'virtual_currency' => $obj3->virtual_currency,
                    'currency' => $obj3->currency,
                    'currency_rate' => $obj3->currency_rate,
                    'identifier' => Sanitize::cleanAll($params['D_identifier']),
                    'comment' => Sanitize::cleanAll($params['D_comment']),
                    'bank_name' => isset($params['bank'])? Sanitize::cleanAll($params['bank']) :false,
                    'acc_name' => isset($params['acc_name'])?  Sanitize::cleanAll($params['acc_name']) : false,
                    'acc_number' => isset($params['acc_number'])?  Sanitize::cleanAll($params['acc_number']) : false,
                    'acc_type' => isset($params['acc_type'])?  Sanitize::cleanAll($params['acc_type']) : false,
                    'ip' => Utility::getClientIP(),
                    'pay_to_bank' => $pay_to_bank,
                    'pay_to_acc_name' => $pay_to_acc_name,
                    'pay_to_acc_number' => $pay_to_acc_number,
                    'modification_timestamp' => $this->time
                       );
                  $this->db->update($parameter,'id',$obj2->id);

                  $more_details = '';
                  $worth = 0;
                  if($type == 'buy'){
                    $worth = ($obj3->buy_rate * $params['D_amount'] * $obj3->currency_rate);
                    $more_details = '<br><hr />Please send the money to: <br>
                      Bank: '.$pay_to_bank.' <br>
                      Acc Name: '.$pay_to_acc_name.'
                      Acc Number: '.$pay_to_acc_number.'
                      Acc Type: Current
                      <br><hr />
                    ';
                  }elseif($type == 'sell'){
                    $worth = ($obj3->sell_rate * $params['D_amount'] * $obj3->currency_rate);
                    $more_details = '<br><hr />Please send the '.$obj3->name.' to: <br>
                      '.$obj3->identifier.'
                       <br><hr />

                      IMPORTANT <br>
                      If you send to any other '.$obj3->identifier_label.' aside the one generated for you on the site and also contained here, your money will be lost and not payable by us. Do not send to the '.$obj3->identifier_label.' more than once. If you need to sell again, kindly place a new order. <br><hr />

                    ';
                  }

                  $subject ='Order Updated Successfully';
                  $message = "
                    <br>
                  
                    PLEASE DO NOT REPLY THIS MAIL

                   <br><hr />
                    You have requested to ".$type." ".$params['D_amount']." ".$obj3->virtual_currency." of ".$obj3->name." worth about ".Number::currency($worth,'NGN')."(This amount may vary with the final value which will be calculated upon verification by our system). Invoice number for this order: ".$obj2->slug.". <br> ".$more_details." This order was updated on ". Utility::unixdatetime_to_text($this->time).". <br><hr /> Please note that you can modify this order while its still pending and waiting to be locked for verification.";
                  Mailer::sendMail($obj1,$subject,$message,$attachment=false);

                   $subject = 'Order - '.$obj2->slug.' - Updated';
                   $message = "Request to ".$type." ".$params['D_amount']." ".$obj3->virtual_currency." of ".$obj3->name." worth about ".Number::currency($worth,'NGN')." was submitted by ".$obj1->email.". Order No: ".$obj2->slug.". <br> This order was updated on ". Utility::unixdatetime_to_text($this->time);
                    //initiate the notification
                    $this->_mailNotifications($subject,$message,$attachment=false);

                 //commit update
                 $this->db->commit();
                 return true;

               } catch (PDOException $e) {
                 // rollback update
                 $this->db->rollback();
                 throw $e;
             }

             }else{ throw new Exception("Error Processing Request",1); }

          }else{ throw new Exception("Error Processing Request",1); }

         }else{ throw new Exception("Error Processing Request",1); }

        }else{ throw new Exception("Error Processing Request",1); }

       }


 public function lock()
   {
    
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

        $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
        $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;

        $transaction_id = isset($params['transaction_id'])? Sanitize::cleanAll($params['transaction_id']) : false;

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {
            $this->_verifySession($obj1->session_id,$token);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            $this->db->table_name =TBL_TRANSACTIONS;
            $obj2 = $this->db->find('id', $transaction_id);

            if($obj2){
                 
              try{
                // begin the transaction
                 $this->db->beginTransaction();

                 $this->db->table_name =TBL_TRANSACTIONS;
                 //set the status to waiting to be paired
                 $parameter = array(
                  'status' => 2,
                  'modification_timestamp' => $this->time
                       ); 
                  $this->db->update($parameter,'id',$obj2->id);

                  $subject ='Order Locked For Verification';
                  $message = "Your Order: ".$obj2->slug." was locked on ". Utility::unixdatetime_to_text($obj2->creation_timestamp)." for verification. Please note that you wont be able to modify this Order any more.";
                  Mailer::sendMail($obj1,$subject,$message,$attachment=false);

                  $subject = 'Order - '.$obj2->slug.' - Locked For Verification';
                  $message = "Order: ".$obj2->slug." was locked on ". Utility::unixdatetime_to_text($obj2->creation_timestamp)." for verification by ".$obj1->email;
                    //initiate the notification
                  $this->_mailNotifications($subject,$message,$attachment=false);

                 $this->db->commit();
                 return true;

               } catch (PDOException $e) {
                 // rollback update
                 $this->db->rollback();
                 throw $e;
             }
          }else{ throw new Exception("Error Processing Request",1); }

         }else{ throw new Exception("Error Processing Request",1); }

        }else{ throw new Exception("Error Processing Request",1); }

       }


  public function fetch()
   {
    
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

        $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
        $transaction_id = isset($params['transaction_id'])? Sanitize::cleanAll($params['transaction_id']) : false;
        $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;
        $type = !empty($params['type'])? Sanitize::cleanAll($params['type']) : false;


        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {
            $this->_verifySession($obj1->session_id,$token);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            
            $this->db->table_name =TBL_TRANSACTIONS;
            $obj2 = $this->db->find('slug',$transaction_id);

            if($obj2){

               if($obj2->user_id != $obj1->id){
                throw new Exception("Sorry you do not have access to this transaction.", 1);
                
                 }

           
                  $t = $obj2;
                  $arr = array();
                  $icon =false;
                  $this->db->table_name = TBL_ECURRENCY;
                   $obj3 = $this->db->find('id',$t->ecurrency_id);

                   if($obj3){

                      $icon = $this->_get_icon_url($obj3);
                   $e = $obj3;

                   $arr = array(
                    'id' => $t->id,
                    'status' => $t->status,
                    'user_id' => $t->user_id,
                    'ecurrency_id' => $t->ecurrency_id,
                    'e_currency' => $t->e_currency,
                    'icon' => $icon,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'pay_to_identifier' => $e->identifier,
                    'identifier' => $t->identifier,
                    'identifier_label' => $e->identifier_label,
                    'buy_rate' => $t->buy_rate,
                    'sell_rate' => $t->sell_rate,
                    'comment' => $t->comment,
                    'general_comment' => $e->comment,
                    'sell_comment' => $e->sell_comment,
                    'buy_comment' => $e->buy_comment,
                    'virtual_currency' => $t->virtual_currency,
                    'currency' => $t->currency,
                    'currency_rate' => $t->currency_rate,
                    'bank_name' => $t->bank_name,
                    'acc_name' => $t->acc_name,
                    'acc_number' => $t->acc_number,
                    'acc_type' => $t->acc_type,
                    'slug' => $t->slug,
                    'ip' => $t->ip,
                    'teller_no' => $t->teller_no,
                    'pay_to_bank' => $t->pay_to_bank,
                    'pay_to_acc_name' => $t->pay_to_acc_name,
                    'pay_to_acc_number' => $t->pay_to_acc_number,
                    'time' => Utility::unixdatetime_to_text($t->creation_timestamp),
                    'creation_timestamp' => $t->creation_timestamp,
                    'modification_timestamp' => $t->modification_timestamp
                            );

                  $arr3 = unserialize(BANK_ACCOUNTS);
                 $arr2 = array();
                 $this->db->table_name =TBL_BANKS;
                 $banks = $this->db->find_all('id','DESC');
                 if($banks){
                   $arr2 = $banks;
                  }

                // var_dump($arr3); 
                 return (array) array('ecurrency'=>$arr, 'banks'=>$arr2, 'bank_accounts'=>$arr3);

                      

              }else{
                throw new Exception("We were unable to sync this transaction with the e-currency. Please try again.",1); 
              } 
          
          }else{ throw new Exception("Error Processing Request",1); }

         }else{ throw new Exception("Error Processing Request",1); }

        }else{ throw new Exception("Error Processing Request",1); }

       }






   public function findall()
   {  
      ///array('id'=>1)
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

       $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
       $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;
       $limit = !empty($params['limit'])? Sanitize::cleanAll($params['limit']) : 200;
        $start = !empty($params['start'])? Sanitize::cleanAll($params['start']) : 0;


        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {
             $this->_verifySession($obj1->session_id,$token);
             //check if the user is active
              $this->_checkUserStatus($obj1->status);

              $this->db->table_name =TBL_TRANSACTIONS;
              $obj3 = $this->db->find_all_pagination_param(array('user_id' => $obj1->id),'modification_timestamp','DESC', $start, $limit);
               //$obj3 = $this->db->find_all_col('user_id',$obj1->id);
           
              $arr3 = array();

             
               if($obj3){
                  foreach ($obj3 as $t) {

                   $this->db->table_name = TBL_ECURRENCY;
                   $obj2 = $this->db->find('id',$t->ecurrency_id);

                   $arr3[] = array(
                    'id' => $t->id,
                    'status' => $t->status,
                    'user_id' => $t->user_id,
                    'ecurrency_id' => $t->ecurrency_id,
                    'e_currency' => $t->e_currency,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'pay_to_identifier' => $t->pay_to_identifier,
                    'identifier' => $t->identifier,
                    'identifier_label' => $t->identifier_label,
                    'buy_rate' => $t->buy_rate,
                    'sell_rate' => $t->sell_rate,
                    'comment' => $t->comment,
                    'virtual_currency' => $t->virtual_currency,
                    'currency' => $t->currency,
                    'currency_rate' => $t->currency_rate,
                    'bank_name' => $t->bank_name,
                    'acc_name' => $t->acc_name,
                    'acc_number' => $t->acc_number,
                    'acc_type' => $t->acc_type,
                    'slug' => $t->slug,
                    'ip' => $t->ip,
                    'teller_no' => $t->teller_no,
                    'pay_to_bank' => $t->pay_to_bank,
                    'pay_to_acc_name' => $t->pay_to_acc_name,
                    'pay_to_acc_number' => $t->pay_to_acc_number,
                    'time' => Utility::unixdatetime_to_text($t->creation_timestamp),
                    'creation_timestamp' => $t->creation_timestamp,
                    'modification_timestamp' => $t->modification_timestamp
                            );
                      
                     }
                  }
               $arr_merge = $arr3;

              //var_dump($arr_merge);

               return $arr_merge;

           }else {
         throw new Exception("Error Processing Request",1);
            }
            

         }else{
              throw new Exception("Error Processing Request",2);
        }
   }





   public function delete()
   {
    
    	$params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

        $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
        $transaction_id = isset($params['transaction_id'])? Sanitize::cleanAll($params['transaction_id']) : false;
        $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;
 

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {

            $this->_verifySession($obj1->session_id,$token);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            $this->db->table_name =TBL_TRANSACTIONS;
            $obj2 = $this->db->find('id', $transaction_id);

            if($obj2){

              if($obj2->status > 1){
                throw new Exception("Sorry, you can not delete this transaction again. Contact us if you feel this is wrong.", 1);
                
                 }
                 
            	try{
                // begin the transaction
                 $this->db->beginTransaction();

        	       $this->db->table_name =TBL_TRANSACTIONS;

                 $this->db->delete('id',strip_tags($transaction_id)); 

   				       //commit update
                 $this->db->commit();
                 return true;

               } catch (PDOException $e) {
                 // rollback update
                 $this->db->rollback();
                 throw $e;
             }
          }else{ throw new Exception("Error Processing Request. You cant terminate a successful transaction",1); }

         }else{ throw new Exception("Error Processing Request",1); }

        }else{ throw new Exception("Error Processing Request",1); }

       }



  
 //admin things



   public function fetchall()
   {  
      ///array('id'=>1)
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

       $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
        $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;
        $limit = !empty($params['limit'])? Sanitize::cleanAll($params['limit']) : 200;
        $start = !empty($params['start'])? Sanitize::cleanAll($params['start']) : 0;
 

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {

            $this->_verifySession($obj1->session_id,$token);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            //check if user is admin
            $this->_checkUserAdmin($obj1->admin);

              $this->db->table_name =TBL_TRANSACTIONS;
              $obj3 = $this->db->find_all_pagination('modification_timestamp','DESC', $start, $limit);
              $arr3 = array();

              $this->db->table_name = TBL_USERS;
               if($obj3){
                  foreach ($obj3 as $t) {

                      $this->db->table_name = TBL_USERS;
                      $user = $this->db->find('id', $t->user_id);

                    $arr3[] = array(
                    'id' => $t->id,
                    'status' => $t->status,
                    'user_id' => $t->user_id,
                    'ecurrency_id' => $t->ecurrency_id,
                    'e_currency' => $t->e_currency,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'pay_to_identifier' => $t->pay_to_identifier,
                    'identifier' => $t->identifier,
                    'identifier_label' => $t->identifier_label,
                    'buy_rate' => $t->buy_rate,
                    'sell_rate' => $t->sell_rate,
                    'comment' => $t->comment,
                    'virtual_currency' => $t->virtual_currency,
                    'currency' => $t->currency,
                    'currency_rate' => $t->currency_rate,
                    'bank_name' => $t->bank_name,
                    'acc_name' => $t->acc_name,
                    'acc_number' => $t->acc_number,
                    'acc_type' => $t->acc_type,
                    'slug' => $t->slug,
                    'ip' => $t->ip,
                    'teller_no' => $t->teller_no,
                    'pay_to_bank' => $t->pay_to_bank,
                    'pay_to_acc_name' => $t->pay_to_acc_name,
                    'pay_to_acc_number' => $t->pay_to_acc_number,
                    'time' => Utility::unixdatetime_to_text($t->creation_timestamp),
                    'creation_timestamp' => $t->creation_timestamp,
                    'modification_timestamp' => $t->modification_timestamp,
                    'status_alt' => Utility::dispStatus($t->status),
                    'status_color' => Utility::dispStatusColor($t->status),
                    'fullname' => ($user)? $user->fullname: '**********',
                     'phone' => ($user)? $user->phone: '**********',
                     'user_id' => ($user)? $user->id : '**********',
                     'username' => ($user)? $user->username : '**********',
                     'email' => ($user)? $user->email : '**********',
                            );
                      
                  }
               }

              // var_dump($arr3); 

               $arr3_count = Utility::getArrCount($arr3);
               return (array) array('transactions'=>$arr3, 'count'=>$arr3_count);

           }else {
         throw new Exception("Error Processing Request",1);
            }
            

         }else{
              throw new Exception("Error Processing Request",2);
        }
   }




    public function searchall()
   {  
      ///array('id'=>1)
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

        if(empty($params['keyword'])){
          throw new Exception("Please enter keyword and avoid entering 0", 1);
        }

       $id = !empty($params['id'])? Sanitize::cleanAll($params['id']) : false;
       $keyword = !empty($params['keyword'])? Sanitize::cleanAll($params['keyword']) : false;
       ;
       $type = !empty($params['type'])? Sanitize::cleanAll($params['type']) : false;
       $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;



        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {

            $this->_verifySession($obj1->session_id,$token);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            //check if user is admin
            $this->_checkUserAdmin($obj1->admin);

              $this->db->table_name =TBL_TRANSACTIONS;
              switch ($type) {
                case 'userid':
                  $obj3_params = array('user_id' => $keyword);
                  break;
                case 'slug':
                $obj3_params = array('slug' => $keyword);
                  break;
          
                 case 'status':
                 $obj3_params = array('status' => $keyword);
                  break;               
                default:
                  $obj3_params = array('id' => $keyword);
                  break;
              }

              $obj3 =  $this->db->find_all_search_pagination($obj3_params,'creation_timestamp','DESC',0,100000);

              $arr3 = array();

              $this->db->table_name = TBL_USERS;
               if($obj3){
                  foreach ($obj3 as $t) {

                      $this->db->table_name = TBL_USERS;
                      $user = $this->db->find('id', $t->user_id);

                        $arr3[] = array(
                    'id' => $t->id,
                    'status' => $t->status,
                    'user_id' => $t->user_id,
                    'ecurrency_id' => $t->ecurrency_id,
                    'e_currency' => $t->e_currency,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'pay_to_identifier' => $t->pay_to_identifier,
                    'identifier' => $t->identifier,
                    'identifier_label' => $t->identifier_label,
                    'buy_rate' => $t->buy_rate,
                    'sell_rate' => $t->sell_rate,
                    'comment' => $t->comment,
                    'virtual_currency' => $t->virtual_currency,
                    'currency' => $t->currency,
                    'currency_rate' => $t->currency_rate,
                    'bank_name' => $t->bank_name,
                    'acc_name' => $t->acc_name,
                    'acc_number' => $t->acc_number,
                    'acc_type' => $t->acc_type,
                    'slug' => $t->slug,
                    'ip' => $t->ip,
                    'teller_no' => $t->teller_no,
                    'pay_to_bank' => $t->pay_to_bank,
                    'pay_to_acc_name' => $t->pay_to_acc_name,
                    'pay_to_acc_number' => $t->pay_to_acc_number,
                    'time' => Utility::unixdatetime_to_text($t->creation_timestamp),
                    'creation_timestamp' => $t->creation_timestamp,
                    'modification_timestamp' => $t->modification_timestamp,
                    'status_alt' => Utility::dispStatus($t->status),
                    'status_color' => Utility::dispStatusColor($t->status),
                    'fullname' => ($user)? $user->fullname: '**********',
                     'phone' => ($user)? $user->phone: '**********',
                     'user_id' => ($user)? $user->id : '**********',
                     'username' => ($user)? $user->username : '**********',
                     'email' => ($user)? $user->email : '**********',
                            );

                  }
               }

              // var_dump($arr3); 

                $arr3_count = Utility::getArrCount($arr3);
               return (array) array('transactions'=>$arr3, 'count'=>$arr3_count);

           }else {
         throw new Exception("Error Processing Request",1);
            }
            

         }else{
              throw new Exception("Error Processing Request",2);
        }
   }


  
  
   public function hold()
   {
    
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

        $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
        $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;


        $transaction_id = isset($params['transaction_id'])? Sanitize::cleanAll($params['transaction_id']) : false;

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {
            $this->_verifySession($obj1->session_id,$token);
            //check if user is admin
            $this->_checkUserAdmin($obj1->admin);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            $this->db->table_name =TBL_TRANSACTIONS;
            $obj2 = $this->db->find('id', $transaction_id);

            if($obj2){

               $this->db->table_name =TBL_USERS;
              $obj3 = $this->db->find('id',$obj2->user_id);


              if($obj3){

                 
              try{
                // begin the transaction
                 $this->db->beginTransaction();

                 $this->db->table_name =TBL_TRANSACTIONS;
                 //set the status to waiting to be paired
                 $parameter = array(
                  'status' => 2,
                  'modification_timestamp' => $this->time
                       ); 
                  $this->db->update($parameter,'id',$obj2->id);

                  $subject ='Order Locked For Verification';
                  $message = "Your order: ".$obj2->slug." was locked on ". Utility::unixdatetime_to_text($obj2->creation_timestamp)." for verification. Please note that you wont be able to modify this order any more.";
                  Mailer::sendMail($obj3,$subject,$message,$attachment=false);

                 $this->db->commit();
                 return true;

               } catch (PDOException $e) {
                 // rollback update
                 $this->db->rollback();
                 throw $e;
             }

             }else{ throw new Exception("Error Processing Request",1); }
          }else{ throw new Exception("Error Processing Request",1); }

         }else{ throw new Exception("Error Processing Request",1); }

        }else{ throw new Exception("Error Processing Request",1); }

       }




  public function confirm()
   {
    
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

        $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
        $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;


        $transaction_id = isset($params['transaction_id'])? Sanitize::cleanAll($params['transaction_id']) : false;

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {
            $this->_verifySession($obj1->session_id,$token);
            //check if user is admin
            $this->_checkUserAdmin($obj1->admin);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            $this->db->table_name =TBL_TRANSACTIONS;
            $obj2 = $this->db->find('id', $transaction_id);

            if($obj2){

               $this->db->table_name =TBL_USERS;
              $obj3 = $this->db->find('id',$obj2->user_id);

              if($obj3){
                 
              try{
                // begin the transaction
                 $this->db->beginTransaction();

                 $this->db->table_name =TBL_TRANSACTIONS;
                 //set the status to confirmed
                 $parameter = array(
                  'status' => 3,
                  'modification_timestamp' => $this->time
                       ); 
                  $this->db->update($parameter,'id',$obj2->id);


                //if($obj2->type == 'buy'){
                 //lets convert currency to naira
                  $naira_equivalent = $obj2->buy_rate * $obj2->amount;
                 //try to credit the ref user
                 $this->_credit_ref($obj3,'order',$naira_equivalent);
                 // }
                 $worth = 0;
                  if($obj2->type == 'buy'){
                     $worth = ($obj2->buy_rate * $obj2->amount * $obj2->currency_rate);
                    }elseif($obj2->type == 'sell'){
                     $worth = ($obj2->sell_rate * $obj2->amount * $obj2->currency_rate);
                   }

                  $subject ='Order Verification successful';
                  $message = "Your ".$obj2->e_currency." order: ".$obj2->slug." of ".$obj2->amount." ".$obj2->virtual_currency." worth about ".Number::currency($worth,'NGN')." was verified successfully.  Please check your balance and note that you wont be able to modify this order any more.";
                  Mailer::sendMail($obj3,$subject,$message,$attachment=false);
                  SMS::sendMessage($obj3,$message);

                 $this->db->commit();


                 return true;

               } catch (PDOException $e) {
                 // rollback update
                 $this->db->rollback();
                 throw $e;
             }

              }else{ throw new Exception("Error Processing Request",1); }
          }else{ throw new Exception("Error Processing Request",1); }

         }else{ throw new Exception("Error Processing Request",1); }

        }else{ throw new Exception("Error Processing Request",1); }

       }




   public function cancel()
   {
    
      $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

        $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
        $transaction_id = isset($params['transaction_id'])? Sanitize::cleanAll($params['transaction_id']) : false;
        $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find('id',strip_tags($id));

        if ($obj1) {
            $this->_verifySession($obj1->session_id,$token);
             //check if user is admin
            $this->_checkUserAdmin($obj1->admin);
            //check if the user is active
            $this->_checkUserStatus($obj1->status);
            $this->db->table_name =TBL_TRANSACTIONS;
            $obj2 = $this->db->find('id',$transaction_id);

            if($obj2){

              $this->db->table_name =TBL_USERS;
              $obj3 = $this->db->find('id',$obj2->user_id);

              if($obj3){

              // if($obj2->status == 3){

              //     throw new Exception("You can not delete a successful transaction.", 1);
                  
              //  }

             
               
               try{
                $this->db->table_name =TBL_TRANSACTIONS;
                // begin the transaction
                 $this->db->beginTransaction();
                
                 $this->db->delete('id',$obj2->id); 

                 $subject ='Order Canceled';
                  $message = "Your order: ".$obj2->slug." was canceled on ". Utility::unixdatetime_to_text($obj2->creation_timestamp).". Please note that you wont be able to see/modify this order any more.";
                  Mailer::sendMail($obj3,$subject,$message,$attachment=false);
                   //commit update
                 $this->db->commit();
                 return true;

               } catch (PDOException $e) {
                 // rollback update
                 $this->db->rollback();
                 throw $e;
              }

            }else{ throw new Exception("Error Processing Request. Please try again.",1); }

          }else{ throw new Exception("Error Processing Request. Please try again.",1); }

         }else{ throw new Exception("Error Processing Request",1); }

        }else{ throw new Exception("Error Processing Request",1); }

       }



 public function bulkTransaction()
  { 

    $params = isset($_POST)? $_POST : array();
         
      if(!empty($params)){

         $id = isset($params['id'])? Sanitize::cleanAll($params['id']) : false;
         $type = isset($params['type'])? Sanitize::cleanAll($params['type']) : false;
         $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;
         $transactions = !empty($params['transactions'])? Sanitize::cleanAll($params['transactions']) : array();

         $this->db->table_name = TBL_USERS;

          $obj1 = $this->db->find('id',strip_tags($id));
            
           if($obj1){
              $this->_verifySession($obj1->session_id,$token);
              //check if the user is active
              $this->_checkUserStatus($obj1->status);
              //check if user is admin
              $this->_checkUserAdmin($obj1->admin);
             
              $this->db->table_name =TBL_TRANSACTIONS;
              
              switch ($type) {
                case 'delete':
                  try{
                  // begin the transaction
                   $this->db->beginTransaction();
                   foreach ($transactions as $t) {
                       
                       $this->db->table_name =TBL_TRANSACTIONS;
                        $obj2 = $this->db->find('id', $t);

                      if($obj2){

                      // if($obj2->status == 3){
                      //     throw new Exception("Sorry, you can not delete  successful transaction(s). Contact us if you feel this is wrong.", 1);
                          
                      //   }

                      $this->db->delete('id',strip_tags($t));

                       }
              
                     }

                     $this->db->commit();
                     return true;

                   } catch (PDOException $e) {
                    // rollback update
                    $this->db->rollback();
                    throw $e;
                    }
                
                  break;

                default:
                 throw new Exception("Error Processing Request",3);
                  break;
              }

            }else{ throw new Exception("Error Processing Request",3);  }

      }else{ throw new Exception("Error Processing Request",3);  }


    }
  


private function _credit_ref($params='',$type='',$amount=0)
  { 
    if(!empty($params) && !empty($type)){

       $this->db->table_name = TBL_SETTINGS;
       $referral_order_bonus = $this->db->find('param','referral_order_bonus');
       $referral_signup_bonus = $this->db->find('param','referral_signup_bonus');
       //set the credit amount as 0 first
        $credit_amount = 0;

        if($referral_order_bonus && $type == 'order'){
            $credit_amount = ($amount * $referral_order_bonus->value) /100;
          }

         if($referral_signup_bonus && $type == 'signup'){
           $credit_amount = $referral_signup_bonus;
         }

         $this->db->table_name = TBL_USERS_REFERRALS;
         $obj1 = $this->db->find('invite_user_id',$params->id);

          if($obj1){

             $this->db->table_name = TBL_USERS_WALLET_HISTORY;
             $slug = $this->_check_slug(Utility::get_key(10));
         
             $parameter = array(
              'user_id' => $obj1->user_id,
              'amount' => Number::precision($credit_amount,2),
              'slug' => $slug,
              'type' => 'credit',
              'program' => $type,
              'status' => 2,
              'ip' => Utility::getClientIP(),
              'creation_timestamp' => $this->time
                   ); 
               $this->db->table_name = TBL_USERS_WALLET_HISTORY;
              $this->db->create($parameter);

            
            }
        
         }

    }




private function _get_icon_url($params=''){
    //find the users avatar
     $icon_url = $this->ecurrencyIconUrl.'blank.gif';
     if(!empty($params)){
      
         if(!empty($params->path) && !empty($params->slug) && !empty($params->ext)){
           $icon_url = $this->ecurrencyIconUrl.$params->path.$params->slug.'.'.$params->ext;
          }
     
      }
  return $icon_url;
   }



 private function _addRep($id='',$rep=1)
  {

    if(!empty($id) && !empty($rep) && is_integer($rep)){
      $this->db->table_name = TBL_USERS;
      //find the user
       $u_i = $this->db->find('id', $id);
        if($u_i){
            $new_rep = $u_i->reputation + $rep;
          //lets update the user rep by adding some rep to his old rep
            $parameter = array(
        'reputation' => $new_rep
              ); 
           $save = $this->db->update($parameter,'id',$u_i->id);
           return ($save)? true: false;
        }

      }
      return false;
  }


  private function _validate_amount($params='',$type='',$user='')
  {
      if($params['D_amount'] <  10){
        throw new Exception("The amount you want to ".$type." is below the minimum amount." , 1);
      }
      if($user->verify == 1 && $params['D_amount'] > 1000){
         throw new Exception("The amount you want to ".$type." is above the maximum amount for unverified users.", 1);   
      }
             
  }


  private function _validate($params='',$type='',$action='')
  {
  
    if(empty($params['D_identifier'])){
      throw new Exception("Please enter the ".$params['identifier_label'], 1);
    
      }

     if(empty($params['D_amount'])){
        throw new Exception("Please enter the amount of ecurrency", 1);
      }else{
        if (!Validation::numeric($params['D_amount'])) {
              throw new Exception('Invalid amount.');   
            } 

      }

    
      if(!empty($params['D_comment'])){
      
        if (strlen($params['D_comment']) < 2 OR strlen($params['D_comment']) > 300) {
             throw new Exception('Comment is either too long or too short.');   
         } 

      }

    if($action == 'buy'){
        if (!isset($params['pay_to']) OR empty($params['pay_to'])) { 

       throw new Exception('Please select how you will pay.');
       
      }
    }

     if($action == 'sell'){

     if (!isset($params['bank']) OR empty($params['bank'])) { 

       throw new Exception('Bank name field is not set.');
       
      }

     if (!isset($params['acc_name']) OR empty($params['acc_name'])) { 

       throw new Exception('Account name field is not set.');
       
      }

      if (!isset($params['acc_number']) OR empty($params['acc_number'])) { 

       throw new Exception('Account number field is not set.');
       
      }

     if (!isset($params['acc_type']) OR empty($params['acc_type'])) { 

       throw new Exception('Please select an account type.');
       
      }
    }


     if($type == 'create'){

      if (!isset($params['type']) OR empty($params['type'])) { 

       throw new Exception('Error Processing Request, Please try again or contact us for help.');
       
      }

    }

  }

  private function _verifySession($session='',$token='')
  {
   if(!empty($session) && !empty($token)){

     if($session != $token){
      throw new Exception("Sorry, We were unable to verify your session. Please login again.",1);
     }

   }else{
      throw new Exception("Sorry, We were unable to verify your session. Please login again.",1);
   }
  }

 

  private function _validateTransactionStatus($status='')
  {
     if (!isset($status) OR empty($status)) { 

           throw new Exception('Invalid PH status.');
          
       }elseif(!in_array($status, array(1,3))){

            throw new Exception('Please select a valid PH pairing status 1 or GH pairing status 3.');
       }
    }



  private function _checkUserStatus($status=1)
  {
    if($status == 3){
       throw new Exception('Account Locked! - Contact the security center for more info.');
    }

    if($status != 2){
      throw new Exception('Account not activated! - Contact the security center for more info.');
    }

  }
	


   private function _checkUserAdmin($status=1)
  {
    if($status <= 1){
      throw new Exception('Permission denied.');
    }

  }

  private function _check_slug($parameter){
    $key = '';
    $i = 0;
    if($this->db->exists('slug', $parameter) == 1){
      $i++;
      $key = $i;
        while($this->db->exists('slug', $parameter.$key) == 1){
          $i++;
          $key = $i;
         }
    }
    $slug = ($i == 0) ? $parameter : $parameter.$key;
    return $slug;
    }
  


 }

?>