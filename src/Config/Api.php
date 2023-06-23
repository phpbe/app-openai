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
     * @BeConfigItem("调用时间最小间隔（秒）", description="防止调用超限，免费账号最低间隔20秒，小于等于0时不限制", driver="FormItemInputNumberInt")
     */
    public int $interval = 30;

    /**
     * @BeConfigItem("调用失败重试次数", driver="FormItemInputNumberInt")
     */
    public int $times = 5;

    /**
     * @BeConfigItem("聊天应签 - 模型", driver="FormItemSelect", values="return ['gpt-3.5-turbo','gpt-3.5-turbo-0301', 'gpt-4', 'gpt-4-0314', 'gpt-4-32k', 'gpt-4-32k-0314']")
     */
    public string $chatCompletionModel = 'gpt-3.5-turbo';

    /**
     * @BeConfigItem("聊天应签 - 缓存时间（秒）", description="启用扣，调用结果将缓存起来，相同内容再次调用时，将直接从缓存中返回结果，小于等于0时无缓存", driver="FormItemInputNumberInt")
     */
    public int $chatCompletionCache = 2592000;

    /**
     * @BeConfigItem("图像生成 - 缓存时间（秒）", description="启用扣，调用结果将缓存起来，相同内容再次调用时，将直接从缓存中返回结果，小于等于0时无缓存", driver="FormItemInputNumberInt")
     */
    public int $imageGenerationCache = 86400;


}

