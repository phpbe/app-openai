<?php

namespace Be\App\Openai\Section\ChatCompletionLatest;

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

        echo '<div class="chat-completion-latest">';
        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '<div class="be-container">';
        }

        if ($this->config->title !== '') {
            echo $this->page->tag0('be-section-title');
            echo $this->config->title;
            echo $this->page->tag1('be-section-title');
        }

        echo $this->page->tag0('be-section-content');


        $serviceChatCompletion = Be::getService('App.Openai.ChatCompletion');
        $result = $serviceChatCompletion->getHistory([
            'pageSize' => $this->config->quantity
        ]);

        if ($result['total'] > 0) {
            foreach ($result['rows'] as $chatCompletion) {
                echo '<div class="be-py-20">';
                echo '<a class="be-d-block be-t-ellipsis-2" href="' . beUrl('Openai.ChatCompletion.index', ['chat_completion_id' => $chatCompletion->id]) . '" title="' . $chatCompletion->name . '">';
                echo $chatCompletion->prompt;
                echo '</a>';
                echo '</div>';
            }
        } else {
            echo '<div class="be-py-20 be-c-font-6">暂无记录！</div>';
        }

        if (isset($section->config->more) && $section->config->more !== '') {
            echo '<div class="be-mt-100 be-bt-eee be-pt-100 be-ta-right">';
            echo '<a href="' . beUrl('Openai.ChatCompletion.history') . '">' . $section->config->more . '</a>';
            echo '</div>';
        }

        echo $this->page->tag1('be-section-content');
        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '</div>';
        }
        echo '</div>';
    }


    private function css()
    {
        echo '<style type="text/css">';
        echo $this->getCssPadding('chat-completion-latest');
        echo $this->getCssMargin('chat-completion-latest');
        echo $this->getCssBackgroundColor('chat-completion-latest');
        echo '</style>';
    }


}

