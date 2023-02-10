<?php

namespace Be\App\Openai\Controller;

use Be\App\ControllerException;
use Be\Be;

class Base
{

    public function __construct()
    {
        $config = Be::getConfig('App.Openai.Auth');
        if ($config->enable === 1 && $config->scope === 'full') {
            $this->auth();
        }
    }

    public function auth()
    {
        $config = Be::getConfig('App.Openai.Auth');
        if ($config->password !== '') {
            // 校验权限
            if (md5('Openai:Password:' . $config->password) !== Be::getRequest()->cookie('Openai:Password')) {
                $redirect = [
                    'url' => beUrl('Openai.Auth.login'),
                    //'message' => '{timeout} 秒后跳转到 <a href="{url}">登录页</a>',
                    'timeout' => 0,
                ];

                throw new ControllerException('此功能限制访问，请先登录！', 0, $redirect);
            }
        }
    }

}
