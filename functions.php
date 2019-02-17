<?php

function getPostNameWithId($post_id){
    
    $queried_post = get_post($post_id);
    return $queried_post->post_title;

}


?>