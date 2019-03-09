<?php

function register_session(){    //Session Register Et
    if( !session_id() )
        session_start();
}
add_action('init','register_session');


function getPostNameWithId($post_id){
    
    $queried_post = get_post($post_id);
    return $queried_post->post_title;

}

function getNameOfTagWithId($tag_id){
    
    return get_term_by('term_id',$tag_id,'post_tag')->name;

}

function checkIsIpValid($ip, $api_key){
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => 'http://proxycheck.io/v2/' . $ip . '?key=' . $api_key . '&vpn=1',
    ]);
    $resp = curl_exec($curl);
    curl_close($curl);

    $json_resp = json_decode($resp);

    $isproxy = $json_resp->$ip->proxy; //Cevabın proxy mi kısmını al sadece

    if ($isproxy == "yes"){
        return false;
    }else{
        return true;
    }
    
}



?>