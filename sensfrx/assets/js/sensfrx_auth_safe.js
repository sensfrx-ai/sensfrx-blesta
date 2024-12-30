// Show log essage on login page
$(document).ready(function(){
    $('form').on('submit', function(e) {
        var device_id= _sensfrx("getRequestString");
        console.log(device_id, "ddddddd");
        $(this).append('<input type="hidden" name="authsafe_device_id" value="'+device_id+'"/> ');
        return true;
    });
    $('a').on('click', function(e) {
        var url=$(this).prop("href");
        var logout_text = "logout";
        if(url.indexOf(logout_text) != -1){
            var device_id =  _sensfrx("getRequestString"); 
            $(this).prop("href", $(this).prop("href")+"?authsafe_device_id="+device_id)
            return true;
        }
    });
});
