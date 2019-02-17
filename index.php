<?php
/*
Plugin Name: Like Counter
Plugin URI: https://berkayyildiz.tk
Description: Like Counter Description
Version: 1.1
Author: Berkay YILDIZ
Author URI: https://berkayyildiz.tk
License: GNU
*/

require_once( plugin_dir_path( __FILE__ ) . '/like-plugin.php'); //Plugin Dosyasını Yükle
require_once( plugin_dir_path( __FILE__ ) . '/functions.php'); //Plugin Dosyasını Yükle

function style_and_script_loader() {
  wp_register_style('style_and_script_loader', plugins_url('like-counter.css',__FILE__ ));
  wp_enqueue_style('style_and_script_loader');
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

  //get_option("yazi_sonu");
  return $content.$yazimiz;
}




add_action('admin_menu', 'benim_ekletim_menu'); // Admin menüsüne eklentimizi ekler
function benim_ekletim_menu(){
 add_menu_page('Like Counter','Like Counter', 'manage_options', 'like_counter_manage', 'benim_eklentim_yonetim'); //Admin menüsüne eklenecek eklenti bilgileri
}




function benim_eklentim_yonetim(){  //Yönetim Paneli Ayarları

  
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){  //Tarayıcıdan direkt bu dosyayın çağırılmasını engelle
  die('You are not allowed to call this page directly.');
}


  echo "
  <h1>Liker Counter Management Page</h1>
  <form method='post'>
  <label>Yazı sonlarında görünecek metin? </label>
  ";
  wp_nonce_field('benim_eklentim_update','benim_eklentim_update'); 
  $opt_yazisonu = get_option('yazi_sonu');
  echo"
  <input type='text' name='yazi_sonu' value='$opt_yazisonu'>
  <input type='hidden' name='action' value='guncelle'>
  <input type='submit' value='Güncelle'>
  </form>
  ";

$t = wp_get_post_tags(1);
print_r($t);


$query = new WP_Query( 'posts_per_page=-1&tag=istanbul' );
echo '<pre>' . print_r( $query->posts, 1 ) . '</pre>'; // this line is for debugging purposes only.
  

  if($_POST["action"]=="guncelle"){
    // Wp_nonce Kontrol edelim
    if (!isset($_POST['benim_eklentim_update']) || ! wp_verify_nonce( $_POST['benim_eklentim_update'], 'benim_eklentim_update' ) ) {
      print 'Üzgünüz, bu sayfaya erişim yetkiniz yok!';
      exit;
    }else{
      // Güvenliği geçti ise
      $yazi_sonu = sanitize_text_field($_POST['yazi_sonu']);
      update_option('yazi_sonu', $yazi_sonu);
  echo'<div class="updated"><p><strong>Ayarlar kaydedildi.</strong></p></div>';
    }
  }





  add_filter( 'rest_endpoints', function( $endpoints )
{
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