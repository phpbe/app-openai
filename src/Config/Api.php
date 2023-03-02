<?php
namespace Be\App\Openai\Config;

/**
 * @BeConfig("API接口")
 */
class Api
{

    /**
     * @BeConfigItem("网址", driver="FormItemInput")
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

