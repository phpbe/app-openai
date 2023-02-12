<?php

namespace Be\App\Openai\Service\Admin;

use Be\App\ServiceException;
use Be\Be;

class Category
{

    /**
     * 获取分类列表
     *
     * @return array 分类列表
     */
    public function getCategories(): array
    {
        $sql = 'SELECT * FROM Openai_category WHERE is_delete=0 ORDER BY ordering ASC';
        return Be::getDb()->getObjects($sql);
    }

    /**
     * 获取分类树
     *
     * @return array 分类树
     */
    public function getTree(): array
    {
        $categories = $this->getCategories();
        return $this->makeTree($categories);
    }

    /**
     * 生成树
     *
     * @param array $categories
     * @param string $parentId
     * @return array
     */
    private function makeTree(array $categories, string $parentId = '')
    {
        $children = [];
        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $category->children = $this->makeTree($categories, $category->id);
                $children[] = $category;
            }
        }
        return $children;
    }

    /**
     * 获取拉平的分类树
     *
     * @return array 拉平的分类树 - 一维
     */
    public function getFlatTree(): array
    {
        $categoryFlatTree = [];
        $categories = $this->getCategories();
        $this->makeFlatTree($categories, $categoryFlatTree);
        return $categoryFlatTree;
    }

    /**
     * 生成拉平的树
     *
     * @param array $categories
     * @param array $categoryFlatTree
     * @param string $parentId
     * @param int $level
     */
    private function makeFlatTree(array $categories, array &$categoryFlatTree, string $parentId = '', int $level = 1)
    {
        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $categoryFlatTree[] = [
                    'id' => $category->id,
                    'parent_id' => $category->parent_id,
                    'name' => $category->name,
                    'is_enable' => (int)$category->is_enable,
                    'level' => $level,
                ];

                $this->makeFlatTree($categories, $categoryFlatTree, $category->id, $level + 1);
            }
        }
    }

    /**
     * 保存分类
     *
     * @param array $formData 分类数据
     * @return bool
     * @throws \Throwable
     */
    public function save($formData)
    {
        $categories = $formData['categories'];

        $db = Be::getDb();
        $db->startTransaction();
        try {

            $keepIds = [];
            foreach ($categories as $category) {
                if (!isset($category['id'])) {
                    throw new ServiceException('分类参数（id）缺失！');
                }

                if (substr($category['id'], 0, 1) !== '-') {
                    $keepIds[] = $category['id'];
                }
            }

            if (count($keepIds) > 0) {
                Be::getTable('Openai_category')
                    ->where('id', 'NOT IN', $keepIds)
                    ->update(['is_delete' => 1]);
            } else {
                Be::getTable('Openai_category')
                    ->update(['is_delete' => 1]);
            }

            $now = date('Y-m-d H:i:s');

            $parentIds = [];
            $ordering = 0;
            foreach ($categories as $category) {
                $isNew = false;
                if (substr($category['id'], 0, 1) === '-') {
                    $isNew = true;
                }

                $tupleCategory = Be::getTuple('Openai_category');

                if (!$isNew) {
                    try {
                        $tupleCategory->load($category['id']);
                    } catch (\Throwable $t) {
                        throw new ServiceException('分类（# ' . $category['id'] . '）不存在！');
                    }
                }

                $parentId = $category['parent_id'] ?? '';
                $name = $category['name'] ?? '';
                $isEnable = $category['is_enable'] ?? 1;

                if (substr($parentId, 0, 1) === '-') {
                    $parentId = $parentIds[$parentId];
                }

                if (!$name) {
                    throw new ServiceException('请填写第' . ($ordering + 1) . '个分类的名称！');
                }

                if (!in_array($isEnable, [0, 1])) {
                    $isEnable = 1;
                }

                $tupleCategory->parent_id = $parentId;
                $tupleCategory->name = $name;
                $tupleCategory->is_enable = $isEnable;

                $tupleCategory->ordering = $ordering;

                if (!$isNew) {
                    $tupleCategory->create_time = $now;
                }

                $tupleCategory->update_time = $now;
                $tupleCategory->save();

                if ($isNew) {
                    $parentIds[$category['id']] = $tupleCategory->id;
                }

                $ordering++;
            }

            $db->commit();

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('保存分类出错！');
        }

        return true;
    }


}
