<?php
/**
 * Class 
 */
class Users extends Controller
{
   private $params;
    /**
     * Construct this object by extending the basic Controller class
     */
    function __construct($params)
    {
            parent::__construct(); 
            $this->params = $params;
            $this->users_model = $this->loadModel('Users',$this->params);
           
    }

    public function login()
    {
        return $this->users_model->login('user');

    }

     public function login_admin()
    {
        return $this->users_model->login('admin');

    }

     public function sudo_login()
    {
        return $this->users_model->login('user','sudo');

    }

    public function logout()
    {
        return $this->users_model->logout();

    }

     public function verify_login()
    {
        return $this->users_model->verify_login();

    }

     public function request_login_otp()
    {
        $config = array('token' => false,
                        'type' => 'login',
                        'medium' => 'email');
        return $this->users_model->request_otp($config);

    }



    public function create()
    {
     
        return $this->users_model->createUser();

    }

     public function createSubscription()
    {
     
        return $this->users_model->createSubscription();

    }


     public function fetch()
    {
     
        return $this->users_model->fetch();

    }


     public function fetch_user_bank()
    {
     
        return $this->users_model->fetch_user_bank();

    }

     public function fetch_wallet_history()
    {
     
        return $this->users_model->fetch_wallet_history();

    }


     public function fetch_user_account()
    {
     
        return $this->users_model->fetch_user_account();

    }


    public function fetchall()
    {
     
        return $this->users_model->fetchall();

    }


    public function searchall()
    {
     
        return $this->users_model->searchall();

    }


    public function update()
    {
     
        return $this->users_model->update();

    }


     public function update_bank()
    {   

         return $this->users_model->update_bank();
    }


    public function update_slug()
    {
     
        return $this->users_model->update_slug();

    }



     public function withdraw()
    {
     
        return $this->users_model->withdraw();

    }


    public function update_password()
    {
     
        return $this->users_model->update_password();

    }

     public function update_password_self()
    {
     
        return $this->users_model->update_password_self();

    }


     public function request_phone_verify_otp()
    {
        $config = array('token' => true,
                        'type' => 'phone_verification',
                        'medium' => 'phone');
        return $this->users_model->request_otp($config);

    }

    public function request_pass_reset_otp()
    {
        
        $config = array('token' => false,
                        'type' => 'password_reset',
                        'medium' => 'email');
        return $this->users_model->request_otp($config);

    }


    public function verify_account()
    {   

         return $this->users_model->verify_account();
    }

      public function verify_account_alt()
    {
     
        return $this->users_model->verify_account_alt();

    }

     public function resend_activation()
    {
     
        return $this->users_model->resend_activation();

    }


     public function verify_reset_otp()
    {   

         return $this->users_model->verify_reset_otp();
    }


     public function verify_token()
    {   

         return $this->users_model->verify_token();
    }


      public function upload_avatar()
    {   

         return $this->users_model->upload_avatar();
    }


    //  public function upload_document()
    // {   

    //      return $this->users_model->upload_document();
    // }


    //  public function process_download_documents()
    // {   

    //      return $this->users_model->process_download_documents();
    // }


    //  public function start_download_documents()
    // {   

    //      return $this->users_model->start_download_documents();
    // }


    public function update_account()
    {   

         return $this->users_model->update_account();
    }


     public function bulk_action()
    {   

         return $this->users_model->bulk_action();
    }




    public function fetch_statistics()
    {   

         return $this->users_model->fetch_statistics();
    }



    public function fetch_settings()
    {   

         return $this->users_model->fetch_settings();
    }



    public function update_settings()
    {   

         return $this->users_model->update_settings();
    }


    public function credit_debit_wallet()
    {
     
        return $this->users_model->credit_debit_wallet();

    }


    public function fetchall_wallet_history()
    {
     
        return $this->users_model->fetchall_wallet_history();

    }


     public function fetchall_user_wallet_history()
    {
     
        return $this->users_model->fetchall_user_wallet_history();

    }



     public function fetchall_user_referral_history()
    {
     
        return $this->users_model->fetchall_user_referral_history();

    }



    public function searchall_wallet_history()
    {
     
        return $this->users_model->searchall_wallet_history();

    }


    public function confirm_wallet_history()
    {
     
        return $this->users_model->confirm_wallet_history();

    }


    public function cancel_wallet_history()
    {
     
        return $this->users_model->cancel_wallet_history();

    }

    public function bulk_wallet_history_action()
    {
     
        return $this->users_model->bulk_wallet_history_action();

    }



}
