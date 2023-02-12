<?php

namespace Be\App\Openai\Service;

use Be\App\ServiceException;
use Be\Be;

class TextCompletion
{

    /**
     * 获取会话列表
     *
     * @return array 获取会话列表
     */
    public function getHistory(array $params = []): array
    {
        $tableTextCompletion = Be::getTable('openai_text_completion');
        if (isset($params['is_complete']) && in_array($params['is_complete'], [0, 1])) {
            $tableTextCompletion->where('is_complete', $params['is_complete']);
        }

        $total = $tableTextCompletion->count();

        if (isset($params['orderBy'])) {
            $orderByDir = 'desc';
            if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
                $orderByDir = $params['orderByDir'];
            }

            $tableTextCompletion->orderBy($params['orderBy'], strtoupper($orderByDir));
        } else {
            $tableTextCompletion->orderBy('create_time DESC');
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
        $tableTextCompletion->limit($pageSize);

        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $tableTextCompletion->offset(($page - 1) * $pageSize);

        $rows = $tableTextCompletion->getObjects();
        foreach ($rows as $textCompletion) {
            $textCompletion->lines = (int)$textCompletion->lines;
            $textCompletion->is_complete = (int)$textCompletion->is_complete;
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
    public function get(string $textCompletionId): object
    {
        $tupleTextCompletion = Be::getTuple('openai_text_completion');
        try {
            $tupleTextCompletion->load($textCompletionId);
        } catch (\Throwable $t) {
            throw new ServiceException('会话（# ' . $textCompletionId . '）不存在！');
        }

        $textCompletion = $tupleTextCompletion->toObject();

        $tableTextCompletionMessage = Be::getTable('openai_text_completion_message');
        $tableTextCompletionMessage->where('text_completion_id', $textCompletion->id)->orderBy('line ASC');

        $messages = $tableTextCompletionMessage->getObjects();
        foreach ($messages as $message) {
            $message->line = (int)$message->line;
            $message->is_complete = (int)$message->is_complete;
            $message->answer = $this->formatAnswer($message->answer);
        }
        $textCompletion->messages = $messages;

        return $textCompletion;
    }

    /**
     * 提问
     *
     * @param string $prompt
     * @param string $textCompletionId
     * @return object
     */
    public function send(string $prompt, string $textCompletionId = ''): object
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new ServiceException('提问内容不可为空！');
        }

        $db = Be::getDb();

        $isNew = ($textCompletionId === '');

        $tupleTextCompletion = Be::getTuple('openai_text_completion');
        if (!$isNew) {
            try {
                $tupleTextCompletion->load($textCompletionId);
            } catch (\Throwable $t) {
                throw new ServiceException('会话（# ' . $textCompletionId . '）不存在！');
            }

            if ($tupleTextCompletion->is_complete === 1) {
                throw new ServiceException('会话因长时间未操作，已关闭！');
            }

            $tableTextCompletionMessage = Be::getTable('openai_text_completion_message');
            $tableTextCompletionMessage->where('text_completion_id', $textCompletionId)
                ->where('is_complete', 0);
            if ($tableTextCompletionMessage->count() > 0) {
                throw new ServiceException('会话正在处理中，请勿频繁提交！');
            }
        }

        $now = date('Y-m-d H:i:s');

        $db->startTransaction();
        try {

            $tupleTextCompletion->update_time = $now;
            if ($isNew) {
                $tupleTextCompletion->prompt = $prompt;
                $tupleTextCompletion->lines = 1;
                $tupleTextCompletion->create_time = $now;
                $tupleTextCompletion->insert();
            } else {
                $tupleTextCompletion->lines = $tupleTextCompletion->lines + 1;;
                $tupleTextCompletion->update();
            }

            $tupleTextCompletionMessage = Be::getTuple('openai_text_completion_message');
            $tupleTextCompletionMessage->text_completion_id = $tupleTextCompletion->id;
            $tupleTextCompletionMessage->line = $tupleTextCompletion->lines;
            $tupleTextCompletionMessage->prompt = $prompt;
            $tupleTextCompletionMessage->answer = '';
            $tupleTextCompletionMessage->times = 0;
            $tupleTextCompletionMessage->is_complete = 0;
            $tupleTextCompletionMessage->create_time = $now;
            $tupleTextCompletionMessage->update_time = $now;
            $tupleTextCompletionMessage->insert();

            $db->commit();

            Be::getService('App.System.Task')->trigger('Openai.TextCompletion');

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException(($isNew ? '新建' : '编辑') . '会话发生异常！');
        }

        $textCompletion = $tupleTextCompletion->toObject();
        $textCompletion->latestMessage = $tupleTextCompletionMessage->toObject();

        return $textCompletion;
    }

    /**
     * 等待消息
     *
     * @param string $textCompletionId 会话ID
     * @param string $textCompletionMessageId 消息ID
     * @param int $timeout 超时时间
     * @return object
     */
    public function waitMessage(string $textCompletionId, string $textCompletionMessageId, int $timeout = 15): object
    {
        $t0 = microtime(1);

        $tupleTextCompletion = Be::getTuple('openai_text_completion');
        try {
            $tupleTextCompletion->load($textCompletionId);
        } catch (\Throwable $t) {
            throw new ServiceException('会话（# ' . $textCompletionId . '）不存在！');
        }

        if ($tupleTextCompletion->is_complete === 1) {
            throw new ServiceException('会话已关闭！');
        }

        while (1) {
            $tableTextCompletionMessage = Be::getTuple('openai_text_completion_message');
            try {
                $tableTextCompletionMessage->load($textCompletionMessageId);
            } catch (\Throwable $t) {
                throw new ServiceException('会话消息（# ' . $textCompletionMessageId . '）不存在！');
            }

            $textCompletionMessage = $tableTextCompletionMessage->toObject();
            $textCompletionMessage->answer = $this->formatAnswer($textCompletionMessage->answer);

            if ($textCompletionMessage->is_complete === 1) {
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

        return $textCompletionMessage;
    }

    /**
     * 关闭
     *
     * @param string $textCompletionId
     * @return object
     */
    public function close(string $textCompletionId): object
    {
        $tupleTextCompletion = Be::getTuple('openai_text_completion');
        try {
            $tupleTextCompletion->load($textCompletionId);
        } catch (\Throwable $t) {
            throw new ServiceException('会话（# ' . $textCompletionId . '）不存在！');
        }

        if ($tupleTextCompletion->is_complete != 1) {
            $tupleTextCompletion->is_complete = 1;;
            $tupleTextCompletion->update_time = date('Y-m-d H:i:s');
            $tupleTextCompletion->update();
        }

        return $tupleTextCompletion->toObject();
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
