<?php

namespace Be\App\Openai\Controller\Admin;

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
class TextCompletion extends Auth
{

    /**
     * @BeMenu("会话", icon = "bi-chat", ordering="1.1")
     * @BePermission("会话", ordering="1.1")
     */
    public function index()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();
        $session = Be::getSession();

        $textCompletionId = $request->get('text_completion_id', '');
        if ($textCompletionId === '') {
            $textCompletionId = $session->get('app:openai:admin:currentTextCompletionId', '');
        }

        $textCompletion = false;
        if ($textCompletionId !== '' && $textCompletionId !== 'new') {
            try {
                $serviceTextCompletion = Be::getService('App.Openai.TextCompletion');
                $textCompletion = $serviceTextCompletion->get($textCompletionId);
            } catch (\Throwable $t) {
            }
        }

        if ($textCompletion || $textCompletionId === 'new') {
            $session->set('app:openai:admin:currentTextCompletionId', $textCompletionId);
        }

        $response->set('textCompletion', $textCompletion);

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
        $session = Be::getSession();

        $textCompletionId = $request->get('text_completion_id', '');
        if ($textCompletionId === '') {
            $textCompletionId = $session->get('app:openai:admin:currentTextCompletionId', '');
        }

        $textCompletion = false;
        if ($textCompletionId !== '' && $textCompletionId !== 'new') {
            try {
                $serviceTextCompletion = Be::getService('App.Openai.TextCompletion');
                $textCompletion = $serviceTextCompletion->get($textCompletionId);
            } catch (\Throwable $t) {
            }
        }

        if ($textCompletion || $textCompletionId === 'new') {
            $session->set('app:openai:admin:currentTextCompletionId', $textCompletionId);
        }

        $response->set('textCompletion', $textCompletion);

        $response->set('title', '与 ChatGPT 对话');
        $response->display(null, 'Blank');
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
            $prompt = $request->post('prompt', '');
            $textCompletionId = $request->post('text_completion_id', '');
            $serviceTextCompletion = Be::getService('App.Openai.TextCompletion');
            $textCompletion = $serviceTextCompletion->send($prompt, $textCompletionId);

            $session = Be::getSession();
            $session->set('app:openai:admin:currentTextCompletionId', $textCompletion->id);

            $response->set('success', true);
            $response->set('message', '提交成功！');
            $response->set('textCompletion', $textCompletion);
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
            $textCompletionId = $request->post('text_completion_id', '');
            $textCompletionMessageId = $request->post('text_completion_message_id', '');
            $serviceTextCompletion = Be::getService('App.Openai.TextCompletion');
            $textCompletionMessage = $serviceTextCompletion->waitMessage($textCompletionId, $textCompletionMessageId);

            $response->set('success', true);
            $response->set('message', '获取成功！');
            $response->set('textCompletionMessage', $textCompletionMessage);
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
    public function history()
    {
        Be::getAdminPlugin('Curd')->setting([
            'label' => '会话记录',
            'table' => 'openai_text_completion',
            'grid' => [
                'title' => '会话记录',
                'orderBy' => 'create_time',
                'orderByDir' => 'DESC',
                'form' => [
                    'items' => [
                        [
                            'name' => 'prompt',
                            'label' => '提问',
                        ],
                    ],
                ],

                'titleRightToolbar' => [
                    'items' => [
                        [
                            'label' => '新增会话',
                            'url' => beAdminUrl('Openai.TextCompletion.index', ['text_completion_id' => 'new']),
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
                            'confirm' => '此操作将从数据库彻底删除，确认要执行么？',
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
                            'name' => 'prompt',
                            'label' => '提问',
                            'align' => 'left',
                            'driver' => TableItemLink::class,
                            'action' => 'detail',
                            'target' => 'self',
                        ],
                        [
                            'name' => 'lines',
                            'label' => '行数',
                            'width' => '90',
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
                                'confirm' => '此操作将从数据库彻底删除，确认要执行么？',
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
    public function detail()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $postData = $request->post('data', '', '');
        if ($postData) {
            $postData = json_decode($postData, true);
            if (isset($postData['row']['id']) && $postData['row']['id']) {
                $response->redirect(beAdminUrl('Openai.TextCompletion.index', ['text_completion_id' => $postData['row']['id']]));
            }
        }
    }

    /**
     * 会话记录-删除
     *
     * @BePermission("会话记录")
     */
    public function delete()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $postData = $request->json();

            $textCompletionIds = [];
            if (isset($postData['selectedRows'])) {
                foreach ($postData['selectedRows'] as $row) {
                    $textCompletionIds[] = $row['id'];
                }
            } elseif (isset($postData['row'])) {
                $textCompletionIds[] = $postData['row']['id'];
            }

            if (count($textCompletionIds) > 0) {
                Be::getService('App.Openai.Admin.TextCompletion')->delete($textCompletionIds);
            }

            $response->set('success', true);
            $response->set('message', '删除成功！');
            $response->json();
        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

}
