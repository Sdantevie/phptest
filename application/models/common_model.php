<?php


class CommonModel
{
    /**
     * Constructor, expects a Database connection
     * @param Database $db The Database object
     * TBL_USERS
  	 * TBL_USER_SESSION
  	 * TBL_USER_OTP
  	 * TBL_USER_FILES
  	 * TBL_USER_REFERRALS
  	 * TBL_USER_WALLET_HISTORY
     * TBL_USER_BANKS
     * TBL_BANKS
     * TBL_PROMOTION
     * TBL_SETTINGS
     * TBL_TRANSACTIONS
     * TBL_TRANSACTION_FILES
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->time = time();
        $this->userDataPath = API_URL."/data/users/";
        $this->userAvatarUrl = API_URL."/data/users/avatars/";
        $this->userFileUrl = API_URL."/data/users/files/";
        $this->userAvatarDataPath = "data/users/avatars/";
        $this->userFileDataPath = "data/users/files/";

    }
  

    public function _mailNotifications($subject='',$message='',$attachment=false)
   {
      $this->db->table_name = TBL_SETTINGS;
         $notification_emails = $this->db->find(array('SettingsParam'=>'notification_emails'));
         if ($notification_emails) {
           if (!empty($notification_emails->SettingsValue)) {
               $emails = unserialize($notification_emails->SettingsValue);
               if(is_array($emails)){
                 Mailer::sendMultipleMail($emails,$subject,$message,$attachment=false);
               }
           }
         }
     }

   public function _blockUser($userid=false)
    {    
        if($userid){
        
        $parameter = array(
        'UserBlockTimestamp' => $this->time
           );
         $this->db->table_name = TBL_USERS;
         $this->db->update($parameter,array('UserID'=>$userid));
       }
    }

   public function _getUserSettings($params=false)
  {
    return !empty($params->UserSettings)? unserialize($params->UserSettings): array('auth'=>'','session'=>'','tips'=>'');
  }

  public function _getUserAvatarUrl($id=''){
    //find the users avatar
     $avatar_url = $this->userAvatarUrl.'user-blank.png';
     if(!empty($id)){
       $this->db->table_name = TBL_USER_FILES;
       $avatar =  $this->db->find(array('UserID'=>$id,'FileType'=>'avatar')); 
       if($avatar){
         if(!empty($avatar->FileImgPath) && !empty($avatar->FileSlug) && !empty($avatar->FileExt)){
           $avatar_url = $this->userAvatarUrl.$avatar->FileImgPath.$avatar->FileSlug.'.'.$avatar->FileExt;
          }
      }
     }
  return $avatar_url;
   }



   public function _creditReferral($userid='',$program=false,$amount=0)
   { 
    if(!empty($userid)){
      //find the user's referral
       $this->db->table_name = TBL_USER_REFERRALS;
       $obj1 = $this->db->find(array('ChildUserID'=> $userid));

       if($obj1){
        //use the referral user id to find the user
       $this->db->table_name = TBL_USERS;
       $obj2 = $this->db->find(array('UserID'=> $obj1->UserID));
        if($obj2){  
           //credit the user
          $feedback =  $this->_credit_ref($obj2,$program,$amount);
           return ($feedback)? true: false;
        }

      }
    }
      return false;
  }

  
   public function _credit_ref($params='',$type='',$amount=0)
  { 
    if(!empty($params) && !empty($type)){

       $this->db->table_name = TBL_SETTINGS;
       $referral_order_bonus = $this->db->find(array('SettingsParam'=>'referral_order_bonus'));
       $referral_signup_bonus = $this->db->find(array('SettingsParam'=>'referral_signup_bonus'));
       //set the credit amount as 0 first
        $credit_amount = 0;
        $feedback =  false;

          	if($referral_order_bonus && $type == 'order'){
                $credit_amount  =  ($amount * $referral_order_bonus->SettingsValue) /100;
	            }

	         if($referral_signup_bonus && $type == 'signup'){
	         	//lets check if both users are using thesame device
	         	if($params->UserIp == Utility::getClientIP()){
	         	//if they are using thesame device we give the user nothing
	         		$credit_amount = 0; // set to 0
	         	}else{
	         	   $credit_amount  = $referral_signup_bonus->SettingsValue;
	            	} 
	          }

             $this->db->table_name = TBL_USER_WALLET_HISTORY;
             $slug = $this->_generate_identifier($this->db,'WalletSlug',Utility::get_key(10));
         
             $parameter = array(
              'UserID' => $params->UserID,
              'WalletAmount' => Number::precision($credit_amount,2),
              'WalletSlug' => $slug,
              'WalletType' => 'credit',
              'WalletProgram' => 'bonus',
              'WalletStatus' => 2,
              'WalletIp' => Utility::getClientIP(),
              'WalletCreationTimestamp' => $this->time
                   ); 
               $this->db->table_name = TBL_USER_WALLET_HISTORY;
	             if($referral_order_bonus && $type == 'order'){
                 if($credit_amount > 0){
	             	  $feedback = $this->db->create($parameter);
                    }
	               }

	              if($referral_signup_bonus && $type == 'signup'){
  			         	//lets check if both users are using thesame device
  			         	if($params->UserIp == Utility::getClientIP()){
  			         	  //if they are using thesame device we give the user nothing
  			         	   //$this->db->create($parameter);
  			         	}else{
                    if($credit_amount > 0){
  			         	  $feedback =  $this->db->create($parameter);
                    }
  			         	} 
			          }

            return ($feedback)? true: false;
        
         }

    }

   public function _walletStatistics($params='',$program=false)
   {
     $total_earned = 0;
     $total_collected = 0;
     $total_refs = 0;
     $total_pending_debit = 0;
     $available_withdrawal = 0;

     // $earned_this_month = 0;
     // $collected_this_month = 0;
     // $this_month_refs = 0;
     
     $this->start  = '1-'.Utility::unixdatetime_to_date_my($this->time); 
     $this->end = Utility::monthEnd(Utility::unixdatetime_to_date_my($this->time),'-','mm-yyyy');
     $this->start_timestamp  = Utility::getTimestamp($this->start,'midnight'); 
     $this->end_timestamp = Utility::getTimestamp($this->end,'tomorrow');
    
     if (!empty($params)) {

       $this->db->table_name = TBL_USER_WALLET_HISTORY;
       if($program){
        $obj3 = $this->db->find_all_param(array('UserID'=>$params->UserID,'WalletProgram'=>$program),$order='WalletID');
       }else{
        $obj3 = $this->db->find_all_param(array('UserID'=>$params->UserID),$order='WalletID');
       }
      
      
      if ($obj3) {
       foreach ($obj3 as $h) {

          if($h->WalletType == 'credit'){
            $total_earned += +$h->WalletAmount;
          }
          if($h->WalletType == 'debit' && $h->WalletStatus == '2'){
           $total_collected += +$h->WalletAmount;
          }
          if($h->WalletType == 'debit' && $h->WalletStatus == '1'){
           $total_pending_debit += +$h->WalletAmount;
          }


          // if ($h->creation_timestamp >= $this->start_timestamp && $h->creation_timestamp <= $this->end_timestamp && $h->type == 'credit') {
          //    $earned_this_month += +$h->amount;
          //  }
          // if ($h->creation_timestamp >= $this->start_timestamp && $h->creation_timestamp <= $this->end_timestamp && $h->type == 'debit' && $h->status == '2') {
          //    $collected_this_month += +$h->amount;
          //  }

        }
      }
    

      $this->db->table_name = TBL_USER_REFERRALS;
      $obj4 = $this->db->find_all_param(array('UserID'=>$params->UserID),$order='ReferralID');
       // if ($obj4) {
        //  foreach ($obj4 as $r) {
        //    if ($r->creation_timestamp >= $this->start_timestamp && $r->creation_timestamp <= $this->end_timestamp) {
       //          $this_month_refs += +1;
       //         } 
        //  }
       // }

      $total_refs = Utility::getArrCount($obj4);
      $available_withdrawal = $total_earned - $total_collected;
      
       }

       return array('total_refs'=> $total_refs,
            //'this_month_refs' => $this_month_refs,
            'total_earned' => Number::currency($total_earned, 'NGN'),
            'total_earned_raw' => $total_earned,
            //'earned_this_month' => Number::currency($earned_this_month, 'USD'),
            'total_collected' =>  Number::currency($total_collected, 'NGN'),
            //'collected_this_month' => Number::currency($collected_this_month, 'USD'),
            'total_pending_debit' =>  Number::currency($total_pending_debit, 'NGN'),
            'raw_total_pending_debit' =>  $total_pending_debit,
            'available_withdrawal' =>  Number::currency($available_withdrawal, 'NGN'),
            'raw_available_withdrawal' =>  $available_withdrawal);

   }


   public function _validateWithdraw($obj1=false,$amount=0,$program='bonus')
   {
       $wallet = $this->_walletStatistics($obj1,$program);
       $this->db->table_name = TBL_SETTINGS;
       $minimum_cashout = $this->db->find(array('SettingsParam'=>'minimum_cashout'));

       if ($amount > $wallet['raw_available_withdrawal']) {

          throw new Exception("You cant withdraw more than ". $wallet['available_withdrawal']." ".$program, 1);
         };

       if ($wallet['raw_available_withdrawal'] < $minimum_cashout->SettingsValue) {

        throw new Exception("You are not eligible to make Withdrawals any ".$program." yet. Your ".$program." balance must be above ".Number::currency($minimum_cashout->SettingsValue, 'NGN'), 1);
         }


       if ($wallet['raw_total_pending_debit'] > 0) {

      throw new Exception("You last ".$program." withdraw of ". $wallet['total_pending_debit'].' has not been approved yet.', 1);
      };

    }


    public function _withdraw($obj1=false,$amount=0,$program='bonus')
   {
      if ($amount) {
       
       $this->db->table_name = TBL_USER_WALLET_HISTORY;
       $slug = $this->_generate_identifier($this->db,'WalletSlug',Utility::get_key(10));
    
        try{
          // begin the transaction
           $this->db->beginTransaction();

         $parameter = array(
          'UserID' => $obj1->UserID,
          'WalletAmount' => $amount,
          'WalletSlug' => $slug,
          'WalletType' => 'debit',
          'WalletProgram' => $program,
          'WalletStatus' => 2,
          'WalletIp' => Utility::getClientIP(),
          'WalletCreationTimestamp' => $this->time
         ); 
       
         $this->db->create($parameter);

         $this->db->commit();

           return true;

           } catch (PDOException $e) {
          // rollback update
          $this->db->rollback();
          //throw $e;
          return false;
        }
      }
      return false; //shouldnt reach here except amount is 0
   }



	 public function _checkUser($params=false,$token=false,$config=array())
	 {	
	 	if(isset($config['session'])){
	 	 $this->_checkUserSession($params->UserID,$token);
	 	}
	 	if(isset($config['status'])){
          //check if the user is active
         $this->_checkUserStatus($params->UserStatus);
         }
         if(isset($config['admin'])){
        //check if user is admin
         $this->_checkUserAdmin($params->UserLevel);
    	 }
      
	 }
	
    public function _checkUserSession($userid=fasle,$token=false)
    {
    
      $userAgent = UserAgent::parse();
      $device = $userAgent['browser'].' on '.$userAgent['platform'];
      $ip = Utility::getClientIP();
      $this->db->table_name = TBL_USER_SESSION;
      $obj1 = $this->db->find(array('UserID'=>$userid,'SessionIp'=>$ip,'SessionDevice'=>$device)); 
     

     if(!empty($obj1) && !empty($token)){

       if($obj1->SessionToken != $token){
        throw new Exception("Sorry, We were unable to verify your session. Please login again.",1);
       }
       return true;
     }else{
        throw new Exception("Sorry, We were unable to verify your session. Please login again.",1);
     }
    }


	  public function _checkUserStatus($status=1)
	  {
	    if($status == 3){
	       throw new Exception('Account Locked! - Contact the security center for more info.');
	    }

	    if($status == 1){
	      throw new Exception('Account not activated, please visit your email and click on the activation link sent to your email, did not receive activation link? resend activation.');
	    }

	  }


	   public function _checkUserAdmin($status=1)
	  {
	    if($status <= 1){
	      throw new Exception('Permission denied.');
	    }

	  }


	   public function _checkUserVerified($status=1)
	  {
	    if($status == 2){
	      throw new Exception('Your account is verified.');
	    }

	  }
  

	 
	public function _generate_identifier($instance,$column=false,$parameter=false){
    $key = '';
    $i = 0;
    if($instance->exists($column, $parameter) == 1){
    	$i++;
    	$key = $i.Utility::get_key(2);
        while($instance->exists($column, $parameter.$key) == 1){
        	$i++;
        	$key = $i.Utility::get_key(2);
         }
    }
    $slug = ($i == 0) ? $parameter : $parameter.$key;
    return $slug;
    }
	
	
   public function _generate_ID($instance,$column=false,$parameter=false){
    $key = '';
    $i = 0;
    if($instance->exists($column, $parameter) == 1){
      $i++;
      $key = $i;
        while($instance->exists($column, $parameter.$key) == 1){
          $i++;
          $key = $i;
         }
    }
    $slug = ($i == 0) ? $parameter : $parameter.$key;
    return $slug;
    }

	  /**
	 * Checks if the entered captcha is the same like the one from the rendered image which has been saved in session
	 * @return bool success of captcha check
	 */
    public function checkCaptcha($params)
    { 
    	//throw new Exception($_SESSION['captcha'], 1);
    	//var_dump($params["captcha"]);

        if (isset($params["captcha"]) AND isset($_SESSION['captcha']) AND ($params["captcha"] == $_SESSION['captcha'])) {
            return true;
        }
        // default return
        return false;

    }




   public function _validateCaptcha($params=false)
   {
    if (empty($params['captcha'])) {
             throw new Exception('Captcha field is empty.');
      
         } else {
            if (!$this->checkCaptcha($params)) {
                throw new Exception('The entered captcha security characters were wrong.');
            } 
        }

   }



	

 }

?>