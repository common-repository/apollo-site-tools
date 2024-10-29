var load_more;
var global_selector_lemonsta;
var global_user_name;

(function ($) {
  $.fn.lemonsta = function(user_name) {
    var loc = this;
    global_selector_lemonsta = loc;
    global_user_name = user_name;
    $('head').append('<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">');

    var url = 'https://www.instagram.com/'+user_name+'/?__a=1';

    $.get( url, function( data ) {
      var user_id = data.graphql.user.id;
      getFeed(user_id,loc);
    });

  };
})(jQuery);

function getFeed(user_id,loc){
    var url = "https://www.instagram.com/graphql/query/";
    if (load_more){
      var variables = encodeURIComponent('{"id":"' + user_id + '","first":48,"after":"' + load_more + '"}');
    } else {
      var variables = encodeURIComponent('{"id":"' + user_id+ '","first":48}');
    }
    url = url + "?query_hash=472f257a40c653c64c666ce877d59d2b&query_id=17888483320059182&variables=" + variables;

    $.ajax({
      type: 'GET',
      url: url,
      headers: {
          Cookie: "Cookie"
      },
      success: function (data) {
        $('html').css('cursor', 'pointer');
        $('.animation').removeClass('lds-ripple');
        load_more = data.data.user.edge_owner_to_timeline_media.page_info.end_cursor;
        var html = '<div class="container">';
        html+='<div class="row">';
        for(var i=0;i<data.data.user.edge_owner_to_timeline_media.edges.length;i++){
          var media_url = data.data.user.edge_owner_to_timeline_media.edges[i].node.display_url;
          var href = data.data.user.edge_owner_to_timeline_media.edges[i].node.shortcode;
          html+="<div class='col-md-3'><div class='mt-3 mb-3'><a href='https://www.instagram.com/p/"+href+"' target='_blank'><img class='img-fluid' src="+media_url+"></a></div></div>";
        }
        html+='<div class="col-md-12 text-center mb-5 load-more"><button class="lemonsta-load-more btn" onclick="lemonstaLoadMore()">Load More</button></div>';
        html+='<div class="animation"><div></div></div>';
        html+='</div></div>';
        $(loc).append(html);
      }
    });
}

function lemonstaLoadMore(){
  $('.load-more').remove();
  $(global_selector_lemonsta).lemonsta(global_user_name);
  $('html').css('cursor', 'wait');
  $('.animation').addClass('lds-ripple');
}
