

<be-page-content>
    <div class="be-bc-fff be-p-150">
        <div class="be-fs-110">
            尽可能详细描述下您需要生成的图像。
        </div>

        <div class="be-mt-100">
            <textarea name="prompt" style="width: 100%; height: 120px;" id="image-generation-prompt" placeholder="请输入您的需求，按回车发送">111</textarea>
        </div>

        <div class="be-mt-100">
            <button type="submit" class="be-btn be-btn-major" id="image-generation-submit"><i class="bi-send"></i> 生成</button>
            <button type="button" class="be-btn be-btn-green" id="image-generation-new"><i class="bi-plus"></i> 重新生成</button>
        </div>
    </div>

    <div class="be-mt-150 be-bc-fff be-p-150">
        <div class="be-fs-110">
            生成结果：
            <div id="image-generation-result"></div>
        </div>

    </div>


    <script>

        let imageGenerationId = "";

        let handling = false;
        let receiving = false;

        let timer;
        let timerStartTime;

        let $result = $("#image-generation-result");
        let $prompt = $("#image-generation-prompt");
        let $submit = $("#image-generation-submit");
        let $newSession = $("#image-generation-new");

        $prompt.change(check).keydown(function (event) {
            check();
            if (event.keyCode === 13) {
                $submit.trigger("click");
            }
        })

        $submit.click(function () {

            if ($.trim($prompt.val()) === "") {
                return;
            }

            handling = true;
            $submit.prop('disabled', true).html('<i class="bi-send"></i> 发送中...');

            $.ajax({
                url: "<?php echo beAdminUrl('Openai.ImageGeneration.send'); ?>",
                data: {
                    prompt: $.trim($prompt.val())
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {

                        imageGenerationId = json.imageGeneration.id;

                        $result.html('处理中.');

                        $submit.html('<i class="bi-send"></i> 生成中...');

                        timer = setInterval(function () {
                            if (handling) {

                                let html = $result.html();
                                html += '.';
                                if (html.slice(-7) === ".......") {
                                    html = html.slice(0, -7);
                                }
                                $result.html(html);

                            } else {
                                clearInterval(timer);
                                $submit.prop('disabled', false).html('<i class="bi-send"></i> 生成');
                            }

                            if (!receiving) {
                                receive();
                            }
                        }, 200);
                    } else {
                        alert(json.message);
                    }
                },
                error: function () {
                    alert("System Error!");
                }
            });
        });

        $newSession.click(function () {
            window.location.href = "<?php echo beAdminUrl('Openai.ImageGeneration.index'); ?>";
        });

        function check() {
            if (!handling) {
                if ($.trim($prompt.val()) === "") {
                    $submit.prop('disabled', true);
                } else {
                    $submit.prop('disabled', false);
                }
            }

            $newSession.prop('disabled', true);
        }

        check();

        function receive() {
            receiving = true;
            $.ajax({
                async: true,
                url: "<?php echo beAdminUrl('Openai.ImageGeneration.receive'); ?>",
                data: {
                    image_generation_id: imageGenerationId,
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {

                        handling = false;
                        $submit.prop('disabled', false).html('<i class="bi-send"></i> 生成');

                        let html = '<img src="' + json.imageGeneration.url + '" alt="">';
                        $result.html(html);

                        check();

                    } else {
                        alert(json.message);
                    }
                    receiving = false;
                },
                error: function () {
                    alert("System Error!");
                    receiving = false;
                }
            });
        }
    </script>
</be-page-content>