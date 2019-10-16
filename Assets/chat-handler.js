
$(function(){
    var chatWrap = $(".chat .card");

    chatWrap.animate({
        scrollTop: chatWrap.prop("scrollHeight")
    }, 600);

    $("[name='message']").keyup(function(){
        var value = $(this).val();
            $submit = $("button[type='submit']");

        if ($.trim(value) === '') {
            $submit.addClass('disabled').prop('disabled', true);
        } else {
            $submit.removeClass('disabled').prop('disabled', false);
        }
    });
});