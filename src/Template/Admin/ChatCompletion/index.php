<be-head>
    <style type="text/css">
       .chat-completion-messages {
            min-height: 300px;
        }
        .chat-completion-message-answer p {
            margin: 0;
        }
    </style>
</be-head>


<be-page-content>
    <div class="be-bc-fff be-p-150">
        <div class="chat-completion-messages" id="chat-completion-messages">
            <div class="be-fs-110">
                您好，我是来自 OpenAI 实验室的人工智能 ChatGPT。
            </div>
            <?php
            if ($this->chatCompletion !== false) {
                foreach ($this->chatCompletion->messages as $message) {
                    echo '<div class="be-row be-mt-200">';
                    echo '<div class="be-col-auto">';
                    echo '<span class="be-c-major be-fw-bold">问：</span>';
                    echo '</div>';
                    echo '<div class="be-col be-c-major">';
                    echo $message->prompt;
                    echo '<span class="be-c-major-6">（' . $message->create_time . '）</span>';
                    echo '</div>';
                    echo '</div>';

                    echo '<div class="be-row be-mt-50">';
                    echo '<div class="be-col-auto">';
                    echo '<span class="be-fw-bold">答：</span>';
                    echo '</div>';
                    echo '<div class="be-col chat-completion-message-answer">';
                    echo $message->answer;
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>

        <div class="be-mt-200">
            <div class="be-row">
                <div class="be-col">
                    <textarea name="prompt" class="be-textarea" id="chat-completion-prompt" placeholder="请输入提问内容，按Ctrl+回车发送" style="height: 90px;"></textarea>
                </div>
                <div class="be-col-auto">
                    <div class="be-pl-50">
                        <button type="submit" class="be-btn be-btn-major" id="chat-completion-submit"><i class="bi-send"></i> 发送</button>
                    </div>
                </div>
                <div class="be-col-auto">
                    <div class="be-pl-50">
                        <a href="<?php echo beAdminUrl('Openai.ChatCompletion.index'); ?>" class="be-btn be-btn-green"><i class="bi-plus"></i> 发起新会话</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let chatCompletionId = "<?php echo $this->chatCompletion ? $this->chatCompletion->id : ''; ?>";
        let chatCompletionMessageId = "";

        let handling = false;
        let receiving = false;

        let timer;
        let timerStartTime;

        let $messages = $("#chat-completion-messages");
        let $prompt = $("#chat-completion-prompt");
        let $submit = $("#chat-completion-submit");

        $prompt.change(check).keydown(function (event) {
            check();
            if (event.ctrlKey && event.keyCode === 13) {
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
                url: "<?php echo beAdminUrl('Openai.ChatCompletion.send'); ?>",
                data: {
                    chat_completion_id: chatCompletionId,
                    prompt: $.trim($prompt.val())
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {

                        chatCompletionId = json.chatCompletion.id;
                        chatCompletionMessageId = json.chatCompletion.latestMessage.id;

                        let html = "";
                        html += '<div class="be-row be-mt-200">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-c-major be-fw-bold">问：</span>';
                        html += '</div>';
                        html += '<div class="be-col be-c-major">';
                        html += json.chatCompletion.latestMessage.prompt;
                        html += '<span class="be-c-major-6">（' + json.chatCompletion.latestMessage.create_time + '）</span>';
                        html += '</div>';
                        html += '</div>';

                        html += '<div class="be-row be-mt-50">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-fw-bold">答：</span>';
                        html += '</div>';
                        html += '<div class="be-col chat-completion-message-answer" id="chat-completion-message-answer-' + json.chatCompletion.latestMessage.id + '">';
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

                                let $answer = $("#chat-completion-message-answer-" + chatCompletionMessageId);
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

        function check() {
            if (!handling) {
                if ($.trim($prompt.val()) === "") {
                    $submit.prop('disabled', true);
                } else {
                    $submit.prop('disabled', false);
                }
            }
        }

        check();

        function receive() {
            receiving = true;
            $.ajax({
                async: true,
                url: "<?php echo beAdminUrl('Openai.ChatCompletion.receive'); ?>",
                data: {
                    chat_completion_id: chatCompletionId,
                    chat_completion_message_id: chatCompletionMessageId,
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {
                        if (json.chatCompletionMessage.is_complete === 1) {
                            handling = false;
                            $submit.prop('disabled', false).html('<i class="bi-send"></i> 发送');

                            //$("#chat-completion-message-answer-" + chatCompletionMessageId).html(marked.parse(json.chatCompletionMessage.answer));
                            $("#chat-completion-message-answer-" + chatCompletionMessageId).html(json.chatCompletionMessage.answer);

                            let scrollTo = $messages.offset().top + $messages.height() - $(window).height() + 20;
                            $('html,body').animate({scrollTop:scrollTo},100);

                            /*
                            let $answer = $("#chat-completion-message-answer-" + chatCompletionMessageId);
                            let pos = 0;
                            let length = json.chatCompletionMessage.answer.length;
                            let typeTimer = setInterval(function () {
                                pos++;
                                if (pos < length) {
                                    $answer.html(json.chatCompletionMessage.answer.substring(0, pos));
                                } else {
                                    // 打字效果
                                    let html = "";
                                    html += json.chatCompletionMessage.answer;
                                    html += '<span class="be-c-font-6">（' + json.chatCompletionMessage.create_time + '）</span>';
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
        $(".chat-completion-message-answer").each(function () {
            $(this).html(marked.parse($(this).html()));
        });
       */
    </script>
</be-page-content>