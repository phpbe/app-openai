<?php

namespace Be\App\Openai\Controller\Admin;

use Be\AdminPlugin\Detail\Item\DetailItemCode;
use Be\AdminPlugin\Detail\Item\DetailItemImage;
use Be\AdminPlugin\Detail\Item\DetailItemToggleIcon;
use Be\AdminPlugin\Table\Item\TableItemImage;
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
     * @BeMenu("生成图像", icon = "bi-image-alt", ordering="1.1")
     * @BePermission("生成图像", ordering="1.1")
     */
    public function index()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $response->set('title', '生成图像');
        $response->display();
    }

    /**
     * @BePermission("生成")
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
     * 生成图像-发送
     *
     * @BePermission("生成图像")
     */
    public function send()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $serviceImageGeneration = Be::getService('App.Openai.ImageGeneration');
            $imageGeneration = $serviceImageGeneration->send($request->post());

            $response->set('success', true);
            $response->set('message', '提交成功！');
            $response->set('imageGeneration', $imageGeneration);
            $response->json();
        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

    /**
     * 生成图像-接收
     *
     * @BePermission("生成图像")
     */
    public function receive()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $imageGenerationId = $request->post('image_generation_id', '');
            $serviceImageGeneration = Be::getService('App.Openai.ImageGeneration');
            $imageGeneration = $serviceImageGeneration->wait($imageGenerationId);

            $response->set('success', true);
            $response->set('message', '获取成功！');
            $response->set('imageGeneration', $imageGeneration);
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
                            'label' => '生成新图像',
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
                            'name' => 'prompt',
                            'label' => '提问',
                            'align' => 'left',
                            'driver' => TableItemLink::class,
                            'task' => 'detail',
                            'target' => 'drawer',
                            'drawer' => [
                                'width' => '75%',
                            ],
                        ],
                        [
                            'name' => 'url',
                            'label' => '生成的图像',
                            'driver' => TableItemImage::class,
                            'width' => '160',
                            'action' => 'view',
                            'target' => 'blank',
                        ],
                        [
                            'name' => 'is_complete',
                            'label' => '是否完成',
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

            'detail' => [
                'form' => [
                    'items' => [
                        [
                            'name' => 'id',
                            'label' => 'ID',
                        ],
                        [
                            'name' => 'prompt',
                            'label' => '提问',
                        ],
                        [
                            'name' => 'options',
                            'label' => '参数',
                            'driver' => DetailItemCode::class,
                            'language' => 'json',
                            'value' => function ($row) {
                                return json_encode(unserialize($row['options']));
                            },
                        ],
                        [
                            'name' => 'image',
                            'label' => '生成图像',
                            'driver' => DetailItemImage::class,
                            'value' => function ($row) {
                                return  $row['url'];
                            },
                        ],
                        [
                            'name' => 'url',
                            'label' => '生成图像网址',
                        ],
                        [
                            'name' => 'times',
                            'label' => '失败重试次数',
                        ],
                        [
                            'name' => 'is_complete',
                            'driver' => DetailItemToggleIcon::class,
                            'label' => '是否完成',
                        ],
                        [
                            'name' => 'create_time',
                            'label' => '创建时间',
                        ],
                        [
                            'name' => 'update_time',
                            'label' => '更新时间',
                        ],
                    ]
                ],
            ],
        ])->execute();
    }

    /**
     * 会话记录-删除
     *
     * @BePermission("生成图像记录")
     */
    public function delete()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $postData = $request->json();

            $imageGenerationIds = [];
            if (isset($postData['selectedRows'])) {
                foreach ($postData['selectedRows'] as $row) {
                    $imageGenerationIds[] = $row['id'];
                }
            } elseif (isset($postData['row'])) {
                $imageGenerationIds[] = $postData['row']['id'];
            }

            if (count($imageGenerationIds) > 0) {
                Be::getService('App.Openai.Admin.ImageGeneration')->delete($imageGenerationIds);
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

    /**
     * @BePermission("生成图像记录")
     */
    public function view()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $postData = $request->post('data', '', '');
        if ($postData) {
            $postData = json_decode($postData, true);
            if (isset($postData['row']['id']) && $postData['row']['id']) {
                $imageGenerationId = $postData['row']['id'];
                $imageGeneration = Be::getService('App.Openai.ImageGeneration')->get($imageGenerationId);
                $substr = substr($imageGeneration->url,0, 7);
                if ($substr === 'http://' || $substr === 'https:/') {
                    $response->redirect( $imageGeneration->url);
                } else {
                    $response->write($imageGeneration->url);
                    $response->end();
                }
            }
        }
    }



}
