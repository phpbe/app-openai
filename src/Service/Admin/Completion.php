<?php

namespace Be\App\Openai\Service;

use Be\App\ServiceException;
use Be\Be;

class Completion
{

    /**
     * 删除会话
     *
     * @param array $sessionIds
     */
    public function delete(array $sessionIds)
    {
        if (count($sessionIds) === 0) return;

        $db = Be::getDb();
        $db->startTransaction();
        try {
            foreach ($sessionIds as $sessionId) {
                $tupleSession = Be::getTuple('openai_completion_session');
                try {
                    $tupleSession->load($sessionId);
                } catch (\Throwable $t) {
                    throw new ServiceException('会话（# ' . $sessionId . '）不存在！');
                }

                Be::getTable('openai_completion_session_message')
                    ->where('completion_session_id', $sessionId)
                    ->delete();

                $tupleSession->delete();
            }

            $db->commit();
        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('删除会话发生异常！');
        }
    }

}
