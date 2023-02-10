<?php

namespace Be\App\Openai\Controller;

use Be\Be;

class Auth
{

    /**
     * 登录
     *
     * @BeMenu("登录")
     * @BeRoute("/openai/login")
     */
    public function login()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        if ($request->isPost()) {
            $password = $request->post('password');
            $config = Be::getConfig('App.Openai.Auth');
            if ($config->password === $password) {
                $response->cookie('Openai:Password', md5('Openai:Password:' . $config->password), time() + 86400 * 180, '/', $request->getDomain(), false, true);
                //$response->success( '登录成功！', ['url' => beUrl('Openai.Completion.session')]);
                $response->redirect(beUrl('Openai.Completion.session'));
            } else {
                $response->error('密码错误！', ['url' => beUrl('Openai.Auth.login')]);
            }
        } else {
            $response->set('title', '登录');
            $response->display();
        }
    }


}
