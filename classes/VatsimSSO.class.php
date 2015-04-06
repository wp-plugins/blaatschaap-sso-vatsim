<?php



class VatsimSSO implements AuthService {
//------------------------------------------------------------------------------
  public function canLogin(){
    
    return (  isset($_SESSION['bs_vatsimsso_id']) || 
              isset($_GET['oauth_verifier']) || 
              isset($_GET['return']) ); 

    
  }
//------------------------------------------------------------------------------
  public function Login($service_id){
//    die("vatsim login");
    try {
      self::process('self::process_login', $service_id);
    } catch (Exception $e) { 
      unset ($_SESSION['bs_vatsimsso_id']);   
      unset ($_SESSION['bsauth_plugin']);
      unset ($_SESSION['bsauth_login_id']);
      unset ($_SESSION['bsauth_link_id']);
      echo "error: " . $e->getMessage();
    }
  }
  
//------------------------------------------------------------------------------
  public function Link($service_id){
/*
    echo "<br>";
    print_r($_SESSION);
    die("</br>");
*/
    if (isset($_SESSION['blaat_vatsimsso_user']) &&
        $_SESSION['blaat_vatsimsso_sid']  == $service_id) {
      // process_link($vs_user,$service_name,$service_id)
      self::process_link( $_SESSION['blaat_vatsimsso_user'],
                          $_SESSION['bsauth_display'],
                          $service_id);
      unset($_SESSION['blaat_vatsimsso_user']);
      unset($_SESSION['blaat_vatsimsso_sid']);
    } else 


    try {
      self::process('self::process_link', $service_id);
    } catch (Exception $e) { 
      unset ($_SESSION['bs_vatsimsso_id']);   
      unset ($_SESSION['bsauth_plugin']);
      unset ($_SESSION['bsauth_login_id']);
      unset ($_SESSION['bsauth_link_id']);
      echo "error: " . $e->getMessage();
    }
  }
  
//------------------------------------------------------------------------------

  public function getRegisterData(){

    if (!isset($_SESSION['blaat_vatsimsso_user'])) return NULL;

    $vs_user = $_SESSION['blaat_vatsimsso_user'];
    $new_user = array();
    
    $new_user['user_login'] = $vs_user->id;
    $new_user['first_name'] = $vs_user->name_first;
    $new_user['last_name']  = $vs_user->name_last;
    if (get_option("bs_auth_signup_user_email")!="Disabled") $new_user['user_email'] = $vs_user->email;

    return $new_user;

  }
//------------------------------------------------------------------------------
//  When a WordPress user is deleted, delete any linked services to this user
  public function Delete($user_id){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_vatsimsso_sessions";
    $wpdb->delete( $table_name, array( 'wordpress_id' => $user_id ) );    
  }
//------------------------------------------------------------------------------
  public function getButtons(){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_vatsimsso_services";
    $results = $wpdb->get_results("select * from $table_name where enabled=1 ",
                   ARRAY_A);
    $buttons = array();    
    foreach ($results as $result) {
      $button = array();
      if(!$result['customlogo_enabled']) 
        $service=strtolower($result['client_name']); 
      else {
        $service="custom-".$result['id'];
        $button['css']="<style>.bs-auth-btn-logo-".$service.
           " {background-image:url('" .$result['customlogo_url']."');}</style>"; 
      }

      //deprecated html generation in class
      $button['button']="<button class='bs-auth-btn' name=bsauth_login 
             type=submit value='blaat_vatsimsso-".$result['id']."'><span class='bs-auth-btn-logo 
             bs-auth-btn-logo-$service'></span><span class='bs-auth-btn-text'>".
             $result['display_name']."</span></button>";

      $button['order']=$result['display_order'];
      $button['plugin']="blaat_vatsimsso";

      $button['id']      = $result['id'];
      $button['service'] = $service;
      $button['display_name'] = $result['display_name'];


      $buttons[]=$button;
    }
    return $buttons;
  }

  public function getButtonsLinked($id){
      global $wpdb;
      $buttons = array(); 
      $buttons['linked']= array();
      $buttons['unlinked'] = array();

      $user = wp_get_current_user();


      // TODO rewrite as OAuth Class Methods
      $table_name = $wpdb->prefix . "bs_vatsimsso_sessions";
      $user_id    = $user->ID;
      $query = $wpdb->prepare("SELECT service_id FROM $table_name WHERE `wordpress_id` = %d",$user_id);



      $linked_services = $wpdb->get_results($query,ARRAY_A);
       
      $table_name = $wpdb->prefix . "bs_vatsimsso_services";
      $query = "SELECT * FROM $table_name where enabled=1";
      $available_services = $wpdb->get_results($query,ARRAY_A);

      $linked = Array();
      foreach ($linked_services as $linked_service) {
        $linked[]=$linked_service['service_id'];
      }  


      foreach ($available_services as $available_service) {
        $button = array();
        $button['class'] = $class;

        if(!$available_service['customlogo_enabled'])
          $service=strtolower($available_service['client_name']);
        else {
          $service="custom-".$available_service['id'];
          $button['logo']= $available_service['customlogo_url'];
          $button['css'] = "<style>.bs-auth-btn-logo-".$service." {background-image:url('" .$available_service['customlogo_url']."');}</style>";
        }


      $button['order']   = $available_service['display_order'];
      $button['plugin']  = "blaat_vatsimsso";
      $button['id']      = $available_service['id'];
      $button['service'] = $service;

      $button['display_name'] = $available_service['display_name'];


      if (in_array($available_service['id'],$linked)) { 
        $buttons['linked'][]=$button;
      } else {
        $buttons['unlinked'][]=$button;
      }


    }
    return $buttons;
  }
//------------------------------------------------------------------------------
  public function process($function, $service_id){
    global $wpdb; // Database functions



    $table_name = $wpdb->prefix . "bs_vatsimsso_services";
    $query = $wpdb->prepare(" SELECT `client_baseurl` as `base`, 
                                     `client_key`     as `key`, 
                                     `client_secret`  as `secret`, 
                                     `client_method`  as `method`, 
                                     `client_cert`    as `cert`,
                                     `display_name`,
                                     `auto_register` , `fetch_data`
                              FROM $table_name  WHERE id = %d", $service_id);
    //$results = $wpdb->get_results($query,ARRAY_A);
    //$sso = $results[0];
    $sso = $wpdb->get_row($query,ARRAY_A);

    $SSO = new SSO($sso['base'], $sso['key'], $sso['secret'], $sso['method'], $sso['cert']);

    // it seems the sso_return variable should contain the redirect_url
    $sso_return = site_url("/".get_option("login_page"));

    if (isset($_GET['oauth_verifier']) && !isset($_GET['oauth_cancel'])){

      if (isset($_SESSION[SSO_SESSION]) && isset($_SESSION[SSO_SESSION]['key']) && isset($_SESSION[SSO_SESSION]['secret'])){



        if (@$_GET['oauth_token']!=$_SESSION[SSO_SESSION]['key']){
          throw new Exception("token_mismatch");
          //echo '<p>Returned token does not match</p>';
        }
        
        if (@!isset($_GET['oauth_verifier'])){
          throw new Exception("verification_code_missing");
          //echo '<p>No verification code provided</p>';
        }
        
        // obtain the details of this user from VATSIM
        $user = $SSO->checkLogin($_SESSION[SSO_SESSION]['key'], $_SESSION[SSO_SESSION]['secret'], @$_GET['oauth_verifier']);
        
        if ($user){
          // One-time use of tokens, token no longer valid
          unset($_SESSION[SSO_SESSION]);


          switch ($user->request->result) {
            case "success":
              call_user_func($function, $user->user, $sso['display_name'], $service_id, $sso['auto_register'], $sso['fetch_data']);
              return;
            default:
              throw new Exception("login_failed");
          }
          
        } else {

          /*
          // OAuth or cURL errors have occurred, output here
          echo '<p>An error occurred</p>';
          $error = $SSO->error();

          if ($error['code']){
            echo '<p>Error code: '.$error['code'].'</p>';
          }

          echo '<p>Error message: '.$error['message'].'</p>';
          
          // do not proceed to send the user back to VATSIM
          */
          // TODO : How can be do better error handling at this point?
          throw new Exception("unspecified_error");
          return;
        }
      } 
    } else if (isset($_GET['oauth_cancel'])){
      throw new Exception("aborted");
    }





    // create a request token for this login. Provides return URL and suspended/inactive settings
    $token = $SSO->requestToken($sso_return, false, false);

    if ($token){
        
        // store the token information in the session so that we can retrieve it when the user returns
        $_SESSION[SSO_SESSION] = array(
            'key' => (string)$token->token->oauth_token, // identifying string for this token
            'secret' => (string)$token->token->oauth_token_secret // secret (password) for this token. Keep server-side, do not make visible to the user
        );
        
        // redirect the member to VATSIM
        $SSO->sendToVatsim();
        
    } else {
     
   
        echo '<p>An error occurred</p>';
        $error = $SSO->error();
        
        if ($error['code']){
            echo '<p>Error code: '.$error['code'].'</p>';
        }
        
        echo '<p>Error message: '.$error['message'].'</p>';


        
    }
  }
//------------------------------------------------------------------------------
  public function  install() {
    global $wpdb;
    global $bs_vatsimsso_plugin;
    $dbver = 4;
    $live_dbver = get_option( "bs_vatsimsso_dbversion" );


    //!!if ($dbver != $live_dbver) {
    if (true) {
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  
      $table_name = $wpdb->prefix . "bs_vatsimsso_sessions";
      $query = "CREATE TABLE $table_name (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `wordpress_id` INT NOT NULL DEFAULT 0,
                `service_id` INT NOT NULL ,
                `external_id` INT NOT NULL ,
                PRIMARY KEY  (id)
                );";
      dbDelta($query);


   
      $table_name = $wpdb->prefix . "bs_vatsimsso_services";
      $query = "CREATE TABLE $table_name (
                `id` INT NOT NULL AUTO_INCREMENT,
                `enabled` BOOLEAN NOT NULL DEFAULT FALSE ,
                `display_name` TEXT NOT NULL ,
                `display_order` INT NOT NULL DEFAULT 1,
                `client_baseurl` TEXT NOT NULL ,
                `client_key` TEXT NOT NULL ,
                `client_secret` TEXT NOT NULL,
                `client_method` TEXT NOT NULL,
                `client_cert` TEXT NOT NULL DEFAULT '',
                `customlogo_url` TEXT NULL DEFAULT NULL,
                `customlogo_filename` TEXT NULL DEFAULT NULL,
                `customlogo_enabled` BOOLEAN DEFAULT FALSE,
                `auto_register` BOOL NOT NULL DEFAULT FALSE,
                `fetch_data` BOOL NOT NULL DEFAULT FALSE,
                PRIMARY KEY  (id)
                );";
      dbDelta($query);

      update_option( "bs_vatsimsso_dbversion" , $dbver);
    }
  }
//------------------------------------------------------------------------------
  public function  process_link($vs_user,$service_name,$service_id, $auto=false, $fetch=false) {
    global $wpdb;    

//!!OK    echo ("process_link($vs_user->id,$service_name,$service_id)");
    $wp_user = wp_get_current_user();
    $wp_uid  = $wp_user->ID;
    $vs_uid =  $vs_user->id;

    // UNSETTING LINKAGE VARIABLES
    unset ($_SESSION['bs_vatsimsso_id']);   
    unset ($_SESSION['bsauth_plugin']);
    unset ($_SESSION['bsauth_link_id']);


    $table_name = $wpdb->prefix . "bs_vatsimsso_sessions";

    // We need to verify the external account is not already linked
    $testQuery = $wpdb->prepare("SELECT * FROM $table_name 
                                 WHERE service_id = %d 
                                 AND   external_id = %d" , $service_id, $vs_user->id);
    $testResult = $wpdb->get_results($testQuery,ARRAY_A);
    if (count($testResult)) {
        printf( __("Your %s account has is already linked to another local account", "blaat_auth"), $service );
      } else {
        // proceed with linking
        $query = $wpdb->prepare("INSERT INTO $table_name (`wordpress_id`, `service_id`, `external_id` )
                                         VALUES      ( %d      ,  %d         ,  %d          )",
                                                      $wp_uid , $service_id , $vs_uid );
        $wpdb->query($query);
        printf( __("Your %s account has been linked", "blaat_auth"), $service_name );
        
      }
  }
//------------------------------------------------------------------------------

  public function Unlink ($id) {
    global $wpdb;    
    $table_name = $wpdb->prefix . "bs_vatsimsso_sessions";
    $table_name2 = $wpdb->prefix . "bs_vatsimsso_services";
    $query2 = $wpdb->prepare("Select display_name from $table_name2 where id = %d", $id );
    $service_name = $wpdb->get_results($query2,ARRAY_A);
    $service = $service_name[0]['display_name'];
    $query = $wpdb->prepare ("Delete from $table_name where wordpress_id = %d AND service_id = %d", get_current_user_id(), $id );
    $wpdb->query($query);
    printf( __("You are now unlinked from %s.", "blaat_auth"), $service );
    unset($_SESSION['bsauth_unlink']);

  }
//------------------------------------------------------------------------------
  private function process_login($vs_user,$display_name,$service_id, $auto_register=false, $fetch_data=false){

    global $wpdb;
    $_SESSION['bsauth_display'] = $display_name;

    // unsetting the session variables should happen inside the processing
    // functions
    unset ($_SESSION['bs_vatsimsso_id']);   
    unset ($_SESSION['bsauth_plugin']);
    unset ($_SESSION['bsauth_login_id']);

    if ( is_user_logged_in() ) { 
      header("Location: ".site_url("/".get_option("link_page")). '?' . $_SERVER['QUERY_STRING']);
    } else {

      $table_name = $wpdb->prefix . "bs_vatsimsso_sessions";
      $query = $wpdb->prepare("SELECT `wordpress_id` FROM $table_name WHERE `service_id` = %d AND `external_id` = %d",$service_id,$vs_user->id);  
      $results = $wpdb->get_results($query,ARRAY_A);
      $result = $results[0];

      if ($result) {
        unset ($_SESSION['bsauth_login']);  
        wp_set_current_user ($result['wordpress_id']);
        wp_set_auth_cookie($result['wordpress_id']);
        header("Location: ".site_url("/".get_option("login_page")));     
      } else {
        $_SESSION['bsauth_fetch_data'] = $fetch_data;
        $_SESSION['bsauth_register_auto'] = $auto_register;
        $_SESSION['bsauth_register'] = "blaat_vatsimsso-$service_id";
        $_SESSION['blaat_vatsimsso_user'] = $vs_user;
        $_SESSION['blaat_vatsimsso_sid']  = $service_id;
        header("Location: ".site_url("/".get_option("register_page")));

      }
    }
  }
//------------------------------------------------------------------------------
}

?>
