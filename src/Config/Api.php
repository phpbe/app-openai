<?php
namespace Be\App\Openai\Config;

/**
 * @BeConfig("API接口")
 */
class Api
{

    /**
     * @BeConfigItem("网址", driver="FormItemInput", description="OpenAI官方API网址（https://api.openai.com），或代理网址")
     */
    public string $url = 'https://api.openai.com';

    /**
     * @BeConfigItem("API key", driver="FormItemInput")
     */
    public string $apiKey = '';

    /**
     * @BeConfigItem("调用失败重试次数", driver="FormItemInputNumberInt")
     */
    public int $times = 5;


}

