
$(function(){
    var chatWrap = $(".chat .card");

    chatWrap.animate({
        scrollTop: chatWrap.prop("scrollHeight")
    }, 600);
    
});