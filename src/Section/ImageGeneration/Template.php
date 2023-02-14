<?php

namespace Be\App\Openai\Section\ImageGeneration;

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

        echo '<div class="image-generation">';
        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '<div class="be-container">';
        }

        echo $this->page->tag0('be-section-title');
        echo $this->config->title;
        echo $this->page->tag1('be-section-title');


        echo $this->page->tag0('be-section-content');
        ?>
        <form id="form-image-generation">
            <div class="be-row">
                <div class="be-col-24 be-md-col">
                    <div class="be-mt-50">
                        尽可能详细描述下您需要生成的图像：
                    </div>

                    <div class="be-mt-50">
                        <textarea name="prompt" class=" be-textarea" style="width: 100%; height: 120px;" id="image-generation-prompt" placeholder="请输入您的需求，按回车发送"></textarea>
                    </div>
                </div>

                <div class="be-col-24 be-md-col-auto">
                    <div class="be-pl-200 be-mt-150"></div>
                </div>
                <div class="be-col-24 be-md-col-auto">
                    <div class="be-mt-50">
                        图像大小：
                    </div>

                    <div class="be-mt-50">
                        <div class="be-mt-50">
                            <input type="radio" class="be-radio" name="size" value="256x256" id="size-256"> <label for="size-256">256 x 256</label>
                        </div>

                        <div class="be-mt-50">
                            <input type="radio" class="be-radio" name="size" value="512x512" id="size-512" checked> <label for="size-512">512 x 512</label>
                        </div>

                        <div class="be-mt-50">
                            <input type="radio" class="be-radio" name="size" value="1024x1024" id="size-1024"> <label for="size-1024">1024 x 1024</label>
                        </div>
                    </div>

                </div>
            </div>

            <div class="be-mt-100">
                <button type="submit" class="be-btn be-btn-major" id="image-generation-submit"><i class="bi-send"></i> 发送</button>
            </div>

        </form>

        <div class="be-mt-200 be-fs-110">
            生成结果：
        </div>

        <div class="be-mt-50 be-row">
            <div class="be-col-24 be-md-col">
                <div class="be-mt-50" id="image-generation-result"></div>
            </div>
            <div class="be-col-24 be-md-col-auto">

            </div>
        </div>
        <?php

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
        echo $this->getCssPadding('image-generation');
        echo $this->getCssMargin('image-generation');
        echo $this->getCssBackgroundColor('image-generation');

        echo '#' . $this->id . ' #image-generation-result {';
        echo 'min-height: 60vh;';
        echo '}';

        echo '</style>';
    }


    private function js()
    {
        ?>
        <script>

            let imageGenerationId = "";

            let handling = false;
            let receiving = false;

            let timer;
            let timerStartTime;

            let $result = $("#image-generation-result");
            let $prompt = $("#image-generation-prompt");
            let $submit = $("#image-generation-submit");

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
                    url: "<?php echo beUrl('Openai.ImageGeneration.send'); ?>",
                    data: $("#form-image-generation").serialize(),
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
                    url: "<?php echo beUrl('Openai.ImageGeneration.receive'); ?>",
                    data: {
                        image_generation_id: imageGenerationId,
                    },
                    method: "POST",
                    success: function (json) {
                        if (json.success) {

                            handling = false;
                            $submit.prop('disabled', false).html('<i class="bi-send"></i> 发送');

                            let html;
                            if (json.imageGeneration.url.indexOf("http://") === 0 || json.imageGeneration.url.indexOf("https://") === 0) {
                                html = '<img src="' + json.imageGeneration.url + '" alt="" style="max-width: 100%; max-height: 100%;">';
                            } else {
                                html = json.imageGeneration.url;
                            }
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
        <?php
    }

}

