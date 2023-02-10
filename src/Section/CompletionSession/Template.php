<?php

namespace Be\App\Openai\Section\CompletionSession;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['middle', 'center'];


    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }
        $this->css();

        $session = false;
        $sessionId = \Be\Be::getRequest()->get('session_id', '');
        if ($sessionId !== '') {
            $serviceCompletion = Be::getService('App.Openai.Completion');
            $session = $serviceCompletion->getSession($sessionId);
        }

        echo '<div class="completion-session">';
        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '<div class="be-container">';
        }

        echo $this->page->tag0('be-section-title');
        echo '<div class="be-row">';
        echo '<div class="be-col">';
        echo $this->config->title;
        echo '</div>';
        echo '<div class="be-col-auto">';
        if ($session === false) {
            echo '<button type="submit" class="be-btn be-btn-major" id="completion-session-new"><i class="bi-plus"></i> 发起新会话</button>';
        } else {
            echo '<a href="' . beUrl('Openai.Completion.session') . '" class="be-btn be-btn-major"><i class="bi-plus"></i> 发起新会话</a>';
        }
        echo '</div>';
        echo '</div>';
        echo $this->page->tag1('be-section-title');


        echo $this->page->tag0('be-section-content');

        echo '<div class="completion-session-messages" id="completion-session-messages">';
        if ($session !== false) {
            foreach ($session->messages as $message) {
                echo '<div class="be-row">';
                echo '<div class="be-col-auto">';
                echo '<span class="be-c-major be-fw-bold">我：</span>';
                echo '</div>';
                echo '<div class="be-col be-c-major">';
                echo $message->question;
                echo '<span class="be-c-major-6">（' . $message->create_time . '）</span>';
                echo '</div>';
                echo '</div>';

                echo '<div class="be-row be-mt-50 be-mb-200">';
                echo '<div class="be-col-auto">';
                echo '<span class="be-fw-bold">ChatGPT：</span>';
                echo '</div>';
                echo '<div class="be-col completion-session-message-answer">';
                echo $message->answer;
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="be-row be-mt-50 be-mb-200">';
            echo '<div class="be-col-auto">';
            echo '<span class="be-fw-bold">ChatGPT：</span>';
            echo '</div>';
            echo '<div class="be-col">';
            echo '我是来自 OpenAI 实验室的 ChatGPT，有什么问题尽管问我吧。';
            echo '<span class="be-c-font-6">（' . date('Y-m-d H:i:s') . '）</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        if ($session === false) {
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
        }

        echo $this->page->tag1('be-section-content');
        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '</div>';
        }
        echo '</div>';

        $this->js();
    }


    private function css()
    {
        echo '<style type="text/css">';
        echo $this->getCssPadding('completion-session');
        echo $this->getCssMargin('completion-session');
        echo $this->getCssBackgroundColor('completion-session');

        echo '#' . $this->id . ' .completion-session-messages {';
        echo 'min-height: 400px;';
        echo 'height: 60vh;';
        //echo 'padding: .5rem;';
        //echo 'border: var(--font-color-9) 1px solid;';
        echo 'overflow-y: auto;';
        echo '}';

        echo '#' . $this->id . ' .completion-session-message-answer p {';
        echo 'margin: 0;';
        echo '}';

        echo '</style>';
    }


    private function js()
    {
        ?>
        <!--script src="<?PHP echo \Be\Be::getProperty('App.Openai')->getWwwUrl(); ?>/lib/marked/marked.min.js"></script-->
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
                    url: "<?php echo beUrl('Openai.Completion.send'); ?>",
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
                            html += '<span class="be-c-major be-fw-bold">我：</span>';
                            html += '</div>';
                            html += '<div class="be-col be-c-major">';
                            html += json.session.latestMessage.question;
                            html += '<span class="be-c-major-6">（' + json.session.latestMessage.create_time + '）</span>';
                            html += '</div>';
                            html += '</div>';

                            html += '<div class="be-row be-mt-50 be-mb-200">';
                            html += '<div class="be-col-auto">';
                            html += '<span class="be-fw-bold">ChatGPT：</span>';
                            html += '</div>';
                            html += '<div class="be-col completion-session-message-answer" id="completion-session-message-answer-' + json.session.latestMessage.id + '">';
                            html += '处理中.';
                            html += '</div>';
                            html += '</div>';

                            $messages.append(html);

                            let scrollHeight = $messages.prop("scrollHeight");
                            $messages.animate({scrollTop: scrollHeight}, 100);

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
                    url: "<?php echo beUrl('Openai.Completion.close'); ?>",
                    data: {
                        session_id: sessionId,
                    },
                    method: "POST",
                    success: function (json) {
                        if (json.success) {
                            window.location.href = "<?php echo beUrl('Openai.Completion.session'); ?>";
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
                    url: "<?php echo beUrl('Openai.Completion.receive'); ?>",
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

                                let messagesCcrollHeight0 = $messages.prop("scrollHeight");
                                $messages.animate({scrollTop: messagesCcrollHeight0}, 100);

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
        <?php
    }


}

