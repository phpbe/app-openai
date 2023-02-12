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
 * @BeMenuGroup("图像生成", icon = "bi-image", ordering="3")
 * @BePermissionGroup("图像生成")
 */
class ImageGeneration extends Auth
{

    /**
     * @BeMenu("生成", icon = "bi-image-alt", ordering="1.1")
     * @BePermission("生成", ordering="1.1")
     */
    public function index()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $response->set('title', '生成图像');
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
            $prompt = $request->post('prompt', '');
            $textCompletionId = $request->post('text_completion_id', '');
            $serviceImage = Be::getService('App.Openai.Image');
            $textCompletion = $serviceImage->send($prompt, $textCompletionId);

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
            $serviceImage = Be::getService('App.Openai.Image');
            $textCompletionMessage = $serviceImage->waitSessionMessage($textCompletionId, $textCompletionMessageId);

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
     * @BeMenu("生成图像记录", icon = "bi-images", ordering="1.2")
     * @BePermission("生成图像记录", ordering="1.2")
     */
    public function history()
    {
        Be::getAdminPlugin('Curd')->setting([
            'label' => '生成图像记录',
            'table' => 'openai_image_generation',
            'grid' => [
                'title' => '生成图像记录',
                'orderBy' => 'create_time',
                'orderByDir' => 'DESC',
                'form' => [
                    'items' => [
                        [
                            'name' => 'name',
                            'label' => '名称',
                        ],
                    ],
                ],

                'titleRightToolbar' => [
                    'items' => [
                        [
                            'label' => '新增会话',
                            'action' => 'index',
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
                Be::getService('App.Cms.Admin.Image')->delete($textCompletionIds);
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
