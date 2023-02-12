<?php

namespace Be\App\Openai\Controller\Admin;

use Be\AdminPlugin\Detail\Item\DetailItemSwitch;
use Be\AdminPlugin\Detail\Item\DetailItemToggleIcon;
use Be\AdminPlugin\Form\Item\FormItemSelect;
use Be\AdminPlugin\Table\Item\TableItemLink;
use Be\AdminPlugin\Table\Item\TableItemSelection;
use Be\AdminPlugin\Table\Item\TableItemToggleIcon;
use Be\App\System\Controller\Admin\Auth;
use Be\Be;

/**
 * 存储管理器
 *
 * @BeMenuGroup("文本应签", icon = "bi-chat-text", ordering="1")
 * @BePermissionGroup("文本应签")
 */
class Completion extends Auth
{

    /**
     * @BeMenu("会话", icon = "bi-chat", ordering="1.1")
     * @BePermission("会话", ordering="1.1")
     */
    public function session()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $response->set('title', '与 ChatGPT 对话');
        $response->display();
    }

    /**
     * @BePermission("会话")
     */
    public function pop()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $pageConfig = $response->getPageConfig();
        $response->set('pageConfig', $pageConfig);

        $response->set('title', $pageConfig->title ?: '');
        $response->set('metaDescription', $pageConfig->metaDescription ?: '');
        $response->set('metaKeywords', $pageConfig->metaKeywords ?: '');
        $response->set('pageTitle', $pageConfig->pageTitle ?: ($pageConfig->title ?: ''));

        $response->display();
    }

    /**
     * 会话-发送
     *
     * @BePermission("会话")
     */
    public function send()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $question = $request->post('question', '');
            $sessionId = $request->post('session_id', '');
            $serviceCompletion = Be::getService('App.Openai.Completion');
            $session = $serviceCompletion->send($question, $sessionId);

            $response->set('success', true);
            $response->set('message', '提交成功！');
            $response->set('session', $session);
            $response->json();
        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

    /**
     * 会话-接收
     *
     * @BePermission("会话")
     */
    public function receive()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $sessionId = $request->post('session_id', '');
            $messageId = $request->post('message_id', '');
            $serviceCompletion = Be::getService('App.Openai.Completion');
            $sessionMessage = $serviceCompletion->waitSessionMessage($sessionId, $messageId);

            $response->set('success', true);
            $response->set('message', '获取成功！');
            $response->set('sessionMessage', $sessionMessage);
            $response->json();

        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

    /**
     * 会话-关闭
     *
     * @BePermission("会话")
     */
    public function close()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $sessionId = $request->post('session_id', '');
            $serviceCompletion = Be::getService('App.Openai.Completion');
            $serviceCompletion->close($sessionId);

            $response->set('success', true);
            $response->set('message', '关闭会话成功！');
            $response->json();
        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }


    /**
     * @BeMenu("会话记录", icon = "bi-list", ordering="1.2")
     * @BePermission("会话记录", ordering="1.2")
     */
    public function sessions()
    {
        Be::getAdminPlugin('Curd')->setting([
            'label' => '会话记录',
            'table' => 'openai_completion_session',
            'grid' => [
                'title' => '会话记录',
                'orderBy' => 'create_time',
                'orderByDir' => 'DESC',
                'form' => [
                    'items' => [
                        [
                            'name' => 'name',
                            'label' => '名称',
                        ],
                        [
                            'name' => 'is_complete',
                            'label' => '是否关闭',
                            'driver' => FormItemSelect::class,
                            'keyValues' => [
                                '1' => '关闭',
                                '0' => '未关闭',
                            ]
                        ],
                    ],
                ],

                'titleRightToolbar' => [
                    'items' => [
                        [
                            'label' => '新增会话',
                            'action' => 'session',
                            'target' => 'self', // 'ajax - ajax请求 / dialog - 对话框窗口 / drawer - 抽屉 / self - 当前页面 / blank - 新页面'
                            'ui' => [
                                'icon' => 'el-icon-plus',
                                'type' => 'primary',
                            ],
                        ],
                    ],
                ],

                'tableToolbar' => [
                    'items' => [
                        [
                            'label' => '批量删除',
                            'action' => 'delete',
                            'target' => 'ajax',
                            'confirm' => '确认要删除么？',
                            'ui' => [
                                'icon' => 'el-icon-delete',
                                'type' => 'danger'
                            ],
                        ],
                    ],
                ],

                'table' => [

                    // 未指定时取表的所有字段
                    'items' => [
                        [
                            'driver' => TableItemSelection::class,
                            'width' => '50',
                        ],
                        [
                            'name' => 'name',
                            'label' => '名称',
                            'align' => 'left',
                            'driver' => TableItemLink::class,
                            'action' => 'messages',
                            'target' => 'drawer',
                            'drawer' => [
                                'width' => '75%',
                            ],
                        ],
                        [
                            'name' => 'lines',
                            'label' => '行数',
                            'width' => '90',
                        ],
                        [
                            'name' => 'is_complete',
                            'label' => '是否关闭',
                            'driver' => TableItemToggleIcon::class,
                            'width' => '120',
                        ],
                        [
                            'name' => 'create_time',
                            'label' => '创建时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                        [
                            'name' => 'update_time',
                            'label' => '更新时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                    ],
                    'operation' => [
                        'label' => '操作',
                        'width' => '120',
                        'items' => [
                            [
                                'label' => '删除',
                                'action' => 'delete',
                                'confirm' => '确认要删除么？',
                                'target' => 'ajax',
                                'ui' => [
                                    'type' => 'danger'
                                ]
                            ],
                        ]
                    ],
                ],
            ],
        ])->execute();
    }

    /**
     * @BePermission("会话记录")
     */
    public function messages()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $postData = $request->post('data', '', '');
        if ($postData) {
            $postData = json_decode($postData, true);
            if (isset($postData['row']['id']) && $postData['row']['id']) {
                $sessionId = $postData['row']['id'];
                $session = Be::getService('App.Openai.Completion')->getSession($sessionId);
                $response->set('session', $session);

                $response->set('title', $session->name);
                $response->display(null, 'Blank');
            }
        }
    }

}
