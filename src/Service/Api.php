<?php

namespace Be\App\Openai\Service;

use Be\App\ServiceException;
use Be\Be;

class Api
{

    /**
     * 聊天应答
     */
    public function chatCompletion(array $messages = [], array $options = [])
    {
        $configApi = Be::getConfig('App.Openai.Api');

        if ($configApi->interval > 0) {
            $t = time();
            $cache = Be::getCache();
            $lastCallTimeKey = 'Openai:Api:lastCallTime';
            $lastCallTime = $cache->get($lastCallTimeKey);
            if ($lastCallTime) {
                $lastCallTime = (int) $lastCallTime;
                $interval = $t - $lastCallTime;
                if ($interval < $configApi->interval) {
                    $sleepTime = $configApi->interval - $interval;
                    if (Be::getRuntime()->isSwooleMode()) {
                        \Swoole\Coroutine::sleep($sleepTime);
                    } else {
                        sleep($sleepTime);
                    }
                }
            }
            $cache->set($lastCallTimeKey, $t);
        }

        $url = $configApi->url . '/v1/chat/completions';

        if (isset($configApi->chatCompletionModel)) {
            $model = $configApi->chatCompletionModel;
        } else {
            $model = 'gpt-3.5-turbo';
        }

        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => 2048,
        ];

        foreach ([
                     'model',
                     'temperature',
                     'top_p',
                     'n',
                     'stream',
                     'stop',
                     'max_tokens',
                     'presence_penalty',
                     'frequency_penalty',
                     'logit_bias',
                     'user',
                 ] as $key) {
            if (isset($options[$key])) {
                $data[$key] = $options[$key];
            }
        }

        $headers = ['Authorization: Bearer ' . $configApi->apiKey];

        //file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($url, true) . "\n", FILE_APPEND);
        //file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($data, true) . "\n", FILE_APPEND);
        //file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($headers, true) . "\n", FILE_APPEND);

        $responseStr = \Be\Util\Net\Curl::postJson($url, $data, $headers, [CURLOPT_TIMEOUT => 180]);

        //file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($responseStr, true) . "\n\n\n", FILE_APPEND);

        $response = json_decode($responseStr, true);
        if (!$response || !is_array($response)) {
            throw new ServiceException('调用 OpenAi 聊天应答（/v1/chat/completions）接口未返回有效JSON数据');
        }

        if (isset($response['error'])) {
            $message = '调用 OpenAi 聊天应答（/v1/chat/completions）接口出错';
            if (isset($response['error']['message'])) {
                $message .= '：' . $response['error']['message'];
            } else {
                $message .= '！';
            }
            throw new ServiceException($message);
        }

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new ServiceException('调用 OpenAi 聊天应答（/v1/chat/completions）接口无返回有效数据');
        }

        $answer = $response['choices'][0]['message']['content'];
        $answer = trim($answer);

        if (!$answer) {
            $answer = '...';
        }

        return $answer;
    }


    /**
     * 图像生成
     */
    public function imageGeneration(string $prompt, array $options = [])
    {
        $configApi = Be::getConfig('App.Openai.Api');

        if ($configApi->interval > 0) {
            $t = time();
            $cache = Be::getCache();
            $lastCallTimeKey = 'Openai:Api:lastCallTime';
            $lastCallTime = $cache->get($lastCallTimeKey);
            if ($lastCallTime) {
                $lastCallTime = (int) $lastCallTime;
                $interval = $t - $lastCallTime;
                if ($interval < $configApi->interval) {
                    $sleepTime = $configApi->interval - $interval;
                    if (Be::getRuntime()->isSwooleMode()) {
                        \Swoole\Coroutine::sleep($sleepTime);
                    } else {
                        sleep($sleepTime);
                    }
                }
            }
            $cache->set($lastCallTimeKey, $t);
        }

        $url = $configApi->url . '/v1/images/generations';

        $data = [
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
        ];

        foreach ([
                     'size',
                     'n',
                 ] as $key) {
            if (isset($options[$key])) {
                $data[$key] = $options[$key];
            }
        }

        $headers = ['Authorization: Bearer ' . $configApi->apiKey];

        //file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($url, true) . "\n", FILE_APPEND);
        //file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($data, true) . "\n", FILE_APPEND);
        //file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($headers, true) . "\n", FILE_APPEND);

        $responseStr = \Be\Util\Net\Curl::postJson($url, $data, $headers, [CURLOPT_TIMEOUT => 120]);

        //file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($responseStr, true) . "\n\n\n", FILE_APPEND);

        $response = json_decode($responseStr, true);
        if (!$response || !is_array($response)) {
            throw new ServiceException('调用 OpenAi 图像生成（/v1/images/generations）接口未返回有效JSON数据');
        }

        if (isset($response['error'])) {
            $message = '调用 OpenAi 图像生成（/v1/images/generations）接口出错';
            if (isset($response['error']['message'])) {
                $message .= '：' . $response['error']['message'];
            } else {
                $message .= '！';
            }
            throw new ServiceException($message);
        }

        if (!isset($response['data'][0]['url'])) {
            throw new ServiceException('调用 OpenAi 图像生成（/v1/images/generations）接口未返回有效数据');
        }

        $url = $response['data'][0]['url'];
        $url = trim($url);

        return $url;
    }



}
