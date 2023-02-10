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

        echo '<div class="completion-sessions">';
        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '<div class="be-container">';
        }

        echo $this->page->tag0('be-section-content');
        $serviceCompletion = Be::getService('App.Openai.Completion');
        $result = $serviceCompletion->getSessions([
            'is_complete' => 1,
            'pageSize' => $this->config->quantity
        ]);

        if ($result['total'] > 0) {
            $i = 0;
            foreach ($result['rows'] as $session) {

                if ($i === 0) {
                    echo '<div class="be-mb-50">';
                } else {
                    echo '<div class="be-my-50 be-bt-eee be-pt-50">';
                }

                echo '<div class="be-row">';
                echo '<div class="be-col">';
                echo '<a class="be-d-block be-t-ellipsis-2" href="' . beUrl('Openai.Completion.session', ['session_id' => $session->id]) . '" title="' . $session->name . '">';
                echo $session->name;
                echo '</a>';
                echo '</div>';
                echo '<div class="be-col-auto be-c-font-6">';
                echo $session->create_time;
                echo '</div>';
                echo '</div>';

                echo '</div>';
                $i++;
            }
        } else {
            echo '<div class="be-py-20 be-c-font-6">暂无记录！</div>';
        }

        $total = $result['total'];
        $pageSize = $result['pageSize'];
        $pages = ceil($total / $pageSize);
        if ($pages > 1) {
            $page = $result['page'];
            if ($page > $pages) $page = $pages;

            $html = '<nav class="be-mt-300">';
            $html .= '<ul class="be-pagination" style="justify-content: center;">';
            $html .= '<li>';
            if ($page > 1) {
                $url = beUrl('Openai.Completion.sessions', ['page' => ($page - 1)]);
                $html .= '<a href="' . $url . '">' . beLang('App.Openai', 'PAGINATION.PREVIOUS') . '</a>';
            } else {
                $html .= '<span>' . beLang('App.Openai', 'PAGINATION.PREVIOUS') . '</span>';
            }
            $html .= '</li>';

            $from = null;
            $to = null;
            if ($pages < 9) {
                $from = 1;
                $to = $pages;
            } else {
                $from = $page - 4;
                if ($from < 1) {
                    $from = 1;
                }

                $to = $from + 8;
                if ($to > $pages) {
                    $to = $pages;
                }
            }

            if ($from > 1) {
                $html .= '<li><span>...</span></li>';
            }

            for ($i = $from; $i <= $to; $i++) {
                if ($i == $page) {
                    $html .= '<li class="active">';
                    $html .= '<span>' . $i . '</span>';
                    $html .= '</li>';
                } else {
                    $url = beUrl('Openai.Completion.sessions', ['page' => $i]);
                    $html .= '<li>';
                    $html .= '<a href="' . $url . '">' . $i . '</a>';
                    $html .= '</li>';
                }
            }

            if ($to < $pages) {
                $html .= '<li><span>...</span></li>';
            }

            $html .= '<li>';
            if ($page < $pages) {
                $url = beUrl('Openai.Completion.sessions', ['page' => ($page + 1)]);
                $html .= '<a href="' . $url . '">' . beLang('App.Openai', 'PAGINATION.NEXT') . '</a>';
            } else {
                $html .= '<span>' . beLang('App.Openai', 'PAGINATION.NEXT') . '</span>';
            }
            $html .= '</li>';
            $html .= '</ul>';
            $html .= '</nav>';

            echo $html;
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
        echo $this->getCssPadding('completion-sessions');
        echo $this->getCssMargin('completion-sessions');
        echo $this->getCssBackgroundColor('completion-sessions');
        echo '</style>';
    }
    

}

