<be-head>
    <style type="text/css">
       .text-completion-messages {
            min-height: 300px;
        }
        .text-completion-message-answer p {
            margin: 0;
        }
    </style>
</be-head>


<be-page-content>
    <div class="be-bc-fff be-p-150">
        <div class="text-completion-messages" id="text-completion-messages">
            <div class="be-fs-110">
                您好，我是来自 OpenAI 实验室的人工智能 ChatGPT。
            </div>
        </div>

        <div class="be-mt-50">
            <div class="be-row">
                <div class="be-col">
                    <input type="text" name="prompt" class="be-input" id="text-completion-prompt" placeholder="请输入提问内容，按回车发送">
                </div>
                <div class="be-col-auto">
                    <div class="be-pl-50">
                        <button type="submit" class="be-btn be-btn-major be-lh-175" id="text-completion-submit"><i class="bi-send"></i> 发送</button>
                    </div>
                </div>
                <div class="be-col-auto">
                    <div class="be-pl-50">
                        <button type="button" class="be-btn be-btn-major" id="text-completion-new"><i class="bi-plus"></i> 发起新会话</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let textCompletionId = "";
        let textCompletionMessageId = "";

        let handling = false;
        let receiving = false;

        let timer;
        let timerStartTime;

        let $messages = $("#text-completion-messages");
        let $prompt = $("#text-completion-prompt");
        let $submit = $("#text-completion-submit");
        let $newSession = $("#text-completion-new");

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
                url: "<?php echo beAdminUrl('Openai.TextCompletion.send'); ?>",
                data: {
                    text_completion_id: textCompletionId,
                    prompt: $.trim($prompt.val())
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {

                        textCompletionId = json.textCompletion.id;
                        textCompletionMessageId = json.textCompletion.latestMessage.id;

                        let html = "";
                        html += '<div class="be-row">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-c-major be-fw-bold">问：</span>';
                        html += '</div>';
                        html += '<div class="be-col be-c-major">';
                        html += json.textCompletion.latestMessage.prompt;
                        html += '<span class="be-c-major-6">（' + json.textCompletion.latestMessage.create_time + '）</span>';
                        html += '</div>';
                        html += '</div>';

                        html += '<div class="be-row be-mt-50 be-mb-200">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-fw-bold">签：</span>';
                        html += '</div>';
                        html += '<div class="be-col text-completion-message-answer" id="text-completion-message-answer-' + json.textCompletion.latestMessage.id + '">';
                        html += '处理中.';
                        html += '</div>';
                        html += '</div>';

                        $messages.append(html);

                        let scrollTo = $messages.offset().top + $messages.height() - $(window).height() + 20;
                        $('html,body').animate({scrollTop:scrollTo},100);

                        $prompt.val("");
                        $submit.html('<i class="bi-send"></i> 回复中...');

                        timer = setInterval(function () {
                            if (handling) {

                                let $answer = $("#text-completion-message-answer-" + textCompletionMessageId);
                                html = $answer.html();
                                html += '.';
                                if (html.slice(-7) === ".......") {
                                    html = html.slice(0, -7);
                                }
                                $answer.html(html);

                            } else {
                                clearInterval(timer);
                                $submit.prop('disabled', false).html('<i class="bi-send"></i> 发送');
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
            $.ajax({
                url: "<?php echo beAdminUrl('Openai.TextCompletion.close'); ?>",
                data: {
                    text_completion_id: textCompletionId,
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {
                        window.location.href = "<?php echo beAdminUrl('Openai.TextCompletion.index'); ?>";
                    } else {
                        alert(json.message);
                    }
                },
                error: function () {
                    alert("System Error!");
                }
            });
        });

        function check() {
            if (!handling) {
                if ($.trim($prompt.val()) === "") {
                    $submit.prop('disabled', true);
                } else {
                    $submit.prop('disabled', false);
                }
            }

            if (textCompletionId === "") {
                $newSession.prop('disabled', true);
            } else {
                $newSession.prop('disabled', false);
            }
        }

        check();

        function receive() {
            receiving = true;
            $.ajax({
                async: true,
                url: "<?php echo beAdminUrl('Openai.TextCompletion.receive'); ?>",
                data: {
                    text_completion_id: textCompletionId,
                    text_completion_message_id: textCompletionMessageId,
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {
                        if (json.textCompletionMessage.is_complete === 1) {
                            handling = false;
                            $submit.prop('disabled', false).html('<i class="bi-send"></i> 发送');

                            //$("#text-completion-message-answer-" + textCompletionMessageId).html(marked.parse(json.textCompletionMessage.answer));
                            $("#text-completion-message-answer-" + textCompletionMessageId).html(json.textCompletionMessage.answer);

                            let scrollTo = $messages.offset().top + $messages.height() - $(window).height() + 20;
                            $('html,body').animate({scrollTop:scrollTo},100);

                            /*
                            let $answer = $("#text-completion-message-answer-" + textCompletionMessageId);
                            let pos = 0;
                            let length = json.textCompletionMessage.answer.length;
                            let typeTimer = setInterval(function () {
                                pos++;
                                if (pos < length) {
                                    $answer.html(json.textCompletionMessage.answer.substring(0, pos));
                                } else {
                                    // 打字效果
                                    let html = "";
                                    html += json.textCompletionMessage.answer;
                                    html += '<span class="be-c-font-6">（' + json.textCompletionMessage.create_time + '）</span>';
                                    $answer.html(html);

                                    clearInterval(typeTimer);
                                }

                                let messagesCcrollHeight1 = $messages.prop("scrollHeight");
                                if (messagesCcrollHeight1 !== messagesCcrollHeight0) {
                                    $messages.animate({scrollTop: messagesCcrollHeight1}, 100);
                                }
                            }, 50);
                            */

                            check();
                        }
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

        /*
        $(".text-completion-message-answer").each(function () {
            $(this).html(marked.parse($(this).html()));
        });
       */
    </script>
</be-page-content>