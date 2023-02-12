<be-head>
    <style type="text/css">
       .completion-session-messages {
            min-height: 300px;
        }
        .completion-session-message-answer p {
            margin: 0;
        }
    </style>
</be-head>


<be-page-content>
    <div class="be-bc-fff be-p-200">
        <?php
        echo '<div class="completion-session-messages" id="completion-session-messages">';
        echo '<div class="be-mt-50 be-mb-200">';
        echo '您好，我是来自 OpenAI 实验室的人工智能 ChatGPT。';
        echo '</div>';
        echo '</div>';

        echo '<div class="be-mt-50">';
        echo '<div class="be-row">';
        echo '<div class="be-col">';
        echo '<input type="text" name="question" class="be-input" id="completion-session-question" placeholder="请输入提问内容，按回车发送">';
        echo '</div>';
        echo '<div class="be-col-auto">';
        echo '<div class="be-pl-50">';
        echo '<button type="submit" class="be-btn be-btn-major be-lh-175" id="completion-session-submit"><i class="bi-send"></i> 发送</button>';
        echo '</div>';
        echo '</div>';
        echo '<div class="be-col-auto">';
        echo '<div class="be-pl-50">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        ?>
    </div>

    <script>
        let sessionId = "";
        let messageId = "";

        let handling = false;
        let receiving = false;

        let timer;
        let timerStartTime;

        let $messages = $("#completion-session-messages");
        let $question = $("#completion-session-question");
        let $submit = $("#completion-session-submit");
        let $newSession = $("#completion-session-new");

        $question.change(check).keydown(function (event) {
            check();
            if (event.keyCode === 13) {
                $submit.trigger("click");
            }
        })

        $submit.click(function () {

            if ($.trim($question.val()) === "") {
                return;
            }

            handling = true;
            $submit.prop('disabled', true).html('<i class="bi-send"></i> 发送中...');

            $.ajax({
                url: "<?php echo beAdminUrl('Openai.Completion.send'); ?>",
                data: {
                    session_id: sessionId,
                    question: $.trim($question.val())
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {

                        sessionId = json.session.id;
                        messageId = json.session.latestMessage.id;

                        let html = "";
                        html += '<div class="be-row">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-c-major be-fw-bold">问：</span>';
                        html += '</div>';
                        html += '<div class="be-col be-c-major">';
                        html += json.session.latestMessage.question;
                        html += '<span class="be-c-major-6">（' + json.session.latestMessage.create_time + '）</span>';
                        html += '</div>';
                        html += '</div>';

                        html += '<div class="be-row be-mt-50 be-mb-200">';
                        html += '<div class="be-col-auto">';
                        html += '<span class="be-fw-bold">签：</span>';
                        html += '</div>';
                        html += '<div class="be-col completion-session-message-answer" id="completion-session-message-answer-' + json.session.latestMessage.id + '">';
                        html += '处理中.';
                        html += '</div>';
                        html += '</div>';

                        $messages.append(html);

                        let scrollTo = $messages.offset().top + $messages.height() - $(window).height() + 20;
                        $('html,body').animate({scrollTop:scrollTo},100);

                        $question.val("");
                        $submit.html('<i class="bi-send"></i> 回复中...');

                        timer = setInterval(function () {
                            if (handling) {

                                let $answer = $("#completion-session-message-answer-" + messageId);
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
                url: "<?php echo beAdminUrl('Openai.Completion.close'); ?>",
                data: {
                    session_id: sessionId,
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {
                        window.location.href = "<?php echo beAdminUrl('Openai.Completion.session'); ?>";
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
                if ($.trim($question.val()) === "") {
                    $submit.prop('disabled', true);
                } else {
                    $submit.prop('disabled', false);
                }
            }

            if (sessionId === "") {
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
                url: "<?php echo beAdminUrl('Openai.Completion.receive'); ?>",
                data: {
                    session_id: sessionId,
                    message_id: messageId,
                },
                method: "POST",
                success: function (json) {
                    if (json.success) {
                        if (json.sessionMessage.is_complete === 1) {
                            handling = false;
                            $submit.prop('disabled', false).html('<i class="bi-send"></i> 发送');

                            //$("#completion-session-message-answer-" + messageId).html(marked.parse(json.sessionMessage.answer));
                            $("#completion-session-message-answer-" + messageId).html(json.sessionMessage.answer);

                            let scrollTo = $messages.offset().top + $messages.height() - $(window).height() + 20;
                            $('html,body').animate({scrollTop:scrollTo},100);

                            /*
                            let $answer = $("#completion-session-message-answer-" + messageId);
                            let pos = 0;
                            let length = json.sessionMessage.answer.length;
                            let typeTimer = setInterval(function () {
                                pos++;
                                if (pos < length) {
                                    $answer.html(json.sessionMessage.answer.substring(0, pos));
                                } else {
                                    // 打字效果
                                    let html = "";
                                    html += json.sessionMessage.answer;
                                    html += '<span class="be-c-font-6">（' + json.sessionMessage.create_time + '）</span>';
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
        $(".completion-session-message-answer").each(function () {
            $(this).html(marked.parse($(this).html()));
        });
       */
    </script>
</be-page-content>