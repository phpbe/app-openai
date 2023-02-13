<?php

namespace Be\App\Openai\Service\Admin;

use Be\App\ServiceException;
use Be\Be;

class ImageGeneration
{

    /**
     * 删除会话
     *
     * @param array $ImageGenerationIds
     */
    public function delete(array $ImageGenerationIds)
    {
        if (count($ImageGenerationIds) === 0) return;

        $db = Be::getDb();
        $db->startTransaction();
        try {
            foreach ($ImageGenerationIds as $ImageGenerationId) {
                $tupleImageGeneration = Be::getTuple('openai_image_generation');
                try {
                    $tupleImageGeneration->load($ImageGenerationId);
                } catch (\Throwable $t) {
                    throw new ServiceException('生成图像（# ' . $ImageGenerationId . '）不存在！');
                }

                $tupleImageGeneration->delete();
            }

            $db->commit();
        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('删除生成图像发生异常！');
        }
    }

}
