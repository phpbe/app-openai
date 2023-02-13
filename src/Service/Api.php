<?php

namespace Be\App\Openai\Service;

use Be\App\ServiceException;
use Be\Be;

class Api
{

    /**
     * 文本应签
     */
    public function textCompletion(string $prompt, array $options = [])
    {
        $url = 'https://api.openai.com/v1/completions';

        $configApi = Be::getConfig('App.Openai.Api');

        $data = [
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
            'max_tokens' => 2048,
        ];

        foreach ([
                     'model',
                     'max_tokens',
                     'frequency_penalty',
                     'presence_penalty',
                     'stop',
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
            throw new ServiceException('调用 OpenAi 文本应签（/v1/completions）接口未返回有效JSON数据');
        }

        if (!isset($response['choices'][0]['text'])) {
            throw new ServiceException('调用 OpenAi 文本应签（/v1/completions）接口无返回有效数据');
        }

        $answer = $response['choices'][0]['text'];
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
        $url = 'https://api.openai.com/v1/images/generations';

        $configApi = Be::getConfig('App.Openai.Api');

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
