function send(postid,type){

  jQuery.ajax
  ({
      url: myAjax.ajaxurl,
      type: 'POST',
      data: {
        'action':'myaction',
        'postid' : postid,
        'type' : type
      },
      success: function(data)
      {
            alert(data);
      },
      error: function(data)
      {
            alert(data);
      }
  });

}


jQuery(document).ready(function($){

  $(".like-Unlike").click(function(e) {
    if ($(this).html() == "Like") {
        $(this).html('Unlike');
    }
    else {
        $(this).html('Like');
    }
    return false;
  });

});