<?php

namespace Be\App\Openai\Controller;

use Be\Be;

class Completion extends Base
{

    /**
     * ChatGPT
     *
     * @BeMenu("ChatGPT")
     * @BeRoute("/openai/chatgpt")
     */
    public function session()
    {
        $config = Be::getConfig('App.Openai.Auth');
        if ($config->enable === 1 && $config->scope === 'api') {
            $this->auth();
        }

        $request = Be::getRequest();
        $response = Be::getResponse();

        $pageConfig = $response->getPageConfig();
        $response->set('pageConfig', $pageConfig);

        $response->set('title', $pageConfig->title ?: '');
        $response->set('metaDescription', $pageConfig->metaDescription ?: '');
        $response->set('metaKeywords', $pageConfig->metaKeywords ?: '');
        $response->set('pageTitle', $pageConfig->pageTitle ?: ($pageConfig->title ?: ''));

        $response->display();
    }


    /**
     * ChatGPT 历史会话
     *
     * @BeMenu("ChatGPT 历史会话")
     * @BeRoute("/openai/chatgpt/history")
     */
    public function sessions()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $pageConfig = $response->getPageConfig();
        $response->set('pageConfig', $pageConfig);

        $response->set('title', $pageConfig->title ?: '');
        $response->set('metaDescription', $pageConfig->metaDescription ?: '');
        $response->set('metaKeywords', $pageConfig->metaKeywords ?: '');
        $response->set('pageTitle', $pageConfig->pageTitle ?: ($pageConfig->title ?: ''));

        $response->display();
    }


}
