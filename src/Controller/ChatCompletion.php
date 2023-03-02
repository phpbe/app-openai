<?php

namespace Be\App\Openai\Controller;

use Be\App\ControllerException;
use Be\Be;

class ChatCompletion extends Base
{

    /**
     * ChatGPT
     *
     * @BeMenu("ChatGPT 提问应答")
     * @BeRoute("/openai/chatgpt")
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
     * @BeRoute("/openai/chatgpt/send")
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
            $chatCompletionId = $request->post('chat_completion_id', '');
            $serviceChatCompletion = Be::getService('App.Openai.ChatCompletion');

            if ($chatCompletionId !== '') {
                $chatCompletion = $serviceChatCompletion->get($chatCompletionId);
                if ($chatCompletion->lines >= 5) {
                    throw new ControllerException('一次会话中，最多允许5个回合的应答，请发起新会话。');
                }
            }

            $chatCompletion = $serviceChatCompletion->send($prompt, $chatCompletionId);

            $response->set('success', true);
            $response->set('message', '提交成功！');
            $response->set('chatCompletion',$chatCompletion);
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
     * @BeRoute("/openai/chatgpt/receive")
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

            $chatCompletionId = $request->post('chat_completion_id', '');
            $chatCompletionMessageId = $request->post('chat_completion_message_id', '');

            $serviceChatCompletion = Be::getService('App.Openai.ChatCompletion');
            $chatCompletionMessage = $serviceChatCompletion->waitMessage($chatCompletionId, $chatCompletionMessageId);

            $response->set('success', true);
            $response->set('message', '获取成功！');
            $response->set('chatCompletionMessage', $chatCompletionMessage);
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
     * @BeMenu("ChatGPT 提问应答历史记录")
     * @BeRoute("/openai/chatgpt/history")
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
