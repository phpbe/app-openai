$(function () {

    $("#auth-login-form").validate({
        rules: {
            password: {
                required: true
            }
        },
        messages: {
            password: {
                required: "请输入密码！"
            }
        },

        submitHandler: function (form) {
            $.ajax({
                url: authLoginUrl,
                data : $(form).serialize(),
                method: "POST",
                success: function (json) {
                    if (json.success) {
                        window.location.href = json.redirectUrl;
                    } else {
                        alert(json.message);
                    }
                },
                error: function () {
                    alert("System Error!");
                }
            });
        }
    });

});

