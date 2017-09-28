<?php


class UsersModel extends Controller
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
   *TBL_USER_BANKS
     */
    public function __construct(Database $db, $params)
    {
        $this->db = $db;
        $this->params = $params;
        $this->db->table_name = TBL_USERS;
        $this->time = time();
        $this->block_period = BLOCK_PERIOD;
        $this->userDataPath = API_URL."/data/users/";
        $this->userAvatarUrl = API_URL."/data/users/avatars/";
        $this->userFileUrl = API_URL."/data/users/files/";
        $this->userAvatarDataPath = "data/users/avatars/";
        $this->userFileDataPath = "data/users/files/";
        $this->common_model = $this->loadModel('Common',$this->params);

    }
  


   public function login($type=false,$authentication='normal')
  {

     if($authentication == 'sudo'){
         $login_error = 'Invalid SessionID used, please try again or contact us if error continues.';
         $password_error = 'Password field is empty.';
      }else{
         $login_error = 'Login field is empty.';
         $password_error = 'Password field is empty.';
      }

      if (!isset($this->params['login']) OR empty($this->params['login'])) {

             throw new Exception($login_error);
           }

         if (!isset($this->params['password']) OR empty($this->params['password'])) {
           throw new Exception($password_error);   
          }

           //$this->_validateCaptcha($this->params);

        $login = strip_tags($this->params['login']);
        $password = strip_tags($this->params['password']);

        $column = 'UserEmail';
        if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
             $column = 'UserName';
           }else{
            $column = 'UserEmail';
           } 

          $feedback = $this->db->find(array($column => $login));

        if( $feedback === false ) {

            throw new Exception('Invalid login or password');


          } else{

            $feedback->UserSettings = $this->common_model->_getUserSettings($feedback);

            

            if($feedback->UserStatus == 3){

             throw new Exception('Account Locked! - Contact the security center for more info.');
            }

            if($feedback->UserLevel == 1 && $type == 'admin'){
             throw new Exception('You dont have permission to enter.');
            }

           // if($feedback->status != 2){
           //       throw new Exception('Account not activated, please visit your email and click on the activation link sent to your email, did not receive activation link? enter email used during registration and click resend or Contact the security center for more info.');
           //  }

            //check session failed login for the user
            $this->_checkUserFailedLogins($feedback);

              // check if hash of provided password matches the hash in the database
              if (password_verify($password, $feedback->UserPassword)) {
                 
                 // if($authentication == 'sudo'){

                 //  //lets renew the session
                 //   $feedback->session = $this->_updateUserSession($feedback,'verified');
                 //  return true;
                 
                 // }else{

                  //check 2Factor auth is on
                 if (isset($feedback->UserSettings['auth'])) {
                    if ($feedback->UserSettings['auth'] == 'on') {

                  return (object) array('TwoFactor' => true,'UserEmail' => $feedback->UserEmail,'UserFirstName' => $feedback->UserFirstName);
                     }
                    }
                   
                  //update the user's session and return a token
                  $feedback->session = $this->_updateUserSession($feedback,'verified');
                  $feedback->block = Utility::checkTimeRemainingAlt($feedback->UserBlockTimestamp,$this->block_period);
                  //lets get the users avatar
                  $feedback->avatar_url = $this->common_model->_getUserAvatarUrl($feedback->UserID);
                    
                    return $feedback;

                     // }
          

                    } else {

                     //update the user's session and return a token
                     $this->_updateUserSession($feedback,'failed');
                     throw new Exception('Invalid login or password');

                  }

           }
             
  }


  public function logout()
  {

   $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
   $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
   $userid = !empty($this->params['userid'])? Sanitize::cleanAll($this->params['userid']) : false;
   $session_id = !empty($this->params['session_id'])? Sanitize::cleanAll($this->params['session_id']) : false;

    $obj1 = $this->db->find(array('UserID'=>$id));

    if($obj1){
      
      if($userid){//admins have power, allow only admins if this is set
        $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));
      }else{
        $this->common_model->_checkUser($obj1,$token,$config=array('status'=>true));
      }
      
      $this->db->table_name = TBL_USER_SESSION;
      $feedback = $this->db->delete('SessionID',strip_tags($session_id));
      return ($feedback)? true : false;

    }else{
      throw new Exception("Error Processing Request", 1);
    }

  }


  public function createUser()
  { 
      //validate the params data
       $this->_validateUser($this->params);
       $this->_validateUserPassword($this->params,'new');
       $mailing_list = !empty($this->params['mailing_list'])? Sanitize::cleanAll($this->params['mailing_list']) : false;
       //$this->_validateCaptcha($this->params);

        //validation successful
        //hash the password
        $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
        $password_hash = password_hash($this->params['password'], PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));


        if($this->db->find(array('UserName'=>$this->params['username']))) {
        throw new Exception('This username is already assigned to a user.');
       }

         if($this->db->find(array('UserEmail'=>$this->params['email']))) {
        throw new Exception('This email is already assigned to a user.');
       }

       // if($this->db->find(array('UserPhone'=>$this->params['phone']))) {
       //   throw new Exception('This phone number is already assigned to a user.');
       // }
      
        // generate random hash for email verification (40 char string)
           $activation_code = rand(1,substr(time(),3));
           $activation_hash = sha1(uniqid(mt_rand(), true));
           $slug =  $this->common_model->_generate_identifier($this->db,'UserSlug',Utility::get_key(10));

           //put the user login parameters into an array
             $parameter1 = array(
              'UserPassword' => $password_hash,
              'UserName' => $this->params['username'],
              'UserEmail' => $this->params['email'],
              'UserFirstName' => Sanitize::cleanAll($this->params['firstname']),
              'UserLastName' => Sanitize::cleanAll($this->params['lastname']),
              'UserPhone' => Sanitize::cleanAll($this->params['phone']),
              'UserState' => Sanitize::cleanAll($this->params['state']),
              'UserCountry' => Sanitize::cleanAll($this->params['country']),
              'UserIp' => Utility::getClientIP(),
              'UserStatus' => 2,
              'UserSlug' => $slug,
              'UserVerificationCode' => $activation_code,
              'UserVerificationHash' => $activation_hash,
              'UserVerificationTimestamp' => $this->time,
              'UserCreationTimestamp' => $this->time
                   ); 
       
         $feedback1 = $this->db->create($parameter1);

         if($feedback1){
         
          //perform a fetch action here
          $this->db->table_name = TBL_USERS;
         $user = $this->db->find(array('UserEmail'=>$this->params['email']));

       if($user){

          //if everything goes well, we then update the users bank data
         //$this->_update_bank_alt($user,$this->params);
        //lets sign the user up for newsletter
         if($mailing_list){
           $this->_signupNewsLetter($user->UserEmail,2);
         }
         
         Mailer::sendVerificationEmail($user);

         $subject = 'New user '.$user->UserFirstName.' '.$user->UserLastName.' Joined';
         $message =  $user->UserFirstName.' '.$user->UserLastName." just joined the platform using the following info: Email: ".$user->UserEmail.", Username: ".$user->UserName.", Phone: ".$user->UserPhone;
              //initiate the notification
             $this->common_model->_mailNotifications($subject,$message,$attachment=false);
         //lets link the user to the user who did the invitation
         $this->_addReferral($user->UserID,$this->params['invite']);
         //update the user's session and return a token
            $user->session = $this->_updateUserSession($user,'verified');
           //lets get the users avatar
           $user->avatar_url = $this->common_model->_getUserAvatarUrl($user->UserID);
           $user->UserSettings = $this->common_model->_getUserSettings($user);

             return $user;

           }else{
             throw new Exception("Sorry, your account could not be created at this moment. Please try again.");
             
           }

         }else{
          throw new Exception("Sorry, your account could not be created at this moment. Please try again.");
         }


    }


  public function createSubscription()
  { 

     if (!empty($this->params['email'])) {
              
                  if (strlen($this->params['email']) > 64) { 
                     throw new Exception('Email cannot be longer than 64 characters.'); 
                 
                  } elseif (!filter_var($this->params['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Sorry, your chosen email does not fit into the email naming pattern.'); 
                   
                  } 
            } else{
               throw new Exception('Please provide your Email.'); 
            }

     $this->db->table_name = TBL_MAILING_LIST;
     if($this->db->find(array('MailingListEmail'=>$this->params['email']))) {
        throw new Exception('This email is already on subscription.');
       }
       if ($this->_signupNewsLetter($this->params['email'],1)) {

        return true;
        
       }else{
        throw new Exception("Sorry, your subscription could not be processed at the moment. Please try again.");
       }

 }

 private function _signupNewsLetter($email=false,$status=1)
   {
      if($email) {
          $this->db->table_name = TBL_MAILING_LIST;
          if($this->db->find(array('MailingListEmail'=>$email))) {
        return true; //do nothing
        }
       $activation = sha1(uniqid(mt_rand(), true));
     $parameter = array(
      'MailingListEmail' => $this->params['email'],
      'MailingListIp' => Utility::getClientIP(),
      'MailingListStatus' => $status,
      'MailingListSlug' => $activation,
      'MailingListCreationTimestamp' => $this->time
         ); 
       
         $feedback = $this->db->create($parameter);
         if ($status == 2) {
           return true;
         }
         if ($feedback) {
           $inlist = $this->db->find(array('MailingListEmail'=>$email));
           if($inlist){
            if ($status == 1) {
              Mailer::sendNewsletterSubscription($inlist);
              return true;
            }else{
              Mailer::sendNewsletterWelcome($inlist);
              return true;
            }
            
           } 
          }
         
      }else{
        return false;
      }
      return false;
   }



  public function fetch()
   {

   $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
   $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;

    $obj1 = $this->db->find(array('UserID'=>$id));

    if($obj1){
      
      $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true));

      $obj1->session = $this->_getUserSession($obj1->UserID); 
      $obj1->UserSettings = $this->common_model->_getUserSettings($obj1);
      //lets get the users avatar
      $obj1->avatar_url = $this->common_model->_getUserAvatarUrl($obj1->UserID);

       $this->db->table_name = TBL_USER_BANKS;
       $obj3 = $this->db->find(array('UserID'=>$obj1->UserID));

       $arr2 = array(); $arr3 = array(); $arr4 = array(); $arr5 = array();
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

         $this->db->table_name =TBL_USER_SESSION;
         $sessions = $this->db->find_all_param(array('UserID'=> $obj1->UserID),$order='UserID','DESC');
         if($sessions){
           $arr5 = $sessions;
          }

        $arr_merge = array_merge((array) array('user' => $obj1),array('bank' => !empty($obj3)? $obj3:array()), array('banks'=>$arr2),array('countries'=>$arr3),array('states'=>$arr4),array('sessions'=>$arr5));
  
        return (object)  $arr_merge;

      }else{ throw new Exception("Error Processing Request",2);  }
    
   }



    public function fetch_user_account()
   {  

       $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
        $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
        $user_id = !empty($this->params['user_id'])? Sanitize::cleanAll($this->params['user_id']) : false;

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1 && $user_id) {

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

            $this->db->table_name = TBL_USERS;
            $obj2 = $this->db->find(array('UserID'=>strip_tags($user_id)));
            $arr2 = array();
            if ($obj2) {

                  $obj2->session = $this->_getUserSession($obj2->UserID); 
                  $obj2->UserSettings = $this->common_model->_getUserSettings($obj2);
                  //lets get the users avatar
                  $obj2->avatar_url = $this->common_model->_getUserAvatarUrl($obj2->UserID);
                  $wallet = $this->common_model->_walletStatistics($obj2);

                   $this->db->table_name = TBL_USER_BANKS;
                   $obj3 = $this->db->find(array('UserID'=>$obj2->UserID));

                   $arr2 = array(); $arr3 = array(); $arr4 = array(); $arr5 = array();
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

                     $this->db->table_name =TBL_USER_SESSION;
                     $sessions = $this->db->find_all('UserID','DESC');
                     if($sessions){
                       $arr5 = $sessions;
                      }

                    $arr_merge = array_merge((array) array('user' => $obj2),array('bank' => !empty($obj3)? $obj3:array()), array('banks'=>$arr2),array('countries'=>$arr3),array('states'=>$arr4),array('sessions'=>$arr5),array('wallet'=>$wallet));
              
                    return (object)  $arr_merge;

             }else {
         throw new Exception("Error Processing Request",1);
            }
            

          }else {
         throw new Exception("Error Processing Request",1);
            }
       
    }


  


   public function fetch_user_bank()
   {

       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
       $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if($obj1){

          $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true));
        
         $this->db->table_name = TBL_USER_BANKS;
         $obj3 = $this->db->find(array('UserID'=>$obj1->UserID));
        
         $arr2 = array();
         $this->db->table_name =TBL_BANKS;
             $banks = $this->db->find_all('BankID','DESC');
             if($banks){
               $arr2 = $banks;
              }

            $arr_merge = array_merge((array) array('bank' => !empty($obj3)? $obj3:array()), array('banks'=>$arr2));
      
            return (object)  $arr_merge;

          }else{ throw new Exception("Error Processing Request",2);  }
    
   }




   public function fetch_wallet_history()
   {

       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
       $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
       $limit = !empty($this->params['limit'])? Sanitize::cleanAll($this->params['limit']) : 200;
        $start = !empty($this->params['start'])? Sanitize::cleanAll($this->params['start']) : 0;

         $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if($obj1){

          $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true));

           $wallet_history = array();
           $this->db->table_name = TBL_USER_WALLET_HISTORY;
           $obj2 = $this->db->find_all_pagination_param(array('UserID' => $obj1->UserID),'WalletCreationTimestamp','DESC', $start, $limit);
       
            $statistics = $this->common_model->_walletStatistics($obj1);

            $arr_merge = array_merge((array) array('history' => $obj2), array('wallet' => $statistics));
      
            return (object)  $arr_merge;

          }else{ throw new Exception("Error Processing Request",2);  }
    
   }


    public function fetch_wallet()
   {

       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
       $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
      
        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if($obj1){

          $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true));
       
          $statistics = $this->common_model->_walletStatistics($obj1,'bonus');
      
            return (object) array('wallet' => $statistics);

          }else{ throw new Exception("Error Processing Request",2);  }
    
   }





  
    public function fetchall()
   {  

       $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
        $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
        $limit = !empty($this->params['limit'])? Sanitize::cleanAll($this->params['limit']) : 200;
        $start = !empty($this->params['start'])? Sanitize::cleanAll($this->params['start']) : 0;
 

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

              $this->db->table_name = TBL_USERS;
              $obj3 = $this->db->find_all_pagination('UserCreationTimestamp','DESC', $start, $limit);
              $arr3 = array();

               if($obj3){
                  foreach ($obj3 as $user) {

                       $avatar_url =$this->common_model->_getUserAvatarUrl($user->UserID);
                 $wallet = $this->common_model->_walletStatistics($user);

                 $this->db->table_name = TBL_USER_BANKS;
                 $bank = $this->db->find(array('UserID'=>$user->UserID));

                        $arr3[] = array(
                         'id' => $user->UserID,  
                         'avatar' => $avatar_url,          
                         'status' => $user->UserStatus,
                         'fullname' => $user->UserFirstName.' '.$user->UserLastName,
                         'phone' => $user->UserPhone,
                         'username' => $user->UserName,
                         'email' => $user->UserEmail,
                         'ip' => $user->UserIp,
                         'level' => $user->UserLevel,
                         'verify' => $user->UserVerified,
                         'total_refs' => $wallet['total_refs'],
                         'total_earned' => $wallet['total_earned'],
                         'total_collected' => $wallet['total_collected'],
                         'total_pending_debit' => $wallet['total_pending_debit'],
                         'available_withdrawal' => $wallet['available_withdrawal'],
                         'time_created' => Utility::unixdatetime_to_text($user->UserCreationTimestamp),
                         'time_modified' => Utility::unixdatetime_to_text($user->UserModificationTimestamp),
                         
                        );

                  }
               }

              // var_dump($arr3); 
               $arr3_count = Utility::getArrCount($arr3);
               return (array) array('users'=>$arr3, 'count'=>$arr3_count);

           }else {
         throw new Exception("Error Processing Request",1);
            }
            
   }




    public function searchall()
   {  
     

        if(empty($this->params['keyword'])){
          throw new Exception("Please enter keyword and avoid entering 0", 1);
        }

       $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
       $keyword = !empty($this->params['keyword'])? Sanitize::cleanAll($this->params['keyword']) : false;
       ;
       $type = !empty($this->params['type'])? Sanitize::cleanAll($this->params['type']) : false;
       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

              switch ($type) {
                case 'id':
                 $obj3_params = array('UserID' => $keyword);
                  break;
                case 'firstname':
                  $obj3_params = array('UserFirstName' => $keyword);
                  break;
                 case 'lastname':
                  $obj3_params = array('UserLastName' => $keyword);
                  break;
                case 'username':
                  $obj3_params = array('UserName' => $keyword);
                  break;
                case 'email':
                  $obj3_params = array('UserEmail' => $keyword);
                  break;
                  case 'phone':
                  $obj3_params = array('UserPhone' => $keyword);
                  break;
                 case 'ip':
                  $obj3_params = array('UserIp' => $keyword);
                  break;
                 case 'status':
                 $obj3_params = array('UserStatus' => $keyword);
                  break;   
                 case 'level':
                 $obj3_params = array('UserLevel' => $keyword);
                  break;               
                default:
                  $obj3_params = array('UserID' => $keyword);
                  break;
              }

              $this->db->table_name = TBL_USERS;
              $obj3 =  $this->db->find_all_search_pagination($obj3_params,'UserCreationTimestamp','DESC',0,1000);

              $arr3 = array();

             
               if($obj3){
                  foreach ($obj3 as $user) {

                       $avatar_url =$this->common_model->_getUserAvatarUrl($user->UserID);
                        $wallet = $this->common_model->_walletStatistics($user);

                        $this->db->table_name = TBL_USER_BANKS;
                       $bank = $this->db->find(array('UserID'=>$user->UserID));
                        $arr3[] = array(
                         'id' => $user->UserID,  
                         'avatar' => $avatar_url,          
                         'status' => $user->UserStatus,
                         'fullname' => $user->UserFirstName.' '.$user->UserLastName,
                         'phone' => $user->UserPhone,
                         'username' => $user->UserName,
                         'email' => $user->UserEmail,
                         'ip' => $user->UserIp,
                         'level' => $user->UserLevel,
                         'verify' => $user->UserVerified,
                         'total_refs' => $wallet['total_refs'],
                         'total_earned' => $wallet['total_earned'],
                         'total_collected' => $wallet['total_collected'],
                         'total_pending_debit' => $wallet['total_pending_debit'],
                         'available_withdrawal' => $wallet['available_withdrawal'],
                         'time_created' => Utility::unixdatetime_to_text($user->UserCreationTimestamp),
                         'time_modified' => Utility::unixdatetime_to_text($user->UserModificationTimestamp),
                         
                        );
                  }
               }


              // var_dump($arr3); 

               $arr3_count = Utility::getArrCount($arr3);
               return (array) array('users'=>$arr3, 'count'=>$arr3_count);

           }else {
         throw new Exception("Error Processing Request",1);
            }
       
   }






    public function fetchall_user_referral_history()
   {  

       $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
       $user_id = isset($this->params['user_id'])? Sanitize::cleanAll($this->params['user_id']) : false;
       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
       $limit = !empty($this->params['limit'])? Sanitize::cleanAll($this->params['limit']) : 200;
       $start = !empty($this->params['start'])? Sanitize::cleanAll($this->params['start']) : 0;
 
        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

             $arr2 = array();
             $this->db->table_name = TBL_USER_REFERRALS;
             $obj2 = $this->db->find_all_pagination_param(array('UserID' => $user_id),'ReferralCreationTimestamp','DESC', $start, $limit);
            if($obj2){

              foreach ($obj2 as $u) {
                      $avatar_url = $this->common_model->_getUserAvatarUrl($u->ChildUserID);
                      $this->db->table_name = TBL_USERS;
                  $user = $this->db->find(array('UserID'=>$u->ChildUserID));

                  if($user){
                      $arr2[] = array(
                         'id' => $user->UserID,  
                         'avatar' => $avatar_url,  
                         'status' => $user->UserStatus,
                         'fullname' => $user->UserFirstName.' '.$user->UserLastName,
                         'phone' => $user->UserPhone,
                         'verify_phone' => $user->UserVerifyPhone,
                         'username' => $user->UserName,
                         'email' => $user->UserEmail,
                         'ip' => $user->UserIp,
                         'verify' => $user->UserVerify,
                         'time_created' => Utility::unixdatetime_to_text($user->UserCreationTimestamp),
                         'time_modified' => Utility::unixdatetime_to_text($user->UserModificationTimestamp),
                         
                        );
            }
              }


           }

            $arr2_count = Utility::getArrCount($arr2);
               return (array) array('referrals'=>$arr2, 'count'=>$arr2_count);

       }else {
     throw new Exception("Error Processing Request",1);
        }

  }



  public function credit_debit_wallet()
   {  
      
       $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
       $email = isset($this->params['email'])? Sanitize::cleanAll($this->params['email']) : false;
       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
       $amount = !empty($this->params['amount'])? Sanitize::cleanAll($this->params['amount']) : 0;
       $type = !empty($this->params['type'])? Sanitize::cleanAll($this->params['type']) : 0;
       $reason = !empty($this->params['reason'])? Sanitize::cleanAll($this->params['reason']) : false;
       $program = !empty($this->params['program'])? Sanitize::cleanAll($this->params['program']) : false;
       $this->common_model->_validateAmount($this->params);

       if (!in_array($type, array('credit','debit'))) {
          throw new Exception("Cant perform this Action.", 1);
       }


       if (!in_array($program, array('signup','order'))) {
          throw new Exception("This program is not valid.", 1);
       }

       if (isset($this->params['reason']) OR !empty($this->params['reason'])) { 

       if (strlen($this->params['reason']) > 200) {

                 throw new Exception('Reason should not be above 200 characters.');   
               
             } 

          }
 
        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

            $this->db->table_name = TBL_USERS;
            $obj2 = $this->db->find(array('UserEmail'=>strip_tags($email)));

          if ($obj2) {

            $this->db->table_name = TBL_USER_WALLET_HISTORY;
               $slug = $this->common_model->_generate_identifier($this->db,'WalletSlug',Utility::get_key(10));

            try{

                // begin the transaction
                 $this->db->beginTransaction();

                   $parameter = array(
                  'UserID' => $obj2->UserID,
                  'WalletAmount' => Number::precision($amount,2),
                  'WalletSlug' => $slug,
                  'WalletType' => $type,
                  'WalletProgram' => $program,
                  'WalletStatus' => 2,
                  'WalletIp' => Utility::getClientIP(),
                  'WalletCreationTimestamp' => $this->time
                       ); 

                 $this->db->table_name =TBL_USER_WALLET_HISTORY;
                      $this->db->create($parameter);

                 if($type == 'credit'){
                  $subject ='Wallet Credited With '.Number::currency($amount,'NGN');
                   $message = "Your wallet has been credited with the sum of ".Number::currency($amount,'NGN')."<br>".$reason;
                  }elseif ($type == 'debit') {
                  $subject = Number::currency($amount,'NGN').' Debited From Wallet';
                    $message = "The sum of ".Number::currency($amount,'NGN')." has been debited from your wallet. Please contact us if you feel this is wrong. <br>".$reason;
                  }
                 Mailer::sendMail($obj2,$subject,$message,$attachment=false);

                  $this->db->commit();
               
                 return true;
          }catch (PDOException $e) {
                    // rollback update
                    $this->db->rollback();
                    throw $e;
                   }

        }else {
         throw new Exception("The email you entered does not match any records in our database.",1);
            }

     }else {
         throw new Exception("Error Processing Request",1);
            }

  }




    public function fetchall_user_wallet_history()
   { 
       $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
       $user_id = isset($this->params['user_id'])? Sanitize::cleanAll($this->params['user_id']) : false;
       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
       $limit = !empty($this->params['limit'])? Sanitize::cleanAll($this->params['limit']) : 200;
       $start = !empty($this->params['start'])? Sanitize::cleanAll($this->params['start']) : 0;
 
        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

           $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

             $arr2 = array();
             $this->db->table_name = TBL_USER_WALLET_HISTORY;
             $obj2 = $this->db->find_all_pagination_param(array('UserID' => $user_id),'WalletCreationTimestamp','DESC', $start, $limit);
            if($obj2){

              foreach ($obj2 as $t) {
                     $arr2[] = array(
                      'id' => $t->WalletID,
                      'status' => $t->WalletStatus,
                      'user_id' => $t->UserID,
                      'type' => $t->WalletType,
                      'program' => $t->WalletProgram,
                      'amount' => Number::currency($t->WalletAmount, 'NGN'),
                      'slug' => $t->WalletSlug,
                      'ip' => $t->WalletIp,
                      'time' => Utility::unixdatetime_to_text($t->WalletCreationTimestamp),
                      'creation_timestamp' => $t->WalletCreationTimestamp,
                      'modification_timestamp' => $t->WalletModificationTimestamp
                              );
                         }
              }

            $arr2_count = Utility::getArrCount($arr2);
               return (array) array('history'=>$arr2, 'count'=>$arr2_count);

       }else {
     throw new Exception("Error Processing Request",1);
        }

  }




    public function fetchall_wallet_history()
   {  
     
       $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
       $limit = !empty($this->params['limit'])? Sanitize::cleanAll($this->params['limit']) : 200;
       $start = !empty($this->params['start'])? Sanitize::cleanAll($this->params['start']) : 0;
 
        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

             $this->db->table_name = TBL_USER_WALLET_HISTORY;
             $obj2 = $this->db->find_all_pagination('WalletCreationTimestamp','DESC', $start, $limit);
              $arr2 = array();
               if($obj2){
                  foreach ($obj2 as $t) {

                     $this->db->table_name = TBL_USERS;
               $obj3 = $this->db->find(array('UserID'=>$t->UserID));

               $this->db->table_name = TBL_USER_BANKS;
               $obj4 = $this->db->find(array('UserID'=>$t->UserID));

                        $arr2[] = array(
                        'id' => $t->WalletID, 
                         'user_id' => $t->UserID,  
                         'username' => !empty($obj3)? $obj3->UserName : 'Not found', 
                         'fullname' => !empty($obj3)? $obj3->UserFirstName.' '.$obj3->UserLastName : 'Not found',  
                         'email' => !empty($obj3)? $obj3->UserEmail : 'Not found', 
                         'phone' => !empty($obj3)? $obj3->UserPhone : 'Not found',
                         'bank_name' => !empty($obj4)? $obj4->BankMame : 'Not found', 
                         'acc_name' => !empty($obj4)? $obj4->BankAccName : 'Not found', 
                         'acc_number' => !empty($obj4)? $obj4->BankAccNumber : 'Not found', 
                         'acc_type' => !empty($obj4)? $obj4->BankAccType : 'Not found',  
                         'amount' => Number::currency($t->WalletAmount, 'NGN'),
                         'raw_amount' =>  Number::precision($t->WalletAmount,2),
                         'status' => $t->WalletStatus,
                         'slug' => $t->WalletSlug,
                         'type' => $t->WalletType,
                         'program' => $t->WalletProgram,
                         'ip' => $t->WalletIp,
                         'time_created' => Utility::unixdatetime_to_text($t->WalletCreationTimestamp),
                         'time_modified' => Utility::unixdatetime_to_text($t->WalletModificationTimestamp),
                         
                         
                        );
                  }
               }

              // var_dump($arr3); 
               $arr2_count = Utility::getArrCount($arr2);
               return (array) array('transactions'=>$arr2, 'count'=>$arr2_count);

           }else {
         throw new Exception("Error Processing Request",1);
            }

      
   }




   public function searchall_wallet_history()
   {  
     
        if(empty($this->params['keyword'])){
          throw new Exception("Please enter keyword and avoid entering 0", 1);
        }

       $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
       $keyword = !empty($this->params['keyword'])? Sanitize::cleanAll($this->params['keyword']) : false;
       ;
       $type = !empty($this->params['type'])? Sanitize::cleanAll($this->params['type']) : false;
       $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;



        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

              $this->db->table_name = TBL_USER_WALLET_HISTORY;
           
              switch ($type) {
                case 'userid':
                 $obj2_params = array('UserID' => $keyword);
                  break;
                case 'slug':
                $obj2_params = array('WalletSlug' => $keyword);
                  break;
          
                 case 'status':
                 $obj2_params = array('WalletStatus' => $keyword);
                  break;
                 case 'type':
                 $obj2_params = array('WalletType' => $keyword);
                  break;
                 case 'program':
                 $obj2_params = array('WalletProgram' => $keyword);
                  break;               
                default:
                  $obj2_params = array('WalletID' => $keyword);
                  break;
              }

              $obj2 =  $this->db->find_all_search_pagination($obj2_params,'WalletCreationTimestamp','DESC',0,100000);

              $arr2 = array();

              if($obj2){
                  foreach ($obj2 as $t) {

                     $this->db->table_name = TBL_USERS;
               $obj3 = $this->db->find(array('UserID'=>$t->UserID));

               $this->db->table_name = TBL_USER_BANKS;
               $obj4 = $this->db->find(array('UserID'=>$t->UserID));

                        $arr2[] = array(
                         'id' => $t->WalletID, 
                         'user_id' => $t->UserID,  
                         'username' => !empty($obj3)? $obj3->UserName : 'Not found', 
                         'fullname' => !empty($obj3)? $obj3->UserFirstName.' '.$obj3->UserLastName : 'Not found',  
                         'email' => !empty($obj3)? $obj3->UserEmail : 'Not found', 
                         'phone' => !empty($obj3)? $obj3->UserPhone : 'Not found',
                         'bank_name' => !empty($obj4)? $obj4->BankMame : 'Not found', 
                         'acc_name' => !empty($obj4)? $obj4->BankAccName : 'Not found', 
                         'acc_number' => !empty($obj4)? $obj4->BankAccNumber : 'Not found', 
                         'acc_type' => !empty($obj4)? $obj4->BankAccType : 'Not found',  
                         'amount' => Number::currency($t->WalletAmount, 'NGN'),
                         'raw_amount' =>  Number::precision($t->WalletAmount,2),
                         'status' => $t->WalletStatus,
                         'slug' => $t->WalletSlug,
                         'type' => $t->WalletType,
                         'program' => $t->WalletProgram,
                         'ip' => $t->WalletIp,
                         'time_created' => Utility::unixdatetime_to_text($t->WalletCreationTimestamp),
                         'time_modified' => Utility::unixdatetime_to_text($t->WalletModificationTimestamp),
                         
                        );
                  }
               }

              // var_dump($arr3); 

                $arr2_count = Utility::getArrCount($arr2);
               return (array) array('transactions'=>$arr2, 'count'=>$arr2_count);

           }else {
         throw new Exception("Error Processing Request",1);
            }
            

   }


 



  public function confirm_wallet_history()
   {
    
        $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
        $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;


        $transaction_id = isset($this->params['transaction_id'])? Sanitize::cleanAll($this->params['transaction_id']) : false;

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {
            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

            $this->db->table_name =TBL_USER_WALLET_HISTORY;
            $obj2 = $this->db->find(array('WalletID'=> $transaction_id));

            if($obj2){

               $this->db->table_name =TBL_USERS;
              $obj3 = $this->db->find(array('UserID'=>$obj2->UserID));

              if($obj3){
                 
               try{
                // begin the transaction
                 $this->db->beginTransaction();

                $this->db->table_name =TBL_USER_WALLET_HISTORY;
                 $parameter = array(
                  'WalletStatus' => 2,
                  'WalletModificationTimestamp' => $this->time
                       ); 
                  $this->db->update($parameter,array('WalletID'=>$obj2->WalletID));

                  $subject ='Withdrawal from '.$obj2->program.' earnings approved!';
                  $message = "Your Withdrawal from ".$obj2->program." earnings has been approved successfully. Please check your bank account associated with us for your money.";
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

       }




   public function cancel_wallet_history()
   {

        $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
        $transaction_id = isset($this->params['transaction_id'])? Sanitize::cleanAll($this->params['transaction_id']) : false;
        $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

            $this->db->table_name =TBL_USER_WALLET_HISTORY;
            $obj2 = $this->db->find(array('WalletID'=>$transaction_id));

            if($obj2){

              $this->db->table_name =TBL_USERS;
              $obj3 = $this->db->find(array('UserID'=>$obj2->UserID));

              if($obj3){

               try{
               $this->db->table_name =TBL_USER_WALLET_HISTORY;
                // begin the transaction
                 $this->db->beginTransaction();
                
                 $this->db->delete('WalletID',strip_tags($transaction_id)); 

                 $subject ='Withdrawal from '.$obj2->program.' earnings disapproved!';
                 $message = "Your Withdrawal from ".$obj2->program." earnings has been disapproved. Please contact us for more info.";
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

       }



 public function bulk_wallet_history_action()
  { 

         $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
         $type = isset($this->params['type'])? Sanitize::cleanAll($this->params['type']) : false;
         $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
         $transactions = !empty($this->params['transactions'])? Sanitize::cleanAll($this->params['transactions']) : array();

         $this->db->table_name = TBL_USERS;

          $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));
            
           if($obj1){

              $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));
             
              $this->db->table_name =TBL_USER_WALLET_HISTORY;
              
              switch ($type) {
                case 'delete':
                  try{
                  // begin the transaction
                   $this->db->beginTransaction();
                   foreach ($transactions as $t) {
                       
                       $this->db->table_name =TBL_USER_WALLET_HISTORY;
                        $obj2 = $this->db->find(array('WalletID'=> $t));

                      if($obj2){

                      $this->db->delete('WalletID',strip_tags($t));

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

    }
  


  
   public function withdraw()
  {  
         $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
         $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
         $amount = !empty($this->params['amount'])? Sanitize::cleanAll($this->params['amount']) : 0;
         $program = !empty($this->params['program'])? Sanitize::cleanAll($this->params['program']) : false;
          
         $this->common_model->_validateAmount($this->params);
     
          $obj1 = $this->db->find(array('UserID'=>$id));

          if($obj1){

           $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true));

             $wallet = $this->common_model->_walletStatistics($obj1);

             $this->db->table_name = TBL_SETTINGS;
             $minimum_cashout = $this->db->find(array('SettingsParam'=>'minimum_cashout'));

             if ($amount > $wallet['raw_available_withdrawal']) {

                throw new Exception("You cant withdraw more than ". $wallet['available_withdrawal'], 1);
               };

             if ($wallet['raw_available_withdrawal'] < $minimum_cashout->SettingsValue) {

              throw new Exception("You are not eligible to make Withdrawals yet for this program. Your balance must be above ".Number::currency($minimum_cashout->SettingsValue, 'NGN'), 1);
               }


             if ($wallet['raw_total_pending_debit'] > 0) {

            throw new Exception("You last withdraw of ". $wallet['total_pending_debit'].' has not been approved yet.', 1);
            };

        
             $this->db->table_name = TBL_USER_WALLET_HISTORY;
             $slug = $this->common_model->_generate_identifier($this->db,'WalletSlug',Utility::get_key(10));
          
           try{
                // begin the transaction
                 $this->db->beginTransaction();

               $parameter = array(
                'UserID' => $obj1->UserID,
                'WalletAmount' => $amount,
                'WalletSlug' => $slug,
                'WalletType' => 'debit',
                'WalletProgram' => $program,
                'WalletStatus' => 1,
                'WalletIp' => Utility::getClientIP(),
                'WalletCreationTimestamp' => $this->time
               ); 
             
         $this->db->create($parameter);

         $this->db->commit();

                 return true;

                 } catch (PDOException $e) {
                // rollback update
                $this->db->rollback();
                throw $e;
                  }
             
            }else{

          throw new Exception("Error Processing Request: Can not fetch user".$params['id']);
         }



    }



  public function update()
  { 
    
         $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
         $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
           
           
         $this->_validateUser($this->params,false,'update');
     
          $obj1 = $this->db->find(array('UserID'=>$id));

          if($obj1){
           $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true));
           //check if username exist

            $this->db->table_name = TBL_USERS;
           if($obj1->UserName != $this->params['username']){
             if($this->db->find(array('UserName'=>$this->params['username']))){
              throw new Exception('This username is already assigned to a user.');
             }
           }

           if($obj1->UserEmail != $this->params['email']){
            //check if email exist
             if($this->db->find(array('UserEmail'=>$this->params['email']))) {
              throw new Exception('This email is already assigned to a user.');
             }
            }


           //put the user login parameters into an array
             $parameter1 = array(
              'UserName' => $this->params['username'],
              'UserEmail' => isset($this->params['email'])? $this->params['email'] : false,
              'UserFirstName' => Sanitize::cleanAll($this->params['firstname']),
              'UserLastName' => Sanitize::cleanAll($this->params['lastname']),
              'UserCity' => Sanitize::cleanAll($this->params['city']),
              'UserState' => Sanitize::cleanAll($this->params['state']),
              'UserZip' => Sanitize::cleanAll($this->params['zip']),
              'UserFax' => Sanitize::cleanAll($this->params['fax']),
              'UserCountry' => Sanitize::cleanAll($this->params['country']),
              'UserAddress' => Sanitize::cleanAll($this->params['address']),
              //'UserAddress2' => Sanitize::cleanAll($this->params['address2']),
              'UserSettings' => serialize($this->params['preference']),
              'UserPhone' => Sanitize::cleanAll($this->params['phone']),
              //'UserIp' => Utility::getClientIP(),
              'UserModificationTimestamp' => $this->time
             ); 
             $this->db->table_name = TBL_USERS; 
             $feedback1 = $this->db->update($parameter1,array('UserID'=>$obj1->UserID));
             

            if($feedback1){
               //if everything goes well, we then update the users bank data
               $this->_update_bank_alt($obj1,$this->params);

               $this->db->table_name = TBL_USERS;
              //perform a fetch action here
              $feedback = $this->db->find(array('UserID'=> $obj1->UserID));

             if($feedback){

                  $feedback->session = $this->_getUserSession($feedback->UserID); 
                  $feedback->block = Utility::checkTimeRemainingAlt($feedback->UserBlockTimestamp,$this->block_period);
                  $feedback->UserSettings = $this->common_model->_getUserSettings($feedback);
                  $feedback->avatar_url = $this->common_model->_getUserAvatarUrl($feedback->UserID);

                  return $feedback;
               
           }else{
            return false;
           }
                   //return ($user) ? $user: false;

               }else{

                throw new Exception("Error Processing Request");
                
               }

            }else{

          throw new Exception("Error Processing Request");  
          
            }
      }


  private function _update_bank_alt($obj1=false,$params=false)
     { 
   
       if(!empty($params) && !empty($obj1)){

            $this->db->table_name =TBL_USER_BANKS;
            $obj2 = $this->db->find(array('UserID'=>$obj1->UserID));
            if($obj2){
         
             $parameter = array(
                'BankName' => Sanitize::cleanAll($params['bank_name']),
                'BankAccName' => Sanitize::cleanAll($params['acc_name']),
                'BankAccNumber' => Sanitize::cleanAll($params['acc_number']),
                'BankAccType' => Sanitize::cleanAll($params['acc_type']),
                'BankIp' => Utility::getClientIP(),
                'BankCreationTimestamp' => $this->time
                ); 
           
                $feedback = $this->db->update($parameter,array('BankID'=>$obj2->BankID));

               }else{

             $slug = $this->common_model->_generate_identifier($this->db,'BankSlug',Utility::get_key(10));
         
               $parameter = array(
                'UserID' => $obj1->UserID,
                'BankName' => Sanitize::cleanAll($params['bank_name']),
                'BankAccName' => Sanitize::cleanAll($params['acc_name']),
                'BankAccNumber' => Sanitize::cleanAll($params['acc_number']),
                'BankAccType' => Sanitize::cleanAll($params['acc_type']),
                'BankSlug' => $slug,
                'BankStatus' => 2,
                'BankIp' => Utility::getClientIP(),
                'BankCreationTimestamp' => $this->time
                ); 
             
               $feedback = $this->db->create($parameter);

               }
             
             if($feedback){

                return true;

               }else{

                throw new Exception("Error Updating Bank data, please try again later");  
               }

       }else{

        throw new Exception("Error Processing Request");  
       }

    }



  public function update_bank()
     { 
    //decode the post data
      $params = $this->params;
         
       if(!empty($params)){

         $token = !empty($params['token'])? Sanitize::cleanAll($params['token']) : false;
         $id = !empty($params['id'])? Sanitize::cleanAll($params['id']) : false;
         //lets do the validating
         $this->_validateBank($params);
           
          $obj1 = $this->db->find(array('UserID'=>$id));

          if($obj1){
           
           $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true));

            $this->db->table_name =TBL_USER_BANKS;
            $obj2 = $this->db->find(array('UserID'=>$obj1->UserID));
            if($obj2){
         
          
             $parameter = array(
                'BankName' => Sanitize::cleanAll($params['bank_name']),
                'BankAccName' => Sanitize::cleanAll($params['acc_name']),
                'BankAccNumber' => Sanitize::cleanAll($params['acc_number']),
                'BankAccType' => Sanitize::cleanAll($params['acc_type']),
                'BankIp' => Utility::getClientIP(),
                'BankCreationTimestamp' => $this->time
                ); 
           
                $feedback1 = $this->db->update($parameter,array('BankID'=>$obj2->BankID));

               }else{

            $slug = $this->common_model->_generate_identifier($this->db,'BankSlug',Utility::get_key(10));
            
               $parameter = array(
                'UserID' => $obj1->UserID,
                'BankName' => Sanitize::cleanAll($params['bank_name']),
                'BankAccName' => Sanitize::cleanAll($params['acc_name']),
                'BankAccNumber' => Sanitize::cleanAll($params['acc_number']),
                'BankAccType' => Sanitize::cleanAll($params['acc_type']),
                'BankSlug' => $slug,
                'BankStatus' => 2,
                'BankIp' => Utility::getClientIP(),
                'BankCreationTimestamp' => $this->time
                ); 
             
               $feedback1 = $this->db->create($parameter);

               }
             
             if($feedback1){

                $obj1->session = $this->_getUserSession($obj1->UserID);
                //lets get the users avatar
                $obj1->avatar_url = $this->common_model->_getUserAvatarUrl($obj1->UserID);
                $obj1->UserSettings = $this->common_model->_getUserSettings($obj1);

                return $obj1;

               }else{

                throw new Exception("Error Processing Request");  
               }

            }else{

          throw new Exception("Error Processing Request");  
            }

       }else{

        throw new Exception("Error Processing Request");  
       }


    }
  


  public function update_password()
  { 

         $this->_validateUserPassword($this->params,'new');
          //$this->_validateCaptcha($this->params);
          $email =  Sanitize::cleanAll($this->params['email']);
            //hash the password
            $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
            $password_hash = password_hash($this->params['password'], PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

          $user_exist = $this->db->find(array('UserEmail'=>$email));

          if($user_exist){

              if($this->_checkOtp($obj1->UserID,$this->params['otp'],'password_reset')){
        
                   $parameter = array(
              'UserPassword' => $password_hash,
              'UserModificationTimestamp' => $this->time
                   ); 
               
                   $feedback = $this->db->update($parameter,array('UserID'=>$user_exist->UserID));

              return ($feedback)? true : false;

          }else{
              throw new Exception('Sorry, The Otp you entered is invalid.');
            return false; 
         }

          }else{

          throw new Exception("Error Processing Request");
          
          }
     

    }


   public function update_password_self()
  { 

         $this->_validateUserPassword($this->params,'old');
         //$this->_validateCaptcha($this->params);
         $old_password = isset($this->params['old_password'])? $this->params['old_password'] : false;
            //hash the password
            $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
            $password_hash = password_hash($this->params['password'], PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
            $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;

          $user_exist = $this->db->find(array('UserID'=>$this->params['id']));

          if($user_exist){

            $this->common_model->_checkUser($user_exist,$token,$config=array('session'=>true,'status'=>true));  
            // check if hash of provided password matches the hash in the database
              if (password_verify($old_password, $user_exist->UserPassword)) {

               $parameter = array(
          'UserPassword' => $password_hash,
          'UserModificationTimestamp' => $this->time
               ); 
                 $this->db->table_name = TBL_USERS;
                   $feedback = $this->db->update($parameter,array('UserID'=>$user_exist->UserID));

                   return ($feedback) ? true: false;


                  }else{

                    throw new Exception('The old password you entered is wrong.');
                  }
       

          }else{

          throw new Exception("Error Processing Request");
          
          }
     

      
    }



  

  public function request_otp($config=array())
  { 
       if(!empty($config)){

        $type = !empty($config['type'])? Sanitize::cleanAll($config['type']) : false;
        $medium = !empty($config['medium'])? Sanitize::cleanAll($config['medium']) : 'email';
        $email = !empty($this->params['email'])? Sanitize::cleanAll($this->params['email']) : false;
        
        if(empty($this->params['email'])){
          throw new Exception('Sorry, we could not send the otp at the moment, please try again later.', 1);
        }
       
        $this->db->table_name = TBL_USERS;
        $obj1 = $this->db->find(array('UserEmail'=>$email));

         if ($obj1) {
            //lets generate the otp code
            $this->_generateOtp($obj1->UserID,$type,$medium);

           }else{

          throw new Exception("Error Processing Request");
        
         }
         }else{

        throw new Exception("Error Processing Request");
        
       }

  }


    public function resend_activation()
  {

   $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
   $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;

    $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

    if($obj1){
      
      $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true));

      if ($obj1->UserStatus == 1) {
         $activation_code = rand(1,substr(time(),3));
               $activation_hash = sha1(uniqid(mt_rand(), true));
               $parameter = array(
                  'UserVerificationCode' => $activation_code,
                  'UserVerificationHash' => $activation_hash,
                  'UserVerificationTimestamp' => $this->time
                ); 
         $this->db->table_name = TBL_USERS;

         $feedback = $this->db->update($parameter,array('UserID'=>$obj1->UserID));

         if ($feedback) {
           $obj1->UserVerificationHash = $activation_hash;
                 $obj1->UserVerificationCode = $activation_code;
           $mail = Mailer::sendVerificationEmail($obj1);
           return ($mail)? true : false;
         }else{
          throw new Exception("Sorry, we can not process your request at the moment.", 1);
         } 

      }else{
               throw new Exception("Sorry, Your account is already verified.", 1);
      }
     }else{
      throw new Exception("Sorry we counld not identify your email address, please try again.", 1);
     }

    }

 

    public function verify_account()
  {
   //decode the post data
      $params = $this->params;
         
      if(!empty($params)){
     
    //perform a fetch action here
    $obj1 = $this->db->find(array('UserID'=>$params['user_id'],'UserVerificationHash'=>$params['verification_hash'],'UserStatus'=>1));
     
     if($obj1){

       $feedback = $this->_verifyUser($obj1);
       return ($feedback)? true : false;

        }else{

        throw new Exception('Sorry, no such id/verification code combination here...');
      return false; 
       }
    // throw new Exception($feedback);
    //if saving was not successful, throw an exception
    }else{ throw new Exception("Error Processing Request",1); }
   
    
  }



  public function verify_account_alt()
  {

   $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
   $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
   $code = !empty($this->params['token'])? Sanitize::cleanAll($this->params['code']) : false;

   if (!isset($code) OR empty($code)) { 

       throw new Exception('Please enter activation code.');
   
     }

    $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

    if($obj1){
      
      $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true));
      $obj1->session = $this->_getUserSession($obj1->UserID);
    //lets get the users avatar
    $obj1->avatar_url = $this->common_model->_getUserAvatarUrl($obj1->UserID);

      if ($obj1->UserStatus == 1 && $obj1->UserVerificationCode == $code) {
         
         $feedback = $this->_verifyUser($obj1);

          return ($feedback)? $obj1 : false;

       }else{
               throw new Exception("Sorry, we can not process your request at the moment due to wrong activation code.", 1);
      }

     }else{
      throw new Exception("Sorry we counld not identify your email address, please try again.", 1);
     }

   }


  public function verify_reset_otp()
  {
  
       $email =  Sanitize::cleanAll($this->params['email']);
      $obj1 = $this->db->find(array('UserEmail'=>$email));

         if($obj1){

          //lets check if the otp is valid
            $this->_checkOtp($obj1->UserID,$this->params['otp'],'password_reset');
       

          }else{ throw new Exception("Error Processing Request",1); }
    
  }


    public function verify_login()
  {
   //decode the post data
      $params = $this->params;
         
      if(!empty($params)){
      //$this->_validateCaptcha($params);
         $email =  Sanitize::cleanAll($params['email']);
       //perform a fetch action here
         $feedback = $this->db->find(array('UserEmail'=>$email));

           if($feedback){

           $feedback->UserSettings = $this->common_model->_getUserSettings($feedback);

             if ($this->_checkOtp($feedback->UserID,$this->params['otp'],'login')) {
             
              //update the user's session and return a token
                $feedback->session = $this->_updateUserSession($feedback,'verified');
                $feedback->block = Utility::checkTimeRemainingAlt($feedback->UserBlockTimestamp,$this->block_period);
                $feedback->TwoFactor = false;
                //lets get the users avatar
              $feedback->avatar_url = $this->common_model->_getUserAvatarUrl($feedback->UserID);
                       
              return $feedback;

          }else{

              throw new Exception('Sorry, The Otp you entered is invalid.');
            return false; 
         }

          }else{ throw new Exception("Error Processing Request",1); }
    // throw new Exception($feedback);
    //if saving was not successful, throw an exception
    }else{ throw new Exception("Error Processing Request",1); }
  }





  public function verify_token()
  {
  
         $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
         $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
        //perform a fetch action here
          $this->db->table_name = TBL_USERS;
        $feedback = $this->db->find(array('UserID'=>$id));
           if($feedback){
            
             if ($this->common_model->_checkUserSession($feedback->UserID,$token)) {
                   $feedback->avatar_url = $this->common_model->_getUserAvatarUrl($feedback->UserID);
                   $feedback->session = $this->_getUserSession($feedback->UserID);
                   $feedback->block = Utility::checkTimeRemainingAlt($feedback->UserBlockTimestamp,$this->block_period);
                   $feedback->UserSettings = $this->common_model->_getUserSettings($feedback);
              return $feedback;
           }else{

           throw new Exception("Sorry, We were unable to verify your session. Please login again.",1);
         }

          }else{ throw new Exception("Sorry, We were unable to verify your session. Please login again.",1); }
  
  }





  public function upload_avatar()
  {
      if(empty($this->params['file'])){
        throw new Exception("Please select an image file to proceed", 1);
        
        }

      //$this->_validateCaptcha($this->params);
        $id =  Sanitize::cleanAll($this->params['id']);
        $token = Sanitize::cleanAll($this->params['token']);
        
         //perform a fetch action here
         $obj1 = $this->db->find(array('UserID'=>$id));

           if($obj1){

            $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true));

           $this->db->table_name = TBL_USER_FILES;
            //perform a fetch action here
         $obj2 = $this->db->find(array('UserID'=>$obj1->UserID,'FileType'=>'avatar')); 


          $slug = $this->common_model->_generate_identifier($this->db,'FileSlug',Utility::get_key(23));

        #############  INITIATE UPLOAD ###########################
            $uploader = new Uploader();

            $uploader->file_name = $this->params["file"]["name"]; //original name of file
          $uploader->file_tmp_name = $this->params["file"]["tmp_name"]; //temporary file for upload
          $uploader->file_size = $this->params["file"]["size"]; //size of file
          $uploader->file_error =$this->params["file"]["error"]; //file error(s)
          $uploader->file_type = $this->params["file"]["type"]; //file type
          $uploader->slug = $slug; //unique slug of file

          //set system settings
          $uploader->ext_type = array('jpeg','jpg','JPG','JPEG','png'); //Accepted extension types
          $uploader->mime_type = array('image/jpeg','image/jpg','image/png'); //Accepted mime types
          $uploader->location =  $this->userAvatarDataPath; //location where files should be uploaded
          $uploader->max_size = 1000000; //1 mb max //Allowed maximum size
        
            $file = $uploader->upload();
            //if saving was not successful, throw an exception
            if( $file === false ) {
          throw new Exception('Failed to save');
        }

        #############  END INITIATE UPLOAD ###########################
          try{
                  // begin the transaction
                  $this->db->beginTransaction();

          // check if the user is present in the user avatar tbl
            if($obj2){
           //if saving was not successful, throw an exception
            
            if(!empty($obj2->FileImgPath) && !empty($obj2->FileSlug) && !empty($obj2->FileExt)){
            unlink($this->userAvatarDataPath.$obj2->FileImgPath.$obj2->FileSlug.'.'.$obj2->FileExt);
            }
            
            $parameter = array(
              'FileImgPath' => $file['path'],
              'FileSlug' => $slug,
              'FileType' => 'avatar',
              'FileExt' =>  $file['ext'],
              'FileSize' => $file['size'],
              'FileModificationTimestamp' => $this->time
                  );

                  $this->db->update($parameter,array('FileID'=>$obj2->FileID));

              } else {

          
            $parameter = array(
              'UserID' => $obj1->UserID,
              'FileImgPath' => $file['path'],
              'FileSlug' => $slug,
              'FileType' => 'avatar',
              'FileExt' =>  $file['ext'],
              'FileSize' => $file['size'],
              'FileModificationTimestamp' => $this->time
                  );

            $this->db->create($parameter);

            }

          $obj1->session = $this->_getUserSession($obj1->UserID);
          //lets get the users avatar
          $obj1->avatar_url = $this->common_model->_getUserAvatarUrl($obj1->UserID);
          $obj1->block = Utility::checkTimeRemainingAlt($obj1->UserBlockTimestamp,$this->block_period);
          $obj1->UserSettings = $this->common_model->_getUserSettings($obj1);
                 
            $this->db->commit();
                   return $obj1;

                 } catch (PDOException $e) {
                   // rollback update
                   $this->db->rollback();
                   throw $e;
                 }

          }else{ throw new Exception("Error Processing Request",1); }
    // throw new Exception($feedback);
    
  }



  
    public function update_account()
  { 
    
         $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
         $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
         $userid = !empty($this->params['userid'])? Sanitize::cleanAll($this->params['userid']) : false;
           
           
         $this->_validateUser($this->params,true,'update');

     
          $admin_exist = $this->db->find(array('UserID'=>$id));

          if($admin_exist){

           $this->common_model->_checkUser($admin_exist,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

           $this->db->table_name = TBL_USERS;
           $user_exist = $this->db->find(array('UserID'=>$userid));

          if($user_exist){

           //check if username exist
           if($user_exist->UserName != $this->params['username']){
             if($this->db->find(array('UserName'=>$this->params['username']))){
              throw new Exception('This username is already assigned to a user.');
               }
             }

           if($user_exist->UserEmail != $this->params['email']){
            //check if email exist
             if($this->db->find(array('UserEmail'=>$this->params['email']))) {
              throw new Exception('This email is already assigned to a user.');
               }
              }


            //  if($user_exist->UserPhone != $this->params['phone']){
            // //check if email exist
             // if($this->db->find(array('UserPhone'=>$this->params['phone']))) {
             //   throw new Exception('This phone number is already assigned to a user.');
             //   }
            //   }


            try{
               
               // begin the transaction
             $this->db->beginTransaction();
    
             //put the user login parameters into an array
               $parameter = array(
              'UserName' => $this->params['username'],
              'UserEmail' => isset($this->params['email'])? $this->params['email'] : false,
              'UserFirstName' => Sanitize::cleanAll($this->params['firstname']),
              'UserLastName' => Sanitize::cleanAll($this->params['lastname']),
              'UserCity' => Sanitize::cleanAll($this->params['city']),
              'UserState' => Sanitize::cleanAll($this->params['state']),
              'UserZip' => Sanitize::cleanAll($this->params['zip']),
              'UserFax' => Sanitize::cleanAll($this->params['fax']),
              'UserCountry' => Sanitize::cleanAll($this->params['country']),
              'UserAddress' => Sanitize::cleanAll($this->params['address']),
              'UserVerified' => Sanitize::cleanAll($this->params['verified']),
              'UserEmailVerified' => Sanitize::cleanAll($this->params['email_verified']),
              'UserPhoneVerified' => Sanitize::cleanAll($this->params['phone_verified']),
              'UserLevel' => Sanitize::cleanAll($this->params['level']),
              'UserStatus' => Sanitize::cleanAll($this->params['status']),
              //'UserAddress2' => Sanitize::cleanAll($this->params['address2']),
              'UserSettings' => serialize($this->params['preference']),
              'UserPhone' => Sanitize::cleanAll($this->params['phone']),
              //'UserIp' => Utility::getClientIP(),
              'UserModificationTimestamp' => $this->time
               ); 
           
                $this->db->update($parameter,array('UserID'=>$user_exist->UserID));

               $this->db->commit();
              //if everything goes well, we then update the users bank data
               $this->_update_bank_alt($user_exist,$this->params);
               return true;

                } catch (PDOException $e) {
                   // rollback update
                   $this->db->rollback();
                  throw $e;
                 }
             
            }else{

          throw new Exception("Error Processing Request");
          
            }

          }else{

          throw new Exception("Error Processing Request");
          
         }
        
     
    }



  public function bulk_action()
  { 

 
         $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
         $type = isset($this->params['type'])? Sanitize::cleanAll($this->params['type']) : false;
         $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
         $users = !empty($this->params['users'])? Sanitize::cleanAll($this->params['users']) : array();

         $this->db->table_name = TBL_USERS;

          $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));
            
           if($obj1){

              $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

              switch ($type) {
                case 'delete':
                  try{
                  // begin the transaction
                   $this->db->beginTransaction();
                   foreach ($users as $u) {
                      
                      $this->db->table_name = TBL_USERS;
                      $this->db->delete('UserID',strip_tags($u));
                      $this->db->table_name = TBL_USER_SESSION;
                      $this->db->delete('UserID',strip_tags($u));
                      $this->db->table_name = TBL_USER_OTP;
                      $this->db->delete('UserID',strip_tags($u));
                      $this->db->table_name = TBL_USER_FILES;
                      $this->db->delete('UserID',strip_tags($u));
                      $this->db->table_name = TBL_USER_REFERRALS;
                      $this->db->delete('UserID',strip_tags($u));
                      $this->db->table_name = TBL_USER_WALLET_HISTORY;
                      $this->db->delete('UserID',strip_tags($u));

                      
                      
              
                     }

                     $this->db->commit();
                     return true;

                   } catch (PDOException $e) {
                    // rollback update
                    $this->db->rollback();
                    throw $e;
                    }
                
                  break;

                
                case 'blocking':
                  try{
                      // begin the transaction
                      $this->db->beginTransaction();
                      
                       foreach ($users as $u) {
                    
                      $parameter = array(
              'UserStatus' => 3
                   ); 
                $this->db->table_name = TBL_USERS;
                    $this->db->update($parameter,array('UserID'=>$u));

                          }

                         $this->db->commit();
                         return true;
     
                         } catch (PDOException $e) {
                        // rollback update
                        $this->db->rollback();
                        throw $e;
                        }

                  break;

                    case 'activate':
                  try{
                      // begin the transaction
                   $this->db->beginTransaction();
                      
                       foreach ($users as $u) {
                    
                      $parameter = array(
              'UserStatus' => 2
                   ); 
                $this->db->table_name = TBL_USERS;
                    $this->db->update($parameter,array('UserID'=>$u));

                          }

                         $this->db->commit();
                         return true;
     
                         } catch (PDOException $e) {
                        // rollback update
                        $this->db->rollback();
                        throw $e;
                        }

                  break;


                 case 'verify':
                  try{
                      // begin the transaction
                   $this->db->beginTransaction();
                      
                       foreach ($users as $u) {
                    
                      $parameter = array(
              'UserVerified' => 2
                   ); 
                $this->db->table_name = TBL_USERS;
                    $this->db->update($parameter,array('UserID'=>$u));

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

    }
  




     public function fetch_statistics()
   {  
     
       $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
        $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
 

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

              $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

              $this->db->table_name = TBL_USERS;
              $obj2 = $this->db->find_all('UserID','DESC');

               $this->db->table_name = TBL_ORDERS;
               $obj3 = $this->db->find_all('OrderID','DESC');

              $this->db->table_name = TBL_USER_REFERRALS;
              $obj5 = $this->db->find_all('ReferralID','DESC');

              $this->db->table_name = TBL_USER_WALLET_HISTORY;
              $obj6 = $this->db->find_all('WalletID','DESC');
              
              $active_users = 0;
              $inactive_users = 0;
              $blocked_users = 0;
              $pending_orders = 0;
              $locked_orders = 0;
              $verified_orders = 0;

               if($obj2){
                 foreach ($obj2 as $user) {
                  if($user->UserStatus == 1){
                   $inactive_users += +1;
                  }
                  if($user->UserStatus == 2){
                   $active_users += +1;
                  }
                  if($user->UserStatus == 3){
                   $blocked_users += +1;
                  }
                 }
                }

               //   if($obj3){
               //    foreach ($obj3 as $orders) {
               //     if($orders->OrderStatus == 1){
                //   $pending_orders += +1;
               //     }
               //     if($orders->OrderStatus == 2){
                //   $locked_orders += +1;
               //     }
               //     if($orders->OrderStatus == 3){
                //   $verified_orders += +1;
               //     }
               //    }

               // }


               $total_successful_credit = 0;
               $total_successful_debit = 0;
               $total_pending_debit = 0;

                if($obj6){
                 foreach ($obj6 as $h) {
                  if($h->WalletStatus == 2 && $h->WalletType == 'credit'){
                   $total_successful_credit += +$h->WalletAmount;
                  }
                  if($h->WalletStatus == 2 && $h->WalletType == 'debit'){
                   $total_successful_debit += +$h->WalletAmount;
                  }
                  if($h->WalletStatus == 1 && $h->WalletType == 'debit'){
                   $total_pending_debit += +$h->WalletAmount;
                  }
                  }
                }


              // var_dump($arr3); 
               $users = Utility::getArrCount($obj2);
               $orders = Utility::getArrCount($obj3);
               $referrals = Utility::getArrCount($obj5);

              return (array) array('users'=>$users, 
                'active_users'=>$active_users, 
                'inactive_users'=>$inactive_users, 
                'blocked_users'=>$blocked_users,  
                'orders'=>$orders, 
                'pending_orders'=>$pending_orders, 
                'verified_orders'=>$verified_orders, 
                'locked_orders'=>$locked_orders, 
                'total_referrals'=>$referrals,
                'total_successful_credit'=>Number::precision($total_successful_credit,2),
                  'total_successful_debit'=>Number::precision($total_successful_debit,2),
                  'total_pending_debit'=>Number::precision($total_pending_debit,2));

           }else {
         throw new Exception("Error Processing Request",1);
            }
    
   }



    public function fetch_settings()
   {  
    
        $id = isset($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
        $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
 

        $this->db->table_name = TBL_USERS;

        $obj1 = $this->db->find(array('UserID'=>strip_tags($id)));

        if ($obj1) {

          $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

            $this->db->table_name = TBL_SETTINGS;
            $obj2 = $this->db->find_all('SettingsID','DESC');
            $settings = array();

            if($obj2){

                foreach ($obj2 as $config) {

                  $settings[$config->SettingsParam] = $config->SettingsValue;
                  if($config->SettingsParam == 'notification_emails'){
                  $settings[$config->SettingsParam] = unserialize($config->SettingsValue);
                  }
                }

                return (array) $settings;

            }else{
              throw new Exception("Error Processing Request", 1);
            }
            
           }else {
         throw new Exception("Error Processing Request",1);
            }
       
   }



  

   public function update_settings()
  { 

         $token = !empty($this->params['token'])? Sanitize::cleanAll($this->params['token']) : false;
         $id = !empty($this->params['id'])? Sanitize::cleanAll($this->params['id']) : false;
           
         $this->_validateSettings($this->params);

          $obj1 = $this->db->find(array('UserID'=>$id));

          if($obj1){

           $this->common_model->_checkUser($obj1,$token,$config=array('session'=>true,'status'=>true,'admin'=>true));

              $this->db->table_name = TBL_SETTINGS;
            $obj2 = $this->db->find_all('SettingsID','DESC');
            $settings = array();

            $notification_emails = array();
            if (!empty($this->params['notification_emails'])) {
              $emails = array();
              foreach ($this->params['notification_emails'] as $key => $e) {
                $emails[] = $e['text'];
               }
              $notification_emails = $emails;
            }

            if (Utility::getArrCount($emails) > 5) {
               throw new Exception("You have exceeded the max allowed emails to provide.", 1); 
            }

            $configs = array(
          'referral_signup_bonus' => Sanitize::cleanAll($this->params['referral_signup_bonus']),
          'minimum_signup_cashout' => Sanitize::cleanAll($this->params['minimum_signup_cashout']),
          'minimum_signup_cashout_persons_criteria' => Sanitize::cleanAll($this->params['minimum_signup_cashout_persons_criteria']),
          'minimum_signup_cashout_self_criteria' => Sanitize::cleanAll($this->params['minimum_signup_cashout_self_criteria']),
          'notification_emails' => serialize($notification_emails),
          'modification_timestamp' => $this->time
               );     
            

            try{
               
               // begin the transaction
             $this->db->beginTransaction();

              foreach ($configs as $param => $value) {

               $parameter = array(
          'SettingsValue' => $value,
          'SettingsModificationTimestamp' => $this->time
               ); 
                 $this->db->update($parameter,array('SettingsParam'=>$param));
                 }
           
               $this->db->commit();
               return true;

                } catch (PDOException $e) {
                   // rollback update
                   $this->db->rollback();
                  throw $e;
               }

          }else{
          throw new Exception("Error Processing Request");
         }
     
    }



   private function _addReferral($id='',$slug='')
   { 
    if(!empty($id) && !empty($slug)){
      //find the user
      $this->db->table_name = TBL_USERS;
       $u_i = $this->db->find(array('UserSlug'=> $slug));
        if($u_i){  
          
            $this->common_model->_credit_ref($u_i,'signup');
            $this->db->table_name = TBL_USER_REFERRALS;
          //lets update the user rep by adding some rep to his old rep
            $parameter = array(
            'UserID' => $u_i->UserID,
            'ChildUserID' => $id,
            'ReferralCreationTimestamp' => $this->time
              ); 
           $save = $this->db->create($parameter);
           return ($save)? true: false;
        }

      }
      return false;
  }


  private function _generateOtp($userid=false,$type=fasle,$medium=false)
  { 
    $this->db->table_name = TBL_USERS;
    $obj1 = $this->db->find(array('UserID'=>$userid));
    if ($obj1) {
    
      $this->db->table_name = TBL_USER_OTP;
      $obj2 = $this->db->find(array('UserID'=>$obj1->UserID,'OtpType'=>$type));
      //generate the code first 
      $otp = rand(1,substr(time(),3));
      $otp_hash = sha1($otp);
      if ($obj2) {
        
        if(($obj2->OtpModificationTimestamp > (time()-(60)))) {
             throw new Exception("You have to wait for 1 minute before you can request a new OTP. Contact us if you cant wait.", 1);
            }

          $parameter = array(
            'OtpType' => $type,
            'OtpCode' => $otp,
            'OtpHash' => $otp_hash,
            'OtpIp' => Utility::getClientIP(),
              'OtpModificationTimestamp' => $this->time
              );
          $feedback = $this->db->update($parameter,array('OtpID'=>$obj2->OtpID));

        }else{

        $parameter = array(
          'UserID' => $obj1->UserID,
            'OtpType' => $type,
            'OtpCode' => $otp,
            'OtpHash' => $otp_hash,
            'OtpIp' => Utility::getClientIP(),
              'OtpModificationTimestamp' => $this->time,
              'OtpCreationTimestamp' => $this->time
              );
        $feedback = $this->db->create($parameter);

      }
      if ($feedback) {
        
        switch ($medium) {
          case 'phone':   
          SMS::sendOTPMessage($obj1,$otp);
            break;
          
          default:
          Mailer::sendOTPMail($obj1,$otp);
            break;
        }
      }
        
      return $feedback;
     }else{
      throw new Exception("Sorry, we are unable to execute this request at the moment, Pls try again later.", 1);     
     }

  }


  private function _checkOtp($userid=false,$code=false,$type=fasle)
  {
    $this->db->table_name = TBL_USER_OTP;
    $obj1 = $this->db->find(array('UserID'=>$userid,'OtpType'=>$type));

    if ($obj1) {
    
       if ($obj1->OtpCode == $code) {
        // ONE HOUR
            if (($obj1->OtpModificationTimestamp > (time()-(60*60)))) {

          return true;

          }else{

          throw new Exception('Sorry, Otp expired, please try again.');
        return false; 
         }

       }else{

          throw new Exception('Sorry, The Otp you entered is invalid.');
        return false; 
     }
    }else{
       throw new Exception("Error Processing Request", 1);
       
    }

  }

  private function _checkUserFailedLogins($params=false)
  {
    $this->db->table_name = TBL_USER_SESSION;
    $obj1 = $this->db->find(array('UserID'=>$params->UserID));
  if ($obj1){

      if (($obj1->SessionFailedLogins > 3) AND ($obj1->SessionLastFailedLogin > (time()-(60*1)))) {
       
         throw new Exception('Account Locked! - You have typed in a wrong password 3 or more times already. Please wait 1 minute to try again or Contact the security center for help.');
         //lets clear the failed login couter
         $parameter = array(
        'SessionFailedLogins' => 1
            ); 
    $this->db->update($parameter,array('SessionID'=>$obj1->SessionID));

        }
     }else{
      //do nothin
     }
  }


   private function _getUserSession($userid,$for='user')
   {
    if($userid){
      //lets get the user ip and make sure we also get there session token using the ip and userid
      //lets get the user agent of the user
      $userAgent = UserAgent::parse();
      $device = $userAgent['browser'].' on '.$userAgent['platform'];
      $ip = Utility::getClientIP();
      $this->db->table_name = TBL_USER_SESSION;
      $obj1 = $this->db->find(array('UserID'=>$userid,'SessionIp'=>$ip,'SessionDevice'=>$device)); 
     if($obj1){
      //lets check if the users session is still valid
      if (Utility::checkTimeRemaining($obj1->SessionActiveTimestamp,SESSION_EXPIRE_AFTER) == false) {
         return array('token'=>$obj1->SessionToken,'device'=>$obj1->SessionDevice,'ip'=>$ip,'active'=>false);
        }
        //lets update user session time
          if ($for != 'admin') {
                $parameter = array(
                //'SessionToken' => $token, //This can be updated on every verify if you want 
                'SessionIp' => $ip, //This can be updated on every verify if you want 
                'SessionActiveTimestamp' => $this->time
                     ); 
                $this->db->update($parameter,array('SessionID'=>$obj1->SessionID));
          }
        return array('id'=>$obj1->SessionID,'token'=>$obj1->SessionToken,'device'=>$obj1->SessionDevice,'ip'=>$ip,'active'=>true);
       }
      }
      return array('id'=>false,'token'=>false,'device'=>false,'ip'=>false,'active'=>false);
   }



   private function _updateUserSession($params=false,$type=false)
   {
    $token = false;
    
  if($params){

    //lets get the user agent of the user
    $userAgent = UserAgent::parse();
    $device = $userAgent['browser'].' on '.$userAgent['platform'];
    $ip = Utility::getClientIP();
    $u_agent =  isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : false;
    $this->db->table_name = TBL_USER_SESSION;
    $obj1 = $this->db->find(array('UserID'=>$params->UserID,'SessionIp'=>$ip,'SessionDevice'=>$device)); 
     
     if($obj1){
      if (isset($params->UserSettings['session'])) {
       //lets check the users login session settings
       if ($params->UserSettings['session'] == 'single') {
          //generate a new token for every login
          $token = session_id($obj1->SessionToken);
          
           }else {
            //restore old token for multiple device login
          $token = $obj1->SessionToken;
          }
        }else{
          //restore old token for multiple device login
          $token = $obj1->SessionToken;
        }
                          
      switch ($type) {
        case 'verified':
        $parameter = array(
        'SessionToken' => $token,
        'SessionIp' => $ip,
        'SessionDevice' => $device,
        'SessionUserAgent' => $u_agent,
        'SessionFailedLogins' => 1,
        'SessionActiveTimestamp' => $this->time,
        'SessionModificationTimestamp' => $this->time
             ); 
          break;
        case 'failed':
        // increment the failed login counter for that user
        $parameter = array(
        'SessionFailedLogins' => $obj1->SessionFailedLogins + 1,
        'SessionLastFailedLogin' => $this->time
             ); 
          break;
        
        default:
          throw new Exception("Your session could not be restored, please contact customer support!", 1);
         break;
       }
        $this->db->table_name = TBL_USER_SESSION;
        $feedback = $this->db->update($parameter,array('SessionID'=>$obj1->SessionID));

  }else{
    $token = session_id();

    switch ($type) {
        case 'verified':
        $parameter = array(
        'UserID' => $params->UserID,
        'SessionToken' => $token,
        'SessionIp' => $ip,
        'SessionDevice' => $device,
        'SessionUserAgent' => $u_agent,
        'SessionFailedLogins' => 1,
        'SessionActiveTimestamp' => $this->time,
        'SessionModificationTimestamp' => $this->time,
        'SessionCreationTimestamp' => $this->time
             ); 
          break;
        case 'failed':
        // increment the failed login counter for that user
        $parameter = array(
          'UserID' => $params->UserID,
        'SessionFailedLogins' => 1,
        'SessionLastFailedLogin' => $this->time,
        'SessionCreationTimestamp' => $this->time
             ); 
          break;
        
        default:
          throw new Exception("Your session could not be restored, please contact customer support!", 1);
         break;
       }
         $this->db->table_name = TBL_USER_SESSION;
         $feedback = $this->db->create($parameter);

        }

      if ($feedback) {
        return array('id'=>false,'token'=>$token,'device'=>$device,'ip'=>$ip);
      }else{
        throw new Exception("Your session could not be restored, please contact customer support!", 1);
      }
    }
      //code should not get to this place
      return array('id'=>false,'token'=>false,'device'=>false,'ip'=>false);
    }



  private function _verifyUser($params=false)
  {
    //ONE MINUTE
     if(($params->UserVerificationTimestamp > (time()-(60)))) {
           throw new Exception("You have to wait for 1 minute before you can request a new Activation Link/Code. Contact us if you cant wait.", 1);
          }
        //ONE HOUR
      if (($params->UserVerificationTimestamp > (time()-(60*60)))) {

            $parameter = array(
          'UserStatus' => 2,
          'UserEmailVerified' => 2,
                // 'UserVerificationCode' => 'NULL',
                // 'UserVerificationHash' => 'NULL'
              ); 
         $this->db->table_name = TBL_USERS;
         $feedback = $this->db->update($parameter,array('UserID'=>$params->UserID));

        Mailer::sendWelcomeEmail($params);
        return ($feedback)? true : false;

        }else{

        throw new Exception('Sorry, Activation Link/Code expired, please login and resend activation email.');  
       }
  }


    private function _validateSettings($params=false){

        $email_settings = $this->_validateEmailSettings($params);
        $bank_settings = $this->_validateBankSettings($params);
        $currency_settings = $this->_validateCurrencySettings($params);
        $this->_validateReferralSettings($params);
            return array('bank_settings' => $bank_settings, 'currency_settings' => $currency_settings, 'email_settings' => $email_settings );
    }


     private function _validateEmailSettings($params=false)
    {
        $emails_arr = array();

         if (empty($params['notification_emails']) || $params['notification_emails'] == 'undefined' || $params['notification_emails'] == 'false') {
             //throw new Exception("Please configure email settings", 1);
          }else{
           
           $emails = Utility::stdToArray($params['notification_emails']);
         
            if (Utility::getArrCount($emails) > 5) {
                 throw new Exception("You have exceeded the max allowed number of emails to provide.", 1); 
              }


              foreach ($emails as $key => $value) {
                    //store email data in array
                    $emails_arr[] = $value['text'];

                    if (strlen($value['text']) > 64) { 
                       throw new Exception('Email - '.$value['text'].' cannot be longer than 64 characters.'); 
                   
                    } elseif (!filter_var($value['text'], FILTER_VALIDATE_EMAIL)) {
                      throw new Exception('Sorry, your chosen email - '.$value['text'].' does not fit into the email naming pattern.'); 
                     
                    } 
                
                }
          
            }
            //return a promise if everything goes well
           return array('keys' => $emails_arr);
    }



    private function _validateBankSettings($params=false)
    {
      $banks_arr = array();
      $bank_data = array();

         if (!isset($params['bank_keys']) || empty($params['bank_keys']) || $params['bank_keys'] == 'undefined' || $params['bank_keys'] == 'false') {
             throw new Exception("Please configure bank settings", 1);
          }else{
           
           $bank_keys = Utility::stdToArray($params['bank_keys']);
         
            if (Utility::getArrCount($bank_keys) > 20) {
                 throw new Exception("You have exceeded the max allowed number of banks to provide.", 1); 
              }
 
           $bank_data = Utility::stdToArray($params['bank_data']);

           //throw new Exception(Utility::getArrCount($wallet_option_values), 1); 
             
              foreach ($bank_keys as $key => $value) {
                  //store bank data in array
                  $banks_arr[] = $value['text'];

                  //close the editor
                  $bank_data[$value['text']]['edit'] = 0;

                  if (empty($bank_data[$value['text']]['currency'])) {
                       throw new Exception("Please select your ".$value['text']." currency type.", 1);
                   }
                  if (empty($bank_data[$value['text']]['acc_name'])) {
                       throw new Exception("Please provide your ".$value['text']." account name.", 1);
                   }
                  if (empty($bank_data[$value['text']]['acc_num'])) {
                       throw new Exception("Please provide your ".$value['text']." account number.", 1);
                   }
                   if (empty($bank_data[$value['text']]['acc_type'])) {
                       throw new Exception("Please provide your ".$value['text']." account type.", 1);
                   }
                }
          
            }

           //return a promise if everything goes well
           return array('keys' => $banks_arr,'data' => $bank_data);
    }



    private function _validateCurrencySettings($params=false)
    {
        $currencies_arr = array();
        $currency_data = array();

         if (empty($params['currency_keys']) || $params['currency_keys'] == 'undefined' || $params['currency_keys'] == 'false') {
             throw new Exception("Please configure currency settings", 1);
          }else{
           
           $currency_keys = Utility::stdToArray($params['currency_keys']);
         
            if (Utility::getArrCount($currency_keys) > 5) {
                 throw new Exception("You have exceeded the max allowed number of currencies to provide.", 1); 
              }
 
           $currency_data = Utility::stdToArray($params['currency_data']);
         
           //throw new Exception(Utility::getArrCount($wallet_option_values), 1); 
             
              foreach ($currency_keys as $key => $value) {
                 //store bank data in array
                  $currencies_arr[] = $value['text'];

                  //close the editor
                  $currency_data[$value['text']]['edit'] = 0;

                  if (empty($currency_data[$value['text']]['name'])) {
                       throw new Exception("Please provide transaction charge name for ".$value['text'].".", 1);
                   }

                    if (empty($currency_data[$value['text']]['accept_sell']) && !in_array($currency_data[$value['text']]['accept_sell'], array('yes','no'))) {
                       throw new Exception("Please select yes|no to toggle ".$value['text']." status on sell transaction.", 1);
                    }

                   if (empty($currency_data[$value['text']]['sell_algorithm']) && !in_array($currency_data[$value['text']]['sell_algorithm'], array('+','-','%'))) {
                       throw new Exception("Please select an algorithm to calculate charge for sell in ".$value['text'].".", 1);
                    }
                    
                  if (empty($currency_data[$value['text']]['sell']) && $currency_data[$value['text']]['sell'] < '0') {
                       throw new Exception("Please provide transaction charge for sell in ".$value['text'].".", 1);
                   }
                    if (empty($currency_data[$value['text']]['accept_buy']) && !in_array($currency_data[$value['text']]['accept_buy'], array('yes','no'))) {
                       throw new Exception("Please select yes|no to toggle ".$value['text']." status on buy transaction.", 1);
                    }
                  if (empty($currency_data[$value['text']]['buy']) && $currency_data[$value['text']]['buy'] < '0') {
                       throw new Exception("Please provide transaction charge for buy in ".$value['text'].".", 1);
                   }
                  if (empty($currency_data[$value['text']]['buy_algorithm']) && !in_array($currency_data[$value['text']]['buy_algorithm'], array('+','-','%'))) {
                   throw new Exception("Please select an algorithm to calculate charge for buy in ".$value['text'].".", 1);
                   }
                
                }
          
            }
           //return a promise if everything goes well
           return array('keys' => $currencies_arr,'data' => $currency_data);
    }


    private function _validateReferralSettings($params= false)
    {
      if (!isset($params['referral_order_bonus']) OR empty($params['referral_order_bonus'])) { 
              throw new Exception('Order bonus not set.');
           }else{
         if (!Validation::numeric($params['referral_order_bonus'])) {
                     throw new Exception('Order bonus is Invalid.');       
                 } 
            }

          if (!isset($params['referral_signup_bonus']) OR empty($params['referral_signup_bonus'])) { 
              throw new Exception('Signup bonus not set.');
           }else{
         if (!Validation::numeric($params['referral_signup_bonus'])) {
                     throw new Exception('Signup bonus is Invalid.');       
                 } 
            }

          if (!isset($params['minimum_signup_cashout']) OR empty($params['minimum_signup_cashout'])) { 
              throw new Exception('Minimum signup cashout not set.');
           }else{
         if (!Validation::numeric($params['minimum_signup_cashout'])) {
                     throw new Exception('Minimum signup cashout is Invalid.');       
                 } 
            }

          if (!isset($params['minimum_order_cashout']) OR empty($params['minimum_order_cashout'])) { 
              throw new Exception('Minimum order cashout not set.');
           }else{
         if (!Validation::numeric($params['minimum_order_cashout'])) {
                     throw new Exception('Minimum order cashout is Invalid.');       
                 } 
            }

          if (!isset($params['minimum_signup_cashout_persons_criteria']) OR empty($params['minimum_signup_cashout_persons_criteria'])) { 
              throw new Exception('Total People referred before Cashout not set.');
           }else{
         if (!Validation::numeric($params['minimum_signup_cashout_persons_criteria'])) {
                     throw new Exception('Total People referred before Cashout is Invalid.');       
                 } 
            }

           if (!isset($params['minimum_signup_cashout_self_criteria']) OR empty($params['minimum_signup_cashout_self_criteria'])) { 
              throw new Exception('Total Buy/sell order before Cashout not set.');
           }else{
         if (!Validation::numeric($params['minimum_signup_cashout_self_criteria'])) {
                     throw new Exception('Total Buy/sell order before Cashout is Invalid.');       
                 } 
            }
    }



   private function _validateBank($params='')
   {
     if (!isset($params['bank_name']) OR empty($params['bank_name'])) { 

          throw new Exception('Please configure your bank settings.');
           // throw new Exception('Please select your bank from the list.');
           
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


   private function _validateUserPassword($params=false,$type='new'){
    if($type == 'old'){
       if (!isset($params['old_password']) OR empty($params['old_password'])) { 
                     throw new Exception('Old password field is empty.'); 
                     
              } else {
                if (strlen($params['old_password']) < 6) {
                 throw new Exception('Old password has a minimum length of 6 characters.');  
              } 
           } 
           if (!isset($params['password']) OR empty($params['password'])) { 
                     throw new Exception('New password field is empty.'); 
                     
              } else {
                if (strlen($params['password']) < 6) {
                 throw new Exception('New password has a minimum length of 6 characters.');  
              } 
           } 
        }else{
          if (!isset($params['password']) OR empty($params['password'])) { 
                     throw new Exception('Password field is empty.'); 
                     
              } else {
                if (strlen($params['password']) < 6) {
                 throw new Exception('Password has a minimum length of 6 characters.');  
              } 
          } 
        }
   }

  

   private function _validateUser($params=false,$admin=false,$type=''){

     //validate the params data
        if (!isset($params['firstname']) OR empty($params['firstname'])) { 

           throw new Exception('First name field is empty.');
           
           }

          if (!isset($params['lastname']) OR empty($params['lastname'])) { 

           throw new Exception('Last name field is empty.');
           
           }
     
        if (!isset($params['phone']) OR empty($params['phone'])) { 

           throw new Exception('Phone number field is not set.');
           
          }


           if (!isset($params['country']) OR empty($params['country'])) { 

           throw new Exception('Country field is not set.');
           
          }


            if (!isset($params['state']) OR empty($params['state'])) { 

           throw new Exception('State field is not set.');
           
          }

      
        if (!isset($params['username']) OR empty($params['username'])) { 

               throw new Exception('Username field is empty');
                       
              } else {

                  if (strlen($params['username']) < 2 OR strlen($params['username']) > 64) {

                     throw new Exception('Username should not be below 2 characters and should not be above 64 characters.');   
                   
                    } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $params['username'])) {
                   throw new Exception('Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters.'); 
                 } 

             }

        if (!empty($params['email'])) {
              
                  if (strlen($params['email']) > 64) { 
                     throw new Exception('Email cannot be longer than 64 characters.'); 
                 
                  } elseif (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Sorry, your chosen email does not fit into the email naming pattern.'); 
                   
                  } 
            } 


           if($type == 'update'){
    
               if (isset($params['preference']) && !empty($params['preference']) && $params['preference'] != 'false') { 

                if (!empty($params['preference']['auth']) && isset($params['preference']['auth'])){
                    if (!in_array($params['preference']['auth'], array('on','off'))) {
                    throw new Exception('Invalid 2Factor Authentication settings.');
                    }
                  }

                if (!empty($params['preference']['session']) && isset($params['preference']['session'])){
                 if (!in_array($params['preference']['session'], array('multiple','single'))) {
                    throw new Exception('Invalid login session settings.');
                  }
                }

                 if (!empty($params['preference']['tips']) && isset($params['preference']['tips'])){
                 if (!in_array($params['preference']['tips'], array('on','off'))) {
                    throw new Exception('Invalid tips settings.');
                  }
                }


               }
             }

         if($admin == true){

         if (!isset($params['status']) OR empty($params['status'])) { 

              throw new Exception('Please select account status.');
           
             }elseif (!in_array($params['status'], array(1,2,3))) {
              throw new Exception('Invalid status selected.');
             }


              if (!isset($params['level']) OR empty($params['level'])) { 

              throw new Exception('Please select account level.');
           
             }elseif (!in_array($params['level'], array(1,2,3,4,5,6,7,8,9,10))) {
              throw new Exception('Invalid level selected.');
             }


             // if (!isset($params['verify_phone']) OR empty($params['verify_phone'])) { 

             //  throw new Exception('Please select phone verification status.');
           
             // }elseif (!in_array($params['verify_phone'], array(1,2))) {
             //   throw new Exception('Invalid phone verification status selected.');
             // }


         }

   }


  

 }

?>