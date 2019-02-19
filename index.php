<?php
/*
Plugin Name: Like Counter
Plugin URI: https://berkayyildiz.tk
Description: Like Counter Description
Version: 1.2
Author: Berkay YILDIZ
Author URI: https://berkayyildiz.tk
License: GNU
*/

require_once( plugin_dir_path( __FILE__ ) . '/inc/like-widget.php'); //Widget Dosyasını Yükle
require_once( plugin_dir_path( __FILE__ ) . '/inc/functions.php'); //Plugin Dosyasını Yükle

function style_and_script_loader() {
  //wp_register_style('style_and_script_loader', plugins_url('like-counter.css',__FILE__ ));
  //wp_enqueue_style('style_and_script_loader');
  wp_register_script( 'style_and_script_loader', plugins_url('jquery.js',__FILE__ ));
  wp_enqueue_script('style_and_script_loader');
}

add_action( 'init','style_and_script_loader');  //JS vs CSS dosyalarını ekle



add_action( 'wp_head', 'so_enqueue_scripts' );  //Post Request için javascript dosyalarını ekle
function so_enqueue_scripts(){
  global $post; //post bilgilerini içeren değişkeni global olarak al
  wp_register_script( 'ajaxHandle', plugins_url('like-counter.js',__FILE__ ), array() );
  wp_enqueue_script( 'ajaxHandle' );
  wp_localize_script( 'ajaxHandle', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); //JS içerisindeki bilgileri değiştir
}




add_filter("the_content","benim_eklentim_Function");  //Her yazının contenti için filtre oluştur
function benim_eklentim_Function($content){ //Yazılan yazının sonuna eklene yapar
  global $post;
  
  $yazimiz = "<button class='like-Unlike' onclick='send($post->ID,this.innerHTML)' id='like-button'>Like</button>";

  //get_option("numoftagpp");
  return $content.$yazimiz;
}



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
  <label> Number of Tag per page to show:</label>
  ";
  wp_nonce_field('benim_eklentim_update','benim_eklentim_update'); 
  $opt_yazisonu = get_option('numoftagpp');
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
    foreach ($result as $details) {
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

    $nn = $pagination + 1;
    $bb = $pagination - 1;
    $nextpage = "?page=" . $ParamNameOfTheAdminMenu . "&pagination=" . $nn;
    $backpage = "?page=" . $ParamNameOfTheAdminMenu . "&pagination=" . $bb;
    echo "<tr>";
    if($pagination > 0){echo "<td><a href='$backpage' class='previous'>&laquo; Previous</a><td>";} //Page > 0
    if($popular){echo "<td><a href='$nextpage' class='next'>Next &raquo;</a><td>";} //Daha item varsa next goster
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

  add_filter( 'rest_endpoints', function( $endpoints ){
    if( isset( $endpoints['/wp/v2/tags'][0]['args']['per_page']['maximum'] ) )
        $endpoints['/wp/v2/tags'][0]['args']['per_page']['maximum'] = 120;

    return $endpoints;  
  } );



} //Yönetim Paneli Fonksiyonu Bitiş







//------------AJAX ile gelen beğeni isteklerini handle etmek için------------
add_action( "wp_ajax_myaction", "so_wp_ajax_function" );
add_action( "wp_ajax_nopriv_myaction", "so_wp_ajax_function" );
function so_wp_ajax_function(){   //İstek geldiğinde yapılacak

  global $wpdb;

  $table_name = $wpdb->prefix . "like_counter";

  $ip     = sanitize_text_field($_SERVER['REMOTE_ADDR']);
  $postid = sanitize_text_field($_POST['postid']);
  $type   = sanitize_text_field($_POST['type']);

  if($type == "Like"){

    $wpdb->insert( 
      $table_name, 
      array( 
          'post_id' => (int) $postid,
          'ip_addr' =>  $ip,
          //'date'      => time()
      )
    );

    if ($wpdb->last_error){ //Hata var mı kontrol
      die("HATA");
    }else{
      echo $postid . " Liked";
    }


  }else{

    $count = $wpdb->query("DELETE FROM $table_name WHERE post_id = '$postid' AND ip_addr = '$ip' ");

    if ($count == 0){ //Affected rows var mı bak
      die("HATA");
    }else{
      echo $postid . " UnLiked";
    }

  }


  wp_die(); //Cevaptaki 0 dan kurtulmak için die edilmeli
}
//---------------------------------------------------------------------------


function creating_plugin_table(){ //Veritabanını oluştur
  global $wpdb;
  
  $table_name = $wpdb->prefix . "like_counter"; //Tablo adımız ve tablomuzun prefixi.
  
  $sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  post_id VARCHAR(255) NOT NULL,
  ip_addr VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  INDEX `post_id`(`post_id`) USING BTREE
  ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );  //Tablomuzu oluşturduk.
  }
  register_activation_hook( __FILE__, 'creating_plugin_table' ); //Tablo oluşturma işlemini eklentinin aktivasyonu sırasında yapmak için register_activation_hook() kullanıyoruz




?>