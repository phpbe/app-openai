<?php

namespace Be\App\Openai\Service\Admin;

use Be\App\ServiceException;
use Be\Be;

class ChatCompletion
{

    /**
     * 删除会话
     *
     * @param array $chatCompletionIds
     */
    public function delete(array $chatCompletionIds)
    {
        if (count($chatCompletionIds) === 0) return;

        $db = Be::getDb();
        $db->startTransaction();
        try {
            foreach ($chatCompletionIds as $chatCompletionId) {
                $tupleChatCompletion = Be::getTuple('openai_chat_completion');
                try {
                    $tupleChatCompletion->load($chatCompletionId);
                } catch (\Throwable $t) {
                    throw new ServiceException('会话（# ' . $chatCompletionId . '）不存在！');
                }

                Be::getTable('openai_chat_completion_message')
                    ->where('chat_completion_id', $chatCompletionId)
                    ->delete();

                $tupleChatCompletion->delete();
            }

            $db->commit();
        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('删除会话发生异常！');
        }
    }

}
