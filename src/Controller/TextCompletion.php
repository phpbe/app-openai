<?php

namespace Be\App\Openai\Controller;

use Be\App\ControllerException;
use Be\Be;

class TextCompletion extends Base
{

    /**
     * ChatGPT
     *
     * @BeMenu("ChatGPT 文本应答")
     * @BeRoute("/openai/text/completion")
     */
    public function index()
    {
        $requestLogin = false;
        $config = Be::getConfig('App.Openai.Auth');
        if ($config->enable === 1 && $config->scope === 'api') {
            try {
                $this->auth();
            } catch (\Throwable $t) {
                $requestLogin = true;
            }
        }

        $request = Be::getRequest();
        $response = Be::getResponse();

        $pageConfig = $response->getPageConfig();
        $response->set('pageConfig', $pageConfig);

        $response->set('title', $pageConfig->title ?: '');
        $response->set('metaDescription', $pageConfig->metaDescription ?: '');
        $response->set('metaKeywords', $pageConfig->metaKeywords ?: '');
        $response->set('pageTitle', $pageConfig->pageTitle ?: ($pageConfig->title ?: ''));

        $response->set('requestLogin', $requestLogin);

        $response->display();
    }

    /**
     * 提问
     *
     * @BeRoute("/openai/text/completion/send")
     */
    public function send()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();
        
        try {
            $config = Be::getConfig('App.Openai.Auth');
            if ($config->enable === 1 && $config->scope === 'api') {
                $this->auth();
            }

            $prompt = $request->post('prompt', '');
            $textCompletionId = $request->post('text_completion_id', '');
            $serviceTextCompletion = Be::getService('App.Openai.TextCompletion');

            if ($textCompletionId !== '') {
                $textCompletion = $serviceTextCompletion->get($textCompletionId);
                if ($textCompletion->lines >= 5) {
                    throw new ControllerException('一次会话中，最多允许5个回合的应答，请发起新会话。');
                }
            }

            $textCompletion = $serviceTextCompletion->send($prompt, $textCompletionId);

            $response->set('success', true);
            $response->set('message', '提交成功！');
            $response->set('textCompletion',$textCompletion);
            $response->json();
        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

    /**
     * 接收
     *
     * @BeRoute("/openai/text/completion/receive")
     */
    public function receive()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $config = Be::getConfig('App.Openai.Auth');
            if ($config->enable === 1 && $config->scope === 'api') {
                $this->auth();
            }

            $textCompletionId = $request->post('text_completion_id', '');
            $textCompletionMessageId = $request->post('text_completion_message_id', '');

            $serviceTextCompletion = Be::getService('App.Openai.TextCompletion');
            $textCompletionMessage = $serviceTextCompletion->waitMessage($textCompletionId, $textCompletionMessageId);

            $response->set('success', true);
            $response->set('message', '获取成功！');
            $response->set('textCompletionMessage', $textCompletionMessage);
            $response->json();

        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

    /**
     * ChatGPT 历史会话
     *
     * @BeMenu("ChatGPT 文本应答历史记录")
     * @BeRoute("/openai/text/completion/history")
     */
    public function history()
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
