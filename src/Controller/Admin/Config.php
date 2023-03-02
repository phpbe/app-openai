<?php

namespace Be\App\Openai\Controller\Admin;

use Be\App\System\Controller\Admin\Auth;
use Be\Be;

/**
 * @BeMenuGroup("控制台", icon="el-icon-monitor", ordering="9")
 * @BePermissionGroup("控制台")
 */
class Config extends Auth
{

    /**
     * @BeMenu("参数", icon="el-icon-setting", ordering="9.1")
     * @BePermission("参数", ordering="9.1")
     */
    public function dashboard()
    {
        Be::getAdminPlugin('Config')->setting(['appName' => 'Openai', 'title' => '参数'])->execute();
    }


}