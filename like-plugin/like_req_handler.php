<?php
add_action( "wp_ajax_myaction", "so_wp_ajax_function" );          //Kay�tl� kullan�c� i�in admin_ajax a giden requesti hook et
add_action( "wp_ajax_nopriv_myaction", "so_wp_ajax_function" );   //Ziyaret�i i�in admin_ajax a giden requesti hook et
function so_wp_ajax_function(){   //�stek geldi�inde yap�lacak

  global $wpdb;
  $table_name = $wpdb->prefix . "like_counter";

  $ip     = sanitize_text_field($_SERVER['REMOTE_ADDR']);
  $postid = sanitize_text_field($_POST['postid']);
  $type   = sanitize_text_field($_POST['type']);

  if(get_option('ipValidationEnable') == "Enable"){ //Ip validation aciksa
    if(!checkIsIpValid($ip, get_option('proxyCheckKey'))){ //Ip valid degilse
      die("Banned IP: You can not like this post!");
    }
  }

  if($type == "Like"){  //Beğeni Istegi Geldiyse

    
    if($_SESSION['postlike'][$postid] == 1){
      die("Error: You already like this post before!"); //Cookie ile 2. beğeniyi engelle
    }


    $count = $wpdb->query("SELECT id FROM $table_name WHERE post_id = '$postid' AND ip_addr = '$ip' ");

    
    if (get_option('multilike')){ //Multiple beğeniye izin varsa onu al
      $multiplelikes = get_option('multilike') - 1;
    }else{
      $multiplelikes = 0;
    }

    if ($count > 0 + $multiplelikes){ //Daha �nce post be�enildi mi bu ip ile kontrol et
      die("Error: You already like this post before!");
    }

    $wpdb->insert( 
      $table_name, 
      array( 
          'post_id' => (int) $postid,
          'ip_addr' =>  $ip,
      )
    );

    if ($wpdb->last_error){ //Hata var mi kontrol
      die("Error: SQL Error!");
    }else{      
      $_SESSION['postlike'][$postid] = 1;       //Beğenildi olarak session kaydet
      echo getPostNameWithId($postid) . " Liked";
    }

  }else{  //Unlike istegi geldiyse

    $_SESSION['postlike'][$postid] = 0;  //Sessionda unliked olarak işaretle

    $count = $wpdb->query("DELETE FROM $table_name WHERE post_id = '$postid' AND ip_addr = '$ip' ");  //O ip ile o post'u be�enildiyse sil

    if ($count == 0){ //Affected rows var mi bak
      die("Error: You do not like this post before!");
    }else{
      echo getPostNameWithId($postid) . " UnLiked";
    }
    

  }

  wp_die(); //Cevaptaki 0 dan kurtulmak icin die edilmeli
}
?>