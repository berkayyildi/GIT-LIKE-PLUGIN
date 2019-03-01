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
  
  
    echo "
    <h1>Liker Counter Management Page</h1>
    <form method='post'>
    <label>Tag per page:</label>
    ";
    wp_nonce_field('benim_eklentim_update','benim_eklentim_update'); 
    $opt_yazisonu = get_option('numoftagpp');
    if(!$opt_yazisonu){$opt_yazisonu = 10;}
    echo"
    <input type='text' maxlength='2'  size='2' name='numoftagpp' value='$opt_yazisonu'>
    <input type='hidden' name='action' value='guncelle'>
    <input type='submit' value='Update'>
    </form>
    ";
   
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
  
      if(!$popular){die("<br><b>No liked tags can be list!</b>");}
  
      echo "<table>";
      echo "<tr>";
      echo "<th>TAG NAME</th>";
      echo "<th>TOTAL LIKE</th>";
      foreach ($popular as $key => $value){
        echo "<tr align='center'>";
        echo "<td>" . getNameOfTagWithId($value)  . "</td><td>" .  $values[$value] . "</td>";    //$values[$value] kaç post_id nin kere geçtiğini verir
        echo "</tr>";
      }
  
      $nn = $pagination + 1;
      $bb = $pagination - 1;
      $nextpage = "?page=" . $ParamNameOfTheAdminMenu . "&pagination=" . $nn;
      $backpage = "?page=" . $ParamNameOfTheAdminMenu . "&pagination=" . $bb;
      echo "<tr>";
      if($pagination > 0){echo "<td><a href='$backpage' class='previous'>&laquo; Previous</a><td>";} //Page > 0
      if($pagination < ( $postcount/$tagperpage) - 1 ){echo "<td><a href='$nextpage' class='next'>Next &raquo;</a><td>";} //Daha item varsa next goster
      echo "</tr>";
      echo "</table>";
      //-------------------------------------------------------------------------------------------
  
  
      if($_POST["action"]=="guncelle"){
        // Wp_nonce Kontrol edelim
        if (!isset($_POST['benim_eklentim_update']) || ! wp_verify_nonce( $_POST['benim_eklentim_update'], 'benim_eklentim_update' ) ) {
          print 'Üzgünüz, bu sayfaya erişim yetkiniz yok!';
          exit;
        }else{  // Güvenliği geçti ise
          $numoftagpp = sanitize_text_field($_POST['numoftagpp']);
          update_option('numoftagpp', $numoftagpp);
          echo'<div class="updated"><p><strong>Ayarlar kaydedildi.</strong></p></div>';
          echo '<meta http-equiv="refresh" content="1">';
        }
      }

  
  
  } //Yönetim Paneli Fonksiyonu Bitiş
  

?>