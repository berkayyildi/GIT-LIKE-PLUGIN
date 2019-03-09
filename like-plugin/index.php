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

require_once( plugin_dir_path( __FILE__ ) . '/inc/like-widget.php');    //Widget Dosyasını Yükle
require_once( plugin_dir_path( __FILE__ ) . '/inc/functions.php');      //Fonksiyonları Yükle
require_once( plugin_dir_path( __FILE__ ) . '/adminpage.php');          //Admin Page Manager Include
require_once( plugin_dir_path( __FILE__ ) . '/like_req_handler.php');   //AJAX Request handler Include



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
  global $post, $wpdb;

  $postid = $post->ID;
  
  $table_name = $wpdb->prefix . "like_counter";
  $ip         = sanitize_text_field($_SERVER['REMOTE_ADDR']);

  if ($_SESSION['postlike'][$postid] == 1){
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



register_activation_hook( __FILE__, 'creating_plugin_table' ); //Tablo oluşturma işlemini eklentinin aktivasyonu sırasında yapmak için register_activation_hook() kullanıyoruz
function creating_plugin_table(){ //Veritabanını oluştur
  global $wpdb;
  
  $table_name = $wpdb->prefix . "like_counter"; //Tablo adımız ve tablomuzun prefixi.
  
  $sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  post_id VARCHAR(255) NOT NULL,
  ip_addr VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  INDEX `post_id`(`post_id`) USING BTREE,
  INDEX `ip_addr`(`ip_addr`) USING BTREE
  ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );  //Tablomuzu oluşturduk.


  update_option('numoftagpp', 10);     //Varsayılan sayfalama limitini 10 olarak ayarla
  update_option('ipValidation', 0);    //Spam kontrolü için ip kontrolü varsayılan kapalı
  update_option('proxyCheckKey', "");  //proxycheck.io Api Key
  update_option('multilike', "1");     //Multi beğeni izni

  }


?>