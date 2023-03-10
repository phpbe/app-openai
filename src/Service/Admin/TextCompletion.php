<?php

namespace Be\App\Openai\Service\Admin;

use Be\App\ServiceException;
use Be\Be;

class TextCompletion
{

    /**
     * 删除会话
     *
     * @param array $textCompletionIds
     */
    public function delete(array $textCompletionIds)
    {
        if (count($textCompletionIds) === 0) return;

        $db = Be::getDb();
        $db->startTransaction();
        try {
            foreach ($textCompletionIds as $textCompletionId) {
                $tupleTextCompletion = Be::getTuple('openai_text_completion');
                try {
                    $tupleTextCompletion->load($textCompletionId);
                } catch (\Throwable $t) {
                    throw new ServiceException('会话（# ' . $textCompletionId . '）不存在！');
                }

                Be::getTable('openai_text_completion_message')
                    ->where('text_completion_id', $textCompletionId)
                    ->delete();

                $tupleTextCompletion->delete();
            }

            $db->commit();
        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('删除会话发生异常！');
        }
    }

}
