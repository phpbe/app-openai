<?php

namespace Be\App\Openai\Config\Page\ChatCompletion;


/**
 * @BeConfig("聊天应签")
 */
class index
{

    public int $west = 30;
    public int $center = 70;
    public int $east = 0;

    public array $westSections = [
        [
            'name' => 'App.Openai.ChatCompletionLatest',
        ],
    ];

    public array $centerSections = [
        [
            'name' => 'App.Openai.ChatCompletion',
        ],
    ];

    /**
     * @BeConfigItem("HEAD头标题",
     *     description="HEAD头标题，用于SEO",
     *     driver = "FormItemInput"
     * )
     */
    public string $title = '与 ChatGPT 聊天';

    /**
     * @BeConfigItem("Meta描述",
     *     description="填写页面内容的简单描述，用于SEO",
     *     driver = "FormItemInput"
     * )
     */
    public string $metaDescription = '与 ChatGPT 聊天';

    /**
     * @BeConfigItem("Meta关键词",
     *     description="填写页面内容的关键词，用于SEO",
     *     driver = "FormItemInput"
     * )
     */
    public string $metaKeywords = 'ChatGPT';

    /**
     * @BeConfigItem("页面标题",
     *     description="展示在页面内容中的标题，一般与HEAD头标题一致，两者相同时可不填写此项",
     *     driver = "FormItemInput"
     * )
     */
    public string $pageTitle = '';

}
