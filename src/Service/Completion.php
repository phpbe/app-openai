<?php

namespace Be\App\Openai\Service;

use Be\App\ServiceException;
use Be\Be;

class Completion
{

    /**
     * 获取会话列表
     *
     * @return array 获取会话列表
     */
    public function getSessions(array $params = []): array
    {
        $tableCompletionSession = Be::getTable('openai_completion_session');
        if (isset($params['is_complete']) && in_array($params['is_complete'], [0, 1])) {
            $tableCompletionSession->where('is_complete', $params['is_complete']);
        }

        $total = $tableCompletionSession->count();

        if (isset($params['orderBy'])) {
            $orderByDir = 'desc';
            if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
                $orderByDir = $params['orderByDir'];
            }

            $tableCompletionSession->orderBy($params['orderBy'], strtoupper($orderByDir));
        } else {
            $tableCompletionSession->orderBy('create_time DESC');
        }

        // 分页
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = 10;
        }

        if ($pageSize > 200) {
            $pageSize = 200;
        }
        $tableCompletionSession->limit($pageSize);

        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $tableCompletionSession->offset(($page - 1) * $pageSize);

        $rows = $tableCompletionSession->getObjects();
        foreach ($rows as $session) {
            $session->lines = (int)$session->lines;
            $session->is_complete = (int)$session->is_complete;
        }

        return [
            'total' => $total,
            'pageSize' => $pageSize,
            'page' => $page,
            'rows' => $rows,
        ];
    }

    /**
     * 获取会话
     *
     * @return object 会话明细
     */
    public function getSession(string $sessionId): object
    {
        $tupleCompletionSession = Be::getTuple('openai_completion_session');
        try {
            $tupleCompletionSession->load($sessionId);
        } catch (\Throwable $t) {
            throw new ServiceException('会话（# ' . $sessionId . '）不存在！');
        }

        $session = $tupleCompletionSession->toObject();

        $tableCompletionSessionMessage = Be::getTable('openai_completion_session_message');
        $tableCompletionSessionMessage->where('completion_session_id', $session->id)->orderBy('line ASC');

        $messages = $tableCompletionSessionMessage->getObjects();
        foreach ($messages as $message) {
            $message->line = (int)$message->line;
            $message->is_complete = (int)$message->is_complete;
            $message->answer = $this->formatAnswer($message->answer);
        }
        $session->messages = $messages;

        return $session;
    }

    /**
     * 提问
     *
     * @param string $question
     * @param string $sessionId
     * @return object
     */
    public function send(string $question, string $sessionId = ''): object
    {
        $question = trim($question);
        if ($question === '') {
            throw new ServiceException('提问内容不可为空！');
        }

        $db = Be::getDb();

        $isNew = ($sessionId === '');

        $tupleCompletionSession = Be::getTuple('openai_completion_session');
        if (!$isNew) {
            try {
                $tupleCompletionSession->load($sessionId);
            } catch (\Throwable $t) {
                throw new ServiceException('会话（# ' . $sessionId . '）不存在！');
            }

            if ($tupleCompletionSession->is_complete === 1) {
                throw new ServiceException('会话因长时间未操作，已关闭！');
            }

            $tableCompletionSessionMessage = Be::getTable('openai_completion_session_message');
            $tableCompletionSessionMessage->where('completion_session_id', $sessionId)
                ->where('is_complete', 0);
            if ($tableCompletionSessionMessage->count() > 0) {
                throw new ServiceException('会话正在处理中，请勿频繁提交！');
            }
        }

        $now = date('Y-m-d H:i:s');

        $db->startTransaction();
        try {

            $tupleCompletionSession->update_time = $now;
            if ($isNew) {
                $tupleCompletionSession->name = $question;
                $tupleCompletionSession->lines = 1;
                $tupleCompletionSession->create_time = $now;
                $tupleCompletionSession->insert();
            } else {
                $tupleCompletionSession->lines = $tupleCompletionSession->lines + 1;;
                $tupleCompletionSession->update();
            }

            $tupleCompletionSessionMessage = Be::getTuple('openai_completion_session_message');
            $tupleCompletionSessionMessage->completion_session_id = $tupleCompletionSession->id;
            $tupleCompletionSessionMessage->line = $tupleCompletionSession->lines;
            $tupleCompletionSessionMessage->question = $question;
            $tupleCompletionSessionMessage->answer = '';
            $tupleCompletionSessionMessage->times = 0;
            $tupleCompletionSessionMessage->is_complete = 0;
            $tupleCompletionSessionMessage->create_time = $now;
            $tupleCompletionSessionMessage->update_time = $now;
            $tupleCompletionSessionMessage->insert();

            $db->commit();

            Be::getService('App.System.Task')->trigger('Openai.Completion');

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException(($isNew ? '新建' : '编辑') . '会话发生异常！');
        }

        $session = $tupleCompletionSession->toObject();
        $session->latestMessage = $tupleCompletionSessionMessage->toObject();

        return $session;
    }

    /**
     * 等待消息
     *
     * @param string $sessionId 会话ID
     * @param string $messageId 消息ID
     * @param int $timeout 超时时间
     * @return object
     */
    public function waitSessionMessage(string $sessionId, string $messageId, int $timeout = 15): object
    {
        $t0 = microtime(1);

        $tupleCompletionSession = Be::getTuple('openai_completion_session');
        try {
            $tupleCompletionSession->load($sessionId);
        } catch (\Throwable $t) {
            throw new ServiceException('会话（# ' . $sessionId . '）不存在！');
        }

        if ($tupleCompletionSession->is_complete === 1) {
            throw new ServiceException('会话已关闭！');
        }

        while (1) {
            $tableCompletionSessionMessage = Be::getTuple('openai_completion_session_message');
            try {
                $tableCompletionSessionMessage->load($messageId);
            } catch (\Throwable $t) {
                throw new ServiceException('会话消息（# ' . $messageId . '）不存在！');
            }

            $sessionMessage = $tableCompletionSessionMessage->toObject();
            $sessionMessage->answer = $this->formatAnswer($sessionMessage->answer);

            if ($sessionMessage->is_complete === 1) {
                break;
            }

            $t1 = microtime(1);
            if ($t1 - $t0 >= $timeout) {
                break;
            }

            if (Be::getRuntime()->isSwooleMode()) {
                \Swoole\Coroutine::sleep(1);
            } else {
                sleep(1);
            }
        }

        return $sessionMessage;
    }

    /**
     * 关闭
     *
     * @param string $sessionId
     * @return object
     */
    public function close(string $sessionId): object
    {
        $tupleCompletionSession = Be::getTuple('openai_completion_session');
        try {
            $tupleCompletionSession->load($sessionId);
        } catch (\Throwable $t) {
            throw new ServiceException('会话（# ' . $sessionId . '）不存在！');
        }

        if ($tupleCompletionSession->is_complete != 1) {
            $tupleCompletionSession->is_complete = 1;;
            $tupleCompletionSession->update_time = date('Y-m-d H:i:s');
            $tupleCompletionSession->update();
        }

        return $tupleCompletionSession->toObject();
    }

    /**
     * 格式化应签回复
     *
     * @param string $answer
     * @return string
     */
    public function formatAnswer(string $answer): string
    {
        $answer = nl2br($answer);

        return $answer;
    }
}
