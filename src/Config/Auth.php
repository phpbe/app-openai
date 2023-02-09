<?php
namespace Be\App\Openai\Config;

/**
 * @BeConfig("身份验证")
 */
class Auth
{

    /**
     * @BeConfigItem("是否启用",
     *     driver = "FormItemSwitch")
     */
    public int $enable = 1;

    /**
     * @BeConfigItem("访问密码",
     *     description="留空时不需要密码",
     *     driver="FormItemInput"
     * )
     */
    public string $password = '123456';

    /**
     * @BeConfigItem("范围",
     *     driver="FormItemSelect",
     *     keyValues = "return ['full' => '全部功能', 'api' => '调用API的功能'];"
     * )
     */
    public string $scope = 'api';


}

