<?php

namespace Be\App\Openai\Section\CompletionSessions;

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

        echo '<div class="chat-gpt">';
        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '<div class="be-container">';
        }

        if ($this->config->title !== '') {
            echo $this->page->tag0('be-section-title');
            echo $this->config->title;
            echo $this->page->tag1('be-section-title');
        }

        echo $this->page->tag0('be-section-content');


        $serviceCompletion = Be::getService('App.Openai.Completion');
        $result = $serviceCompletion->getSessions([
            'is_complete' => 1,
            'pageSize' => $this->config->quantity
        ]);

        if ($result['total'] > 0) {
            foreach ($result['rows'] as $session) {
                echo '<div class="be-py-20">';
                echo '<a class="be-d-block be-t-ellipsis" href="' . beUrl('Openai.Completion.detail', ['id' => $session->id]) . '" title="' . $session->title . '">';
                echo $session->title;
                echo '</a>';
                echo '</div>';
            }
        } else {
            echo '<div class="be-py-20 be-c-font-6">暂无记录！</div>';
        }

        if (isset($section->config->more) && $section->config->more !== '') {
            echo '<div class="be-mt-100 be-bt-eee be-pt-100 be-ta-right">';
            echo '<a href="' . beUrl('Openai.Completion.sessions') . '">' . $section->config->more . '</a>';
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
        echo $this->getCssPadding('chat-gpt');
        echo $this->getCssMargin('chat-gpt');
        echo $this->getCssBackgroundColor('chat-gpt');
        echo '</style>';
    }


    private function js()
    {
        echo '<script type="text/javascript">';
        echo '$(function () {';

        echo '});';
        echo '</script>';
    }


}

