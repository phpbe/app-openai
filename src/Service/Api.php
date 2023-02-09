<?php

namespace Be\App\Openai\Service;

use Be\Be;

class Api
{


    /**
     * 应用列表
     */
    public function completion(string $prompt, array $options = [])
    {
        $url = 'https://api.openai.com/v1/completions';

        $configApi = Be::getConfig('Openai.Api');

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
        $response = \Be\Util\Net\Curl::postJson($url, $data, $headers);
        print_r($response);
    }

}
