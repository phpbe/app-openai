<?php

namespace Be\App\Openai\Service;

use Be\App\ServiceException;
use Be\Be;

class Api
{


    /**
     * 应用列表
     */
    public function completion(string $prompt, array $options = [])
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

        $responseStr = \Be\Util\Net\Curl::postJson($url, $data, $headers, [CURLOPT_TIMEOUT => 300]);

        file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($data, true) . "\n", FILE_APPEND);
        file_put_contents(Be::getRuntime()->getRootPath() . '/api.txt', print_r($responseStr, true) . "\n\n\n", FILE_APPEND);

        $response = json_decode($responseStr, true);
        if (!$response || !is_array($response)) {
            throw new ServiceException('调用 OpenAi completions 接口未返回有效JSON数据');
        }

        if (!isset($response['choices'][0]['text'])) {
            throw new ServiceException('调用 OpenAi completions 接口无返回有效数据');
        }

        $answer = $response['choices'][0]['text'];
        $answer = trim($answer);

        if (!$answer) {
            $answer = '...';
        }

        return $answer;
    }

}
