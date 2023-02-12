<?php

namespace Be\App\Openai\Service\Admin;

use Be\App\ServiceException;
use Be\Be;
use Be\Db\Tuple;

class Url
{


    /**
     * 编辑网址
     *
     * @param string $categoryId 分类ID
     * @param array $formData 表单数据
     * @throws \Throwable
     */
    public function edit(string $categoryId, array $formData)
    {
        $db = Be::getDb();
        $db->startTransaction();
        try {

            $keepGroupIds = [];
            foreach ($formData as $group) {
                if (isset($group['id']) && $group['id'] !== '' && substr($group['id'], 0, 1) !== '-') {
                    $keepGroupIds[] = $group['id'];
                }
            }

            if (count($keepGroupIds) > 0) {
                $deleteGroupIds = Be::getTable('Openai_group')
                    ->where('category_id', $categoryId)
                    ->where('id', 'NOT IN', $keepGroupIds)
                    ->getValues('id');

                if (count($deleteGroupIds) > 0) {
                    Be::getTable('Openai_url')
                        ->where('group_id', 'IN', $deleteGroupIds)
                        ->delete();

                    Be::getTable('Openai_group')
                        ->where('id', 'IN', $deleteGroupIds)
                        ->delete();
                }
            } else {
                $deleteGroupIds = Be::getTable('Openai_group')
                    ->where('category_id', $categoryId)
                    ->getValues('id');

                if (count($deleteGroupIds) > 0) {
                    Be::getTable('Openai_url')
                        ->where('group_id', 'IN', $deleteGroupIds)
                        ->delete();

                    Be::getTable('Openai_group')
                        ->where('category_id', $categoryId)
                        ->delete();
                }
            }

            $now = date('Y-m-d H:i:s');

            $groupOrdering = 1;
            foreach ($formData as $group) {

                $isNewGroup = true;
                $groupId = null;
                if (isset($group['id']) && $group['id'] !== '' && substr($group['id'], 0, 1) !== '-') {
                    $isNewGroup = false;
                    $groupId = $group['id'];
                }

                $tupleGroup = Be::getTuple('Openai_group');
                if (!$isNewGroup) {
                    try {
                        $tupleGroup->load($groupId);
                    } catch (\Throwable $t) {
                        throw new ServiceException('分组（# ' . $groupId . '）不存在！');
                    }

                    if ($tupleGroup->category_id !== $categoryId) {
                        throw new ServiceException('分组（# ' . $groupId . '）不属于分类（# ' . $categoryId . '）！');
                    }
                }

                if (!isset($group['name']) || !is_string($group['name'])) {
                    $group['name'] = '';
                }

                if (!isset($group['is_enable']) || !is_numeric($group['is_enable'])) {
                    $group['is_enable'] = 0;
                } else {
                    $group['is_enable'] = (int)$group['is_enable'];
                }
                if (!in_array($group['is_enable'], [0, 1])) {
                    $group['is_enable'] = 0;
                }

                $tupleGroup->name = $group['name'];
                $tupleGroup->ordering = $groupOrdering;
                $tupleGroup->is_enable = $group['is_enable'];
                $tupleGroup->is_delete = 0;
                $tupleGroup->update_time = $now;
                if ($isNewGroup) {
                    $tupleGroup->category_id = $categoryId;
                    $tupleGroup->create_time = $now;
                    $tupleGroup->insert();

                    $groupId = $tupleGroup->id;
                } else {
                    $tupleGroup->update();
                }

                if (!$isNewGroup) {
                    $keepUrlIds = [];
                    foreach ($group['urls'] as $url) {
                        if (isset($url['id']) && $url['id'] !== '' && substr($url['id'], 0, 1) !== '-') {
                            $keepUrlIds[] = $url['id'];
                        }
                    }

                    if (count($keepUrlIds) > 0) {
                        Be::getTable('Openai_url')
                            ->where('group_id', $groupId)
                            ->where('id', 'NOT IN', $keepUrlIds)
                            ->delete();
                    } else {
                        Be::getTable('Openai_url')
                            ->where('group_id', $groupId)
                            ->delete();
                    }
                }

                $urlOrdering = 1;
                foreach ($group['urls'] as $url) {

                    $isNewUrl = true;
                    $urlId = null;
                    if (isset($url['id']) && $url['id'] !== '' && substr($url['id'], 0, 1) !== '-') {
                        $isNewUrl = false;
                        $urlId = $url['id'];
                    }

                    $tupleUrl = Be::getTuple('Openai_url');
                    if (!$isNewUrl) {
                        try {
                            $tupleUrl->load($urlId);
                        } catch (\Throwable $t) {
                            throw new ServiceException('网址（# ' . $urlId . '）不存在！');
                        }
                    }

                    if (!$isNewGroup && !$isNewUrl) {
                        if ($tupleUrl->group_id !== $groupId) {
                            throw new ServiceException('网址（# ' . $urlId . '）不属于分组（# ' . $groupId . '）！');
                        }
                    }

                    if (!isset($url['name']) || !is_string($url['name'])) {
                        $url['name'] = '';
                    }

                    if (!isset($url['url']) || !is_string($url['url'])) {
                        $url['url'] = '';
                    }

                    if (!isset($url['is_enable']) || !is_numeric($url['is_enable'])) {
                        $url['is_enable'] = 0;
                    } else {
                        $url['is_enable'] = (int)$url['is_enable'];
                    }
                    if (!in_array($url['is_enable'], [0, 1])) {
                        $url['is_enable'] = 0;
                    }

                    if (!isset($url['has_account']) || !is_numeric($url['has_account'])) {
                        $url['has_account'] = 0;
                    } else {
                        $url['has_account'] = (int)$url['has_account'];
                    }
                    if (!in_array($url['has_account'], [0, 1])) {
                        $url['has_account'] = 0;
                    }

                    if ($url['has_account'] === 1) {
                        if (!isset($url['username']) || !is_string($url['username'])) {
                            $url['username'] = '';
                        }

                        if (!isset($url['password']) || !is_string($url['password'])) {
                            $url['password'] = '';
                        }

                        if ($url['username'] === '' && $url['password'] === '') {
                            $url['has_account'] = 0;
                        }
                    } else {
                        $url['username'] = '';
                        $url['password'] = '';
                    }

                    $tupleUrl->name = $url['name'];
                    $tupleUrl->url = $url['url'];
                    $tupleUrl->has_account = $url['has_account'];
                    $tupleUrl->username = $url['username'];
                    $tupleUrl->password = $url['password'];
                    $tupleUrl->ordering = $urlOrdering;
                    $tupleUrl->is_enable = $url['is_enable'];
                    $tupleUrl->is_delete = 0;
                    $tupleUrl->update_time = $now;
                    if ($isNewUrl) {
                        $tupleUrl->group_id = $groupId;
                        $tupleUrl->create_time = $now;
                        $tupleUrl->insert();
                    } else {
                        $tupleUrl->update();
                    }

                    $urlOrdering++;
                }

                $groupOrdering++;
            }

            $db->commit();
        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);
            throw new ServiceException('保存时发生异常！');
        }
    }



    /**
     * 获取分类列表
     *
     * @return array 分类列表
     */
    public function getGroupUrls($categoryId): array
    {
        $db = Be::getDb();

        $sql = 'SELECT * FROM Openai_group WHERE category_id=? AND is_delete=0 ORDER BY ordering ASC';
        $groups = $db->getObjects($sql, [$categoryId]);

        foreach ($groups as $group) {
            $group->ordering = (int)$group->ordering;
            $group->is_enable = (int)$group->is_enable;
            $group->is_delete = (int)$group->is_delete;

            $sql = 'SELECT * FROM Openai_url WHERE group_id =? AND is_delete=0 ORDER BY ordering ASC';
            $urls = $db->getObjects($sql, [$group->id]);
            foreach ($urls as $url) {
                $url->ordering = (int)$url->ordering;
                $url->has_account = (int)$url->has_account;
                $url->is_enable = (int)$url->is_enable;
                $url->is_delete = (int)$url->is_delete;
            }
            $group->urls = $urls;
        }

        return $groups;
    }


}
