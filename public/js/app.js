//form submission handler
window.onload = function () {
    $('#circleG').hide();
    $('#success').hide();
    $('#form').show();
    $('form').ajaxForm({
        uploadProgress: function () {
            //show preloader
            $('#circleG').show();
            $('#form').hide();
        },
        complete: function (result) {
            if (result.responseText != 'error') {
                //used to notify user that the file is processed and results ready
                $('#circleG').hide();
                $('#success').show();
            } else {
                window.location = '/error';
            }
        }
    });
};
// 'back to main' button handler
$('#to_main').click(function () {
    window.location = '/';
});
// 'see the results' button handler
$('#results').click(function () {
    $('#success').hide();
    $('#circleG').show();
    $.ajax({
        url: "/part",
        type: "POST",
        complete: function () {
            window.location = '/results';
        }
    });
});




