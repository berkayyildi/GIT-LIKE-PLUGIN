<?php

function getPostNameWithId($post_id){
    
    $queried_post = get_post($post_id);
    return $queried_post->post_title;

}

function getNameOfTagWithId($tag_id){
    
    return get_term_by('term_id',$tag_id,'post_tag')->name;

}

?>