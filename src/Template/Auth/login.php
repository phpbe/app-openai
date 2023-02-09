<be-head>
    <?php
    $wwwUrl = \Be\Be::getProperty('App.Openai')->getWwwUrl();
    ?>
    <script src="<?php echo $wwwUrl; ?>/js/Auth/login.js"></script>
    <script>
        const authLoginUrl = "<?php echo beUrl('Openai.Auth.login'); ?>";
    </script>
</be-head>


<be-page-content>
    <div class="be-row be-py-400">
        <div class="be-col-0 be-md-col-2 be-lg-col-4 be-xl-col-6">
        </div>
        <div class="be-col-24 be-md-col-20 be-lg-col-16 be-xl-col-10">

            <div class="be-fs-200 be-fw-bold be-ta-center">登录</div>

            <form id="auth-login-form" class="be-mt-200" method="post">
                <div class="be-floating be-mt-150">
                    <input type="password" name="password" class="be-input" placeholder="密码" />
                    <label class="be-floating-label">密码 <span class="be-c-red">*</span></label>
                </div>

                <div class="be-mt-150">
                    <button type="submit" class="be-btn be-btn-major be-btn-lg be-mt-150 be-w-100">登录</button>
                </div>
            </form>
        </div>
    </div>
</be-page-content>