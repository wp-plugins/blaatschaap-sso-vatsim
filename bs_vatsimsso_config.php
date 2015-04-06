<?php

//------------------------------------------------------------------------------
function bs_vatsimsso_add_page(){
  global $_SERVER;
  $ACTION="admin.php?page=bs_vatsimsso_services";
  ?>
  <div class="wrap">
  <?php screen_icon(); ?>
  <h2><?php _e("BlaatSchaap OAuth Configuration","blaat_auth");?></h2>
  <p><?php  _e("Documentation:","blaat_auth");?>
    <a href="http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/" target="_blank">
      http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/
    </a>
  </p>
  <script>
    function updPreview(){
    document.getElementById("logoPreview").innerHTML="<span class='bs-auth-btn-preview bs-auth-btn-logo-" +
    document.getElementById("service").value.toLowerCase() +"'></span>";
    document.getElementById('display_name_2').value=document.getElementById("service").value;
    
    }
  </script>
  <form method='post'  enctype="multipart/form-data" action='<?php echo $ACTION; ?>'>
    <table class='form-table bs-auth-settings-table'>

      <tr>
        <th><label><?php _e("Display logo","blaat_auth");?></label></th>
        <td>
          <span class='blaat_addpage_logooption'>
            <?php _e("Default logo","blaat_auth");?>
            <span id="logoPreview">
            </span>
            <script>
              updPreview();
            </script>
          </span>
          <span class='blaat_addpage_logooption'>
            <?php _e("Upload custom logo","blaat_auth");?>
            <input type="file" name="newlogo">
          </span>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Display name","blaat_auth");?></label></th>
        <td>
          <input type='text' id='display_name_2' name='display_name'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Display order","blaat_auth");?></label></th>
        <td>
          <input type='text' name='display_order'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Base URL","blaat_auth");?></td>
        <td>
          <input type='text' name='client_baseurl'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client Key","blaat_auth");?></td>
        <td>
          <input type='text' name='client_key'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client Secret","blaat_auth");?></td>
        <td>
          <input type='text' name='client_secret'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client Method","blaat_auth");?></td>
        <td>
          <select name='client_method'>
            <option value='HMAC'>HMAC</option>
            <option value='RSA'>RSA</option>
          </select>
        </td>
      </tr>

      <tr><th><?php _e("Client Certificate (RSA only)","blaat_auth") ?></th><td>
        <textarea cols=70 rows=30 id='client_cert' name='client_cert' style='font-family: monospace'></textarea>
      </td></tr>'

      <tr>
        <th><label><?php _e("Enabled","blaat_auth");?></td>
        <td><input type='checkbox' name='client_enabled' value=1></input>
      </tr>

      <tr>
        <th><label><?php _e("Fetch Data","blaat_auth");?></td>
        <td><input type='checkbox' name='fetch_data' value=1></input>
      </tr>
      <tr>

      <tr>
        <th><label><?php _e("Auto Register","blaat_auth");?></td>
        <td><input type='checkbox' name='auto_register' value=1></input>
      </tr>
      <tr>
        <td></td>
        <td><input type='submit' name='add_service' value='<?php  _e("Add");?>'></input>
      </tr>
    </table>
  </form>
  <script>updPreview();</script>
  <?php
}
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
function bs_vatsimsso_add_process(){
// TODO

  global $wpdb;
  global $bs_vatsimsso_plugin;

  $service=$_POST['service'];
  $display_name=$_POST['display_name'];
  $client_baseurl=$_POST['client_baseurl'];
  $client_key=$_POST['client_key'];
  $client_secret=$_POST['client_secret'];
  $client_method=$_POST['client_method'];
  $client_cert=$_POST['client_cert'];
  $enabled = (int) $_POST['client_enabled'];
  $auto_register = (int) $_POST['auto_register'];
  $fetch_data = (int) $_POST['fetch_data'];
  $table_name = $wpdb->prefix . "bs_vatsimsso_services";
   
 
  $query = $wpdb->prepare( 
	"INSERT INTO $table_name
	(         `enabled` , `display_name` , `client_baseurl`  , `client_key`  , `client_secret` , `client_method` , `client_cert` , `auto_register` , `fetch_data`)
	VALUES (  %d        ,  %s            ,  %s            , %s            , %s              , %s              , %s , %d , %d)", 
            $enabled  , $display_name  ,  $client_baseurl  , $client_key   , $client_secret  , $client_method  , $client_cert, $auto_register , $fetch_data);

  $result = $wpdb->query($query);



  if ($_FILES['newlogo']['size']){
    $uploadedfile = $_FILES['newlogo'];
    global $bs_set_filename;
    $bs_set_filename="cstlogo_". $wpdb->insert_id .".png";
    $upload_overrides = array( 'test_form' => false, 'unique_filename_callback' => 'bs_upload_filename' );
    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

    if (isset($movefile['file'])){
      $imginfo = getimagesize($movefile['file']);
      if ($imginfo) {
        $image  = file_get_contents($movefile['file']);
        $source = imagecreatefromstring($image);
        $target = imagecreatetruecolor(32,32);
        imagesavealpha($target, true);
        $trans_colour = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefill($target, 0, 0, $trans_colour);
        imagecopyresized($target,$source,1,1,0,0,30,30,$imginfo[0],$imginfo[1]); 
        imagepng($target,$movefile['file']);
        imagedestroy($target);
        imagedestroy($source);


        $new_data=array();
        $new_data["customlogo_url"] = $movefile['url'];
        $new_data["customlogo_filename"] = $movefile['file'];
        $new_data["customlogo_enabled"] = 1;

        $data_id = array();
        $service_id=$wpdb->insert_id;
        $data_id['id']  = $wpdb->insert_id;

        $wpdb->update($table_name, $new_data, $data_id);


        // TODO :: ERROR MESSAGES HANDLING
        // TODO :: IS IT POSSIBLE TO STORE IT ELSEWHERE? (E.G. WITHOUT DATE IN PATH)
        // TODO :: IF NOT, REMOVE OLD FILE

        //echo "file saved as " .$movefile['file'];
        } else {_e("Image error","blaat_auth");}
      } else {_e("Upload error","blaat_auth");};
    } else { // no upload}
  }
  global $SROLLPOS;
  $SROLLPOS="<script>location.hash = '#serv-". $service_id ."';</script>";
}

//------------------------------------------------------------------------------
function bs_vatsimsso_delete_service(){
  global $wpdb;
  global $bs_vatsimsso_plugin;

  // Delete the service entry
  $table_name = $wpdb->prefix . "bs_vatsimsso_services";
  $query = $wpdb->prepare("DELETE FROM $table_name  WHERE id = %d", $_POST['id']);
  $wpdb->query($query);
}
//------------------------------------------------------------------------------
if (!function_exists("blaat_not_implemented")) {
  function blaat_not_implemented() {
      ?>
      <div class="error">
          <p><?php _e( 'Not Implemented!',"blaat_auth"); ?></p>
      </div>
      <?php
  }
}
//------------------------------------------------------------------------------
function bs_vatsimsso_update_service(){
  global $wpdb;
  $table_name = $wpdb->prefix . "bs_vatsimsso_services";

  $new_data = array();
  $new_data["display_name"] = $_POST["display_name"];
  $new_data["display_order"] = $_POST["display_order"];
  $new_data['client_baseurl'] = $_POST['client_baseurl'];
  $new_data["client_key"] = $_POST["client_key"];
  $new_data["client_secret"] = $_POST["client_secret"];
  $new_data["client_method"] = $_POST["client_method"];
  $new_data["client_cert"] = $_POST["client_cert"];
  $new_data["enabled"] = $_POST["client_enabled"];
  $new_data["auto_register"] = $_POST["auto_register"];
  $new_data["fetch_data"] = $_POST["fetch_data"];
  $new_data["customlogo_enabled"] = $_POST["customlogo_enabled"];


  if ($_FILES['newlogo']['size']){
    if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
    $uploadedfile = $_FILES['newlogo'];
    global $bs_set_filename;
    $bs_set_filename="cstlogo_".$_POST['id'].".png";

    /* delete if file already exists */
    /* PHP 5.4+ supports $path= wp_upload_dir($time)['path']; */
    $php53 = wp_upload_dir($time); $php53_path=$php53['path'];
    $bs_target_path=$php53_path."/$bs_set_filename";


    if (file_exists($bs_target_path)) unlink($bs_target_path);  

    $upload_overrides = array( 'test_form' => false, 'unique_filename_callback' => 'bs_upload_filename' );
    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
    if (isset($movefile['file'])){
      $imginfo = getimagesize($movefile['file']);
      if ($imginfo) {
        $image  = file_get_contents($movefile['file']);
        $source = imagecreatefromstring($image);
        $target = imagecreatetruecolor(32,32);
        imagesavealpha($target, true);
        $trans_colour = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefill($target, 0, 0, $trans_colour);
        imagecopyresized($target,$source,1,1,0,0,30,30,$imginfo[0],$imginfo[1]); 
        imagepng($target,$movefile['file']);
        imagedestroy($target);
        imagedestroy($source);

        $new_data["customlogo_url"] = $movefile['url'];
        $new_data["customlogo_filename"] = $movefile['file'];
        $new_data["customlogo_enabled"] = 1;

        } else {_e("Image error","blaat_auth");}
      } else {_e("Upload error","blaat_auth");};
    } else { // no upload}
  }
  
  $data_id = array();
  $data_id['id']  = $_POST['id'];

  $wpdb->update($table_name, $new_data, $data_id);

  global $SROLLPOS;
  $SROLLPOS="<script>location.hash = '#serv-". htmlspecialchars($_POST['id']) ."';</script>";


}
//------------------------------------------------------------------------------
if (!function_exists("bs_upload_filename")) {
  function bs_upload_filename(){
    global $bs_set_filename;
    return $bs_set_filename;
  }
}
//------------------------------------------------------------------------------
function bs_vatsimsso_list_services(){
  global $wpdb;
  global $bs_vatsimsso_plugin;
  global $_SERVER;
  $ACTION=htmlspecialchars($_SERVER['REQUEST_URI']);// . '?' . $_SERVER['QUERY_STRING'];
  $table_name  = $wpdb->prefix . "bs_vatsimsso_services";

  $results = $wpdb->get_results("select * from $table_name",ARRAY_A);


  foreach ($results as $result){
    $enabled= $result['enabled'] ? "checked" : "";
    $auto_register= $result['auto_register'] ? "checked" : "";
    $fetch_data= $result['fetch_data'] ? "checked" : "";
    ?>
  <a name="serv-<?php echo $result['id']; ?>"></a>
  <form method='post'  enctype="multipart/form-data" action='<?php echo $ACTION ?>'>
    <input type='hidden' name='id' value='<?php echo $result['id']; ?>'>
    <table class='form-table bs-auth-settings-table'>
      <tr>
        <th><label><?php _e("Display name","blaat_auth"); ?></label></th>
        <td>
          <input type='text' name='display_name' value='<?php echo $result['display_name']; ?>'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Display order","blaat_auth");?></label></th>
        <td>
          <input type='text' name='display_order' value='<?php echo $result['display_order']; ?>'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Base URL","blaat_auth"); ?></label></th>
        <td>
          <input type='text' name='client_baseurl' value='<?php echo $result['client_baseurl']; ?>'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client Key","blaat_auth"); ?></label></th>
        <td>
          <input type='text' name='client_key' value='<?php echo $result['client_key']; ?>'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client Secret","blaat_auth"); ?></label></th>
        <td>
          <input type='text' name='client_secret' value='<?php echo $result['client_secret']; ?>'></input>
        </td>
      </tr>

      <tr>
        <th><label><?php _e("Client Method","blaat_auth"); ?></label></th>
        <td>
          <select name='client_method'>
            <option value='HMAC' 
              <?php if ($result["client_method"]=="HMAC" ) echo "selected" ;?>              
            >HMAC</option>
            <option value='RSA'
              <?php if ($result["client_method"]=="RSA") echo "selected" ;?> 
            >RSA</option>
          </select>
        </td>
      </tr>
      <tr>
      </tr>
        <th><label><?php _e("Client Certificate (RSA only)","blaat_auth"); ?></label></th>
        <td>
          <textarea cols=70 rows=30 id='client_cert' name='client_cert' style='font-family: monospace'><?php echo $result['client_cert']; ?></textarea>
        </td>
      </tr>
      <tr>
      <tr>

      <tr>
        <th><label><?php _e("Logo:","blaat_auth"); ?></label></th>
        <td>
          <style>.bs-auth-btn-logo-cst<?php echo $result['id'] ?> { background-image:url('<?php echo $result['customlogo_url'];?>'); }</style>
          <?php
          if (!$result['custom_id']) {
            ?>
            <span class='bs-auth-btn-preview bs-auth-btn-logo-<?php echo strtolower($result['client_name']); ?>'></span>
            <input type='radio' name='customlogo_enabled' value='0' <?php if(!$result['customlogo_enabled']) echo "checked"; ?> > 
            <span class='bs-auth-btn-preview bs-auth-btn-logo-cst<?php echo $result['id'] ?>'></span>
            <input type='radio' name='customlogo_enabled' value='1'  <?php if ($result['customlogo_enabled']) echo "checked";?>  >
            <?php } else {?>
              <span class='bs-auth-btn-preview bs-auth-btn-logo-cst<?php echo $result['id'] ?>'></span>
            <?php } ?>
            <input type="file" name="newlogo">
        </td>
      </tr>

      <tr>
        <th><label><?php _e("Enabled","blaat_auth"); ?></label></th>
        <td><input type='checkbox' name='client_enabled' value=1 <?php echo $enabled; ?>></input>
      </tr>
      <tr>

      <tr>
        <th><label><?php _e("Fetch Data","blaat_auth"); ?></label></th>
        <td><input type='checkbox' name='fetch_data' value=1 <?php echo $fetch_data; ?>></input>
      </tr>
      <tr>
      <tr>
        <th><label><?php _e("Auto Register","blaat_auth"); ?></label></th>
        <td><input type='checkbox' name='auto_register' value=1 <?php echo $auto_register; ?>></input>
      </tr>
      <tr>

        <td></td><td><input type='submit' name='delete_service' value='<?php _e("Delete");?>'>
        <input type='submit' name='update_service' value='<?php _e("Update");?>'></input>
      </tr>
    </table>
  </form>
  <hr>
  <?php
  global $SROLLPOS;
  echo $SROLLPOS;
  }
}
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
function bs_vatsimsso_menu() {

  if (!blaat_page_registered('blaat_plugins')){
    add_menu_page('BlaatSchaap', 'BlaatSchaap', 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
    //add_submenu_page('blaat_plugins', "" , "" , 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
  }


// TODO subpage regisered
  add_submenu_page('blaat_plugins',   __('General Auth Configuration',"blaat_auth") , 
                                      __("Auth Config","blaat_auth") , 
                                      'manage_options', 
                                      'bsauth_pages_plugins', 
                                       'blaat_plugins_auth_page');


  add_submenu_page('blaat_plugins' ,  __('Vatsim SSO Configuration',"blaat_auth"), 
                                      __('Vatsim Config',"blaat_auth"), 
                                      'manage_options', 
                                      'bs_vatsimsso_services', 
                                      'bs_vatsimsso_config_page' );
  add_submenu_page('blaat_plugins' ,  __('Vatsim SSO Add Service',"blaat_auth"),   
                                      __('Vatsim Add',"blaat_auth"), 
                                      'manage_options', 
                                      'bs_vatsimsso_add', 
                                      'bs_vatsimsso_add_page' );
  add_action( 'admin_init', 'bsauth_register_options' );
}
//------------------------------------------------------------------------------
function bs_vatsimsso_config_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
        screen_icon();
        echo "<h2>";
        _e("BlaatSchaap VATSIM SSO Configuration","blaat_auth");
        echo "</h2>";
        ?><p><?php  _e("Documentation:","blaat_auth");?>
          <a href="http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/" target="_blank">
            http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/
          </a>
        </p><?php

        if ($_POST['add_service']) bs_vatsimsso_add_process();
        if ($_POST['delete_service']) bs_vatsimsso_delete_service();
        if ($_POST['update_service']) bs_vatsimsso_update_service();
        echo "<h2>"; _e("Configured Services","blaat_auth"); echo "</h2><hr>";
        bs_vatsimsso_list_services();
        echo '<hr>';

}
//------------------------------------------------------------------------------


?>
