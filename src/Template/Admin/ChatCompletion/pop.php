<be-head>
    <style type="text/css">
        .chat-completion-history {
            max-width: 200px;
            height: 100%;
            overflow-y: auto;
            border-right: #eee 1px solid;
            padding-right: 2rem;
        }
        .chat-completion-history a {
            color: var(--major-color);
        }
        .chat-completion-history a:hover {
            color: var(--major-color2);
        }

        .chat-completion-messages {
            min-height: calc(100vh - 200px);
        }
        .chat-completion-message-answer p {
            margin: 0;
        }

        .chat-completion-messages .be-checkbox {
            width: 1.5rem;
            height: 1.5rem;
        }
    </style>
</be-head>


<be-page-content>
    <div class="be-bc-fff be-p-150">
        <div class="be-row">

            <?php
            if ($this->chatCompletionHistory['total'] > 0) {
                ?>
                <div class="be-col-24 be-md-col-auto">
                    <div class="chat-completion-history">
                        <div class="be-fs-110">
                            最近会话：
                        </div>

                        <?php
                        foreach ($this->chatCompletionHistory['rows'] as $chatCompletion) {
                            echo '<div class="be-mt-50">';
                            $url = beAdminUrl('Openai.ChatCompletion.pop', ['chat_completion_id' => $chatCompletion->id, 'callback' => $this->callback]);
                            echo '<a class="be-d-block be-t-ellipsis-2" href="' . $url . '" title="' . $chatCompletion->name . '">';
                            echo $chatCompletion->prompt;
                            echo '</a>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                <div class="be-col-24 be-md-col-auto">
                    <div class="be-pl-200 be-pt-200"></div>
                </div>
                <?php
            }
            ?>

            <div class="be-col-24 be-md-col">
                <div class="chat-completion-messages" id="chat-completion-messages">
                    <div class="be-fs-110">
                        向 ChatGPT 提问，选中内容，插入到编辑器中。
                    </div>

                    <?php
                    if ($this->chatCompletion !== false) {
                        foreach ($this->chatCompletion->messages as $message) {
                            echo '<div class="be-mt-100 be-p-50  be-bc-major-9">';
                            echo '<div class="be-row">';
                            echo '<div class="be-col-auto be-lh-200">';
                            echo '<span class="be-c-major be-fw-bold">问：</span>';
                            echo '</div>';
                            echo '<div class="be-col be-lh-200">';
                            echo '<span class="be-c-major">' . $message->prompt . '</span>';
                            echo '<span class="be-c-major-6">（' . $message->create_time . '）</span>';
                            echo '</div>';
                            echo '<div class="be-col-auto">';
                            echo '<span class="be-pl-50"><input type="checkbox" class="be-checkbox" name="chat_completion_message_prompt" value="'.$message->id.'"></span>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';

                            echo '<div class="be-mt-50 be-p-50">';
                            echo '<div class="be-row">';
                            echo '<div class="be-col-auto">';
                            echo '<span class="be-fw-bold">答：</span>';
                            echo '</div>';
                            echo '<div class="be-col chat-completion-message-answer">';
                            echo $message->answer;
                            echo '</div>';
                            echo '<div class="be-col-auto">';
                            echo '<span class="be-pl-50"><input type="checkbox" class="be-checkbox" name="chat_completion_message_answer" value="'.$message->id.'"></span>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>

                <div class="be-mt-100 be-ta-right">
                    选择:
                    <a class="be-link-major be-ml-50" href="javascript:void(0);" id="chat-completion-toggle-all">全部</a>
                    <a class="be-link-major be-ml-50" href="javascript:void(0);" id="chat-completion-toggle-q">问</a>
                    <a class="be-link-major be-ml-50" href="javascript:void(0);" id="chat-completion-toggle-a">答</a>
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
                                <?php
                                $url = beAdminUrl('Openai.ChatCompletion.pop', ['callback' => $this->callback]);
                                ?>
                                <a href="<?php echo $url; ?>" class="be-btn be-btn-green"><i class="bi-plus"></i> 发起新会话</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        let chatCompletion = <?php echo $this->chatCompletion ? json_encode($this->chatCompletion) : 'false'; ?>;

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
        });

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

                        if (chatCompletion === false) {
                            chatCompletion = json.chatCompletion;
                        }

                        chatCompletionId = json.chatCompletion.id;
                        chatCompletionMessageId = json.chatCompletion.latestMessage.id;

                        let html = "";
                        html += '<div class="be-row be-mt-200 be-bc-major-9">';
                        html += '<div class="be-col-auto be-lh-200">';
                        html += '<span class="be-c-major be-fw-bold">问：</span>';
                        html += '</div>';
                        html += '<div class="be-col be-lh-200">';
                        html += '<span class="be-c-major">' + json.chatCompletion.latestMessage.prompt + '</span>';
                        html += '<span class="be-c-major-6">（' + json.chatCompletion.latestMessage.create_time + '）</span>';
                        html += '</div>';
                        html +=  '<div class="be-col-auto">';
                        html +=  '<span class="be-pl-50"><input type="checkbox" class="be-checkbox" name="chat_completion_message_prompt" value="' + json.chatCompletion.latestMessage.id + '"></span>';
                        html +=  '</div>';
                        html += '</div>';

                        html += '<div class="be-row be-mt-50">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-fw-bold">答：</span>';
                        html += '</div>';
                        html += '<div class="be-col chat-completion-message-answer" id="chat-completion-message-answer-' + json.chatCompletion.latestMessage.id + '">';
                        html += '处理中.';
                        html += '</div>';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-pl-50"><input type="checkbox" class="be-checkbox" name="chat_completion_message_answer" value="' + json.chatCompletion.latestMessage.id + '"></span>';
                        html += '</div>';
                        html += '</div>';

                        $messages.append(html);

                        let scrollTo = $messages.offset().top + $messages.height() - $(window).height() + 180;
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

        let scrollTo = $messages.offset().top + $messages.height() - $(window).height() + 180;
        $('html,body').animate({scrollTop:scrollTo},100);

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

                            $("#chat-completion-message-answer-" + chatCompletionMessageId).html(json.chatCompletionMessage.answer);

                            let scrollTo = $messages.offset().top + $messages.height() - $(window).height() + 20;
                            $('html,body').animate({scrollTop:scrollTo},100);

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

        window.addEventListener('message', function (event) {
            $prompt.val(event.data);
            check();
        });

        // 回调
        function callback() {

            if (chatCompletion === false) {
                return;
            }

            let promptIds = [];
            $(".chat-completion-messages .be-checkbox[name='chat_completion_message_prompt']").each(function () {
                if ($(this).prop("checked")) {
                    promptIds.push($(this).val());
                }
            });

            let answerIds = [];
            $(".chat-completion-messages .be-checkbox[name='chat_completion_message_answer']").each(function () {
                if ($(this).prop("checked")) {
                    answerIds.push($(this).val());
                }
            });

            for (let messasge of chatCompletion.messages) {
                messasge.prompt_selected = promptIds.indexOf(messasge.id) === -1 ? 0 : 1;
                messasge.answer_selected = answerIds.indexOf(messasge.id) === -1 ? 0 : 1;
            }

            <?php echo $this->callbackCode; ?>
        }


        // 切换选中全部
        $("#chat-completion-toggle-all").click(function () {
            let isAllChecked = true;
            $(".chat-completion-messages .be-checkbox").each(function () {
                if (!$(this).prop("checked")) {
                    isAllChecked = false;
                }
            });

            $(".chat-completion-messages .be-checkbox").prop("checked", !isAllChecked);

            callback();
        });

        // 切换选中问题
        $("#chat-completion-toggle-q").click(function () {
            let isAllChecked = true;
            $(".chat-completion-messages .be-checkbox[name='chat_completion_message_prompt']").each(function () {
                if (!$(this).prop("checked")) {
                    isAllChecked = false;
                }
            });

            $(".chat-completion-messages .be-checkbox[name='chat_completion_message_prompt']").prop("checked", !isAllChecked);

            callback();
        });

        // 切换选中答案
        $("#chat-completion-toggle-a").click(function () {
            let isAllChecked = true;
            $(".chat-completion-messages .be-checkbox[name='chat_completion_message_answer']").each(function () {
                if (!$(this).prop("checked")) {
                    isAllChecked = false;
                }
            });

            $(".chat-completion-messages .be-checkbox[name='chat_completion_message_answer']").prop("checked", !isAllChecked);

            callback();
        });

        $(".chat-completion-messages .be-checkbox").click(callback);
    </script>
</be-page-content>