<be-head>
    <style type="text/css">
       .text-completion-messages {
            height: calc(100vh - 120px);
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
                向 ChatGPT 提问，回复内容可插入到编辑器中。
            </div>
            <?php
            if ($this->textCompletion !== false) {
                foreach ($this->textCompletion->messages as $message) {
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
                    echo '<div class="be-col text-completion-message-answer">';
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
                    <textarea name="prompt" class="be-textarea" id="text-completion-prompt" placeholder="请输入提问内容，按Ctrl+回车发送" style="height: 90px;"></textarea>
                </div>
                <div class="be-col-auto">
                    <div class="be-pl-50">
                        <button type="submit" class="be-btn be-btn-major" id="text-completion-submit"><i class="bi-send"></i> 发送</button>
                    </div>
                </div>
                <div class="be-col-auto">
                    <div class="be-pl-50">
                        <a href="<?php echo beAdminUrl('Openai.TextCompletion.index', ['text_completion_id' => 'new']); ?>" class="be-btn be-btn-green"><i class="bi-plus"></i> 发起新会话</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let textCompletionId = "<?php echo $this->textCompletion ? $this->textCompletion->id : ''; ?>";
        let textCompletionMessageId = "";

        let handling = false;
        let receiving = false;

        let timer;
        let timerStartTime;

        let $messages = $("#text-completion-messages");
        let $prompt = $("#text-completion-prompt");
        let $submit = $("#text-completion-submit");

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
                        html += '<div class="be-row be-mt-200">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-c-major be-fw-bold">问：</span>';
                        html += '</div>';
                        html += '<div class="be-col be-c-major">';
                        html += json.textCompletion.latestMessage.prompt;
                        html += '<span class="be-c-major-6">（' + json.textCompletion.latestMessage.create_time + '）</span>';
                        html += '</div>';
                        html += '</div>';

                        html += '<div class="be-row be-mt-50">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-fw-bold">答：</span>';
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