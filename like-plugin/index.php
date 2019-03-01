<?php
/*
Plugin Name: Like Counter
Plugin URI: https://github.com/berkayyildi/Wordpress-Like-Plugin
Description: Like Counter Description
Version: 1.2
Author: Berkay YILDIZ
Author URI: https://berkayyildiz.tk
License: GNU
*/

require_once( plugin_dir_path( __FILE__ ) . '/inc/like-widget.php'); //Widget Dosyasını Yükle
require_once( plugin_dir_path( __FILE__ ) . '/inc/functions.php'); //Plugin Dosyasını Yükle
require_once( plugin_dir_path( __FILE__ ) . '/adminpage.php'); //Plugin Dosyasını Yükle

add_action( 'init','style_and_script_loader');  //JS vs CSS dosyalarını ekle
function style_and_script_loader() {
  wp_enqueue_script( 'jquery' );
  wp_register_style('style_and_script_loader', plugins_url('like-counter.css',__FILE__ ));
  wp_enqueue_style('style_and_script_loader');
}


add_action( 'wp_head', 'so_enqueue_scripts' );  //Post Request için javascript dosyalarını ekle
function so_enqueue_scripts(){
  wp_register_script( 'ajaxHandle', plugins_url('like-counter.js',__FILE__ ), array() );
  wp_enqueue_script( 'ajaxHandle' );
  wp_localize_script( 'ajaxHandle', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); //JS içerisindeki bilgileri değiştir
}


add_filter("the_content","benim_eklentim_Function");  //Her yazının contenti için filtre oluştur
function benim_eklentim_Function($content){ //Yazılan yazının sonuna eklene yapar
  global $post;
  global $wpdb;
  $table_name = $wpdb->prefix . "like_counter";
  $ip         = sanitize_text_field($_SERVER['REMOTE_ADDR']);

  $count = $wpdb->query("SELECT id FROM $table_name WHERE post_id = '$post->ID' AND ip_addr = '$ip' ");
  if ($count){
    $buttontext = "Unlike";
  }else{
    $buttontext = "Like";
  }
  $yazimiz = "<button class='like-Unlike' onclick='send($post->ID,this.innerHTML)' id='like-button'>$buttontext</button>";
  return $content.$yazimiz;
}

add_action( 'wp_footer', 'footer_information' );
function footer_information() { //Alert göstermek için gerekli areayı footer a ekle
  echo '<div id="alert-area" class="alert-area">';
}

//------------AJAX ile gelen beğeni isteklerini handle etmek için------------
add_action( "wp_ajax_myaction", "so_wp_ajax_function" );          //Kayıtlı kullanıcı için admin_ajax a giden requesti hook et
add_action( "wp_ajax_nopriv_myaction", "so_wp_ajax_function" );   //Ziyaretçi için admin_ajax a giden requesti hook et
function so_wp_ajax_function(){   //İstek geldiğinde yapılacak

  global $wpdb;
  $table_name = $wpdb->prefix . "like_counter";

  $ip     = sanitize_text_field($_SERVER['REMOTE_ADDR']);
  $postid = sanitize_text_field($_POST['postid']);
  $type   = sanitize_text_field($_POST['type']);

  if($type == "Like"){

    $count = $wpdb->query("SELECT id FROM $table_name WHERE post_id = '$postid' AND ip_addr = '$ip' ");

    if ($count != 0){ //Daha önce post beğenildi mi bu ip ile kontrol et
      die("Error: You already like this post before!");
    }

    $wpdb->insert( 
      $table_name, 
      array( 
          'post_id' => (int) $postid,
          'ip_addr' =>  $ip,
      )
    );

    if ($wpdb->last_error){ //Hata var mı kontrol
      die("Error: SQL Error!");
    }else{
      echo getPostNameWithId($postid) . " Liked";
    }

  }else{

    $count = $wpdb->query("DELETE FROM $table_name WHERE post_id = '$postid' AND ip_addr = '$ip' ");  //O ip ile o post'u beğenildiyse sil

    if ($count == 0){ //Affected rows var mı bak
      die("Error: You do not like this post before!");
    }else{
      echo getPostNameWithId($postid) . " UnLiked";
    }

  }

  wp_die(); //Cevaptaki 0 dan kurtulmak için die edilmeli
}
//---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'creating_plugin_table' ); //Tablo oluşturma işlemini eklentinin aktivasyonu sırasında yapmak için register_activation_hook() kullanıyoruz
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


?>