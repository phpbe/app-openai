<?php

namespace Be\App\Openai\Controller;

use Be\Be;

/**
 * 图像生成
 */
class ImageGeneration extends Base
{

    /**
     * 生成图像
     *
     * @BeMenu("ChatGPT 生成图像")
     * @BeRoute("/openai/image/generation")
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
     * @BeRoute("/openai/image/generation/send")
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

            $serviceImageGeneration = Be::getService('App.Openai.ImageGeneration');
            $imageGeneration = $serviceImageGeneration->send($request->post());

            $response->set('success', true);
            $response->set('message', '提交成功！');
            $response->set('imageGeneration',$imageGeneration);
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
     * @BeRoute("/openai/image/generation/receive")
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

            $imageGenerationId = $request->post('image_generation_id', '');
            $serviceImageGeneration = Be::getService('App.Openai.ImageGeneration');
            $imageGeneration = $serviceImageGeneration->wait($imageGenerationId);

            $response->set('success', true);
            $response->set('message', '获取成功！');
            $response->set('imageGeneration', $imageGeneration);
            $response->json();

        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }


}
