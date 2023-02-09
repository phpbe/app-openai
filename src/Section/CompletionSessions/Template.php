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

        $serviceCompletion = Be::getService('App.Openai.Completion');
        $result = $serviceCompletion->getSessions([
            'is_complete' => 1,
            'pageSize' => $this->config->quantity
        ]);

        if ($result['total'] === 0) {
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

        $isMobile = \Be\Be::getRequest()->isMobile();
        foreach ($result['rows'] as $session) {
            echo '<div class="be-py-20">';
            echo '<a class="be-d-block be-t-ellipsis" href="' . beUrl('Openai.Completion.detail', ['id' => $session->id]) . '" title="' . $session->title . '"';
            if (!$isMobile) {
                echo ' target="_blank"';
            }
            echo '>';
            echo $session->title;
            echo '</a>';
            echo '</div>';
        }

        if (isset($section->config->more) && $section->config->more !== '') {
            echo '<div class="be-mt-100 be-bt-eee be-pt-100 be-ta-right">';
            echo '<a href="' . $moreLink . '"';
            if (!$isMobile) {
                echo ' target="_blank"';
            }
            echo '>' . $section->config->more . '</a>';
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

