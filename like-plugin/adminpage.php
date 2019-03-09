<?php

$ParamNameOfTheAdminMenu = "like_counter_manage";
add_action('admin_menu', 'benim_ekletim_menu'); // Admin menüsüne eklentimizi ekler
function benim_ekletim_menu(){
  global $ParamNameOfTheAdminMenu;
 add_menu_page('Like Counter','Like Counter', 'manage_options', $ParamNameOfTheAdminMenu, 'benim_eklentim_yonetim'); //Admin menüsüne eklenecek eklenti bilgileri
}


function benim_eklentim_yonetim(){  //Yönetim Paneli Ayarları

    global $ParamNameOfTheAdminMenu;
  
    if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){  //Tarayıcıdan direkt bu dosyayın çağırılmasını engelle
      die('You are not allowed to call this page directly.');
    }

  echo "<h1>Liker Counter Management Page</h1>";
   
  //-------------------------------------------------------------------------------------------
      global $wpdb;
      $table_name = $wpdb->prefix . "like_counter";
      $result = $wpdb->get_results("SELECT post_id 
                                    FROM $table_name"); //Tüm Postların ID numaralarını al
  
      $postlikes = array();
      $alltags = array();
      $postcount = 0; //Post sayısı (pagination limit icin)
      foreach ($result as $details) {
        $postcount++;
        $postlikes[$details->post_id] += 1;                 //Şunu yap:  array[postid] = like_count;
        $tags = wp_get_post_tags($details->post_id);        //Get all tags of this post
        foreach ($tags as $details) {
          $alltags[] = $details->term_id;                   //Her tag id sini array e ekle
        }
      }
  
  
      $tagperpage = get_option('numoftagpp');
      if (!$tagperpage || !is_numeric($tagperpage)) {$tagperpage = 10;} //Yoksa yada numerik değilse 10 yap
  
      $pagination = (@$_GET['pagination']);
      if (!$pagination || !is_numeric($pagination)) {$pagination = 0;}
      
      $values = array_count_values($alltags);
      arsort($values);
      $popular = array_slice(array_keys($values), (0 + ($pagination * $tagperpage)), ($tagperpage), true);  //Kackezkectigi : tag_id şeklinde oluşturur

  
      echo "<table>";
      echo "<tr>";
      echo "<th>TAG NAME</th>";
      echo "<th>TOTAL LIKE</th>";
      foreach ($popular as $key => $value){
        echo "<tr align='center'>";
        echo "<td>" . getNameOfTagWithId($value)  . "</td><td>" .  $values[$value] . "</td>";    //$values[$value] kaç post_id nin kere geçtiğini verir
        echo "</tr>";
      }
      echo "</tr>";

      $nextpage = "?page=" . $ParamNameOfTheAdminMenu . "&pagination=" . ($pagination + 1);
      $backpage = "?page=" . $ParamNameOfTheAdminMenu . "&pagination=" . ($pagination - 1);
      echo "<tr><td>";
      if($pagination > 0){echo "<a href='$backpage' class='previous'>&laquo; Previous</a>";} //Page > 0
      echo "</td><td></td><td>";
      if($pagination < ( $postcount/$tagperpage) - 1 ){echo "<a href='$nextpage' class='next'>Next &raquo;</a>";} //Daha item varsa next goster
      echo "</td></tr>";
      echo "</table>";
      //-------------------------------------------------------------------------------------------
  

    if(!$_POST["action"]){

      $numoftagpp         = get_option('numoftagpp');
      $proxycheck_key     = get_option('proxyCheckKey');
      $ipValidationEnable = get_option('ipValidationEnable');
      $multilike          = get_option('multilike');

      $ipchecked = "";
      if ($ipValidationEnable =="Enable"){$ipchecked = "checked";}

      echo"<form method='post'>";
      wp_nonce_field('benim_eklentim_update','benim_eklentim_update'); 
      echo"
      <label><h3><b>Settings</b></h3></label>
      <label>Tag per page:</label>
      <input type='text' maxlength='2' size='2' name='numoftagpp' value='$numoftagpp'>
      <br>
      <label>ProxyCheck Api Key:</label>
      <input type='text' name='proxyCheckKey' value='$proxycheck_key'>
      Enable: <input type='checkbox' name='ipValidationEnable' value='Enable' $ipchecked> <a target='_blank' href='https://proxycheck.io/'>Register</a>
      <br>
      <label>Max Like Limit Per IP:</label>
      <input type='text' name='multilike' maxlength='2' size='2' value='$multilike'>
      <br>
      <input type='hidden' name='action' value='guncelle'>
      <input type='submit' value='Update'>
      </form>
      <br><br>
      ";
    }
      
    if($_POST["action"]=="guncelle"){
      // Wp_nonce Kontrol edelim
      if (!isset($_POST['benim_eklentim_update']) || ! wp_verify_nonce( $_POST['benim_eklentim_update'], 'benim_eklentim_update' ) ) {
        print 'Error, You can not access this page!';
        exit;
      }else{  // Güvenliği geçti ise

        $numoftagpp           = sanitize_text_field($_POST['numoftagpp']);
        $proxyCheckKey        = sanitize_text_field($_POST['proxyCheckKey']);
        $ipValidationEnable   = sanitize_text_field($_POST['ipValidationEnable']);
        $multilike            = sanitize_text_field($_POST['multilike']);
        
        if ($ipValidationEnable=="Enable"){
          update_option('ipValidationEnable', $ipValidationEnable);
        }else{
          update_option('ipValidationEnable', "Disable");
        }

        if($numoftagpp < 1){
          echo'<div class="updated"><p><strong>ERROR: Tag per page should be integer bigger than 0.</strong></p></div>';
        }
        
        else{ //Hata Yok Imputlarda
          update_option('numoftagpp', $numoftagpp);
          update_option('proxyCheckKey', $proxyCheckKey);
          update_option('multilike', $multilike);
          echo'<div class="updated"><p><strong>Settings Saved.</strong></p></div>';
        }
        
        echo '<meta http-equiv="refresh" content="1">';
      }
    }

    if(!$popular){ die("<br><b>No liked tags can be list!</b>"); }
      
  
  } //Yönetim Paneli Fonksiyonu Bitiş
  
?>