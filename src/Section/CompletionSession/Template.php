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

        echo '<div>112</div>';



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

