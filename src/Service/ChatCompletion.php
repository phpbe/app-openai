<?php

namespace Be\App\Openai\Service;

use Be\App\ServiceException;
use Be\Be;

class ChatCompletion
{

    /**
     * 获取会话列表
     *
     * @return array 获取会话列表
     */
    public function getHistory(array $params = []): array
    {
        $tableChatCompletion = Be::getTable('openai_chat_completion');

        $total = $tableChatCompletion->count();

        if (isset($params['orderBy'])) {
            $orderByDir = 'desc';
            if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
                $orderByDir = $params['orderByDir'];
            }

            $tableChatCompletion->orderBy($params['orderBy'], strtoupper($orderByDir));
        } else {
            $tableChatCompletion->orderBy('create_time DESC');
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
        $tableChatCompletion->limit($pageSize);

        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $tableChatCompletion->offset(($page - 1) * $pageSize);

        $rows = $tableChatCompletion->getObjects();
        foreach ($rows as $chatCompletion) {
            $chatCompletion->lines = (int)$chatCompletion->lines;
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
    public function get(string $chatCompletionId): object
    {
        $tupleChatCompletion = Be::getTuple('openai_chat_completion');
        try {
            $tupleChatCompletion->load($chatCompletionId);
        } catch (\Throwable $t) {
            throw new ServiceException('会话（# ' . $chatCompletionId . '）不存在！');
        }

        $chatCompletion = $tupleChatCompletion->toObject();

        $messages = Be::getTable('openai_chat_completion_message')
            ->where('chat_completion_id', $chatCompletion->id)
            ->orderBy('line ASC')
            ->getObjects();
        foreach ($messages as $message) {
            $message->line = (int)$message->line;
            $message->is_complete = (int)$message->is_complete;
            $message->prompt = nl2br($message->prompt);
            $message->answer = $this->formatAnswer($message->answer);
        }
        $chatCompletion->messages = $messages;

        return $chatCompletion;
    }

    /**
     * 提问
     *
     * @param string $prompt
     * @param string $chatCompletionId
     * @return object
     */
    public function send(string $prompt, string $chatCompletionId = ''): object
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new ServiceException('提问内容不可为空！');
        }

        if (mb_strlen($prompt) > 1000) {
            throw new ServiceException('提问内容最多500个字！');
        }

        $db = Be::getDb();

        $isNew = ($chatCompletionId === '');

        $tupleChatCompletion = Be::getTuple('openai_chat_completion');
        if (!$isNew) {
            try {
                $tupleChatCompletion->load($chatCompletionId);
            } catch (\Throwable $t) {
                throw new ServiceException('会话（# ' . $chatCompletionId . '）不存在！');
            }

            if (Be::getTable('openai_chat_completion_message')
                    ->where('chat_completion_id', $chatCompletionId)
                    ->where('is_complete', 0)
                    ->count() > 0) {
                throw new ServiceException('会话正在处理中，请勿频繁提交！');
            }
        }

        $now = date('Y-m-d H:i:s');

        $db->startTransaction();
        try {

            $tupleChatCompletion->update_time = $now;
            if ($isNew) {
                $tupleChatCompletion->prompt = $prompt;
                $tupleChatCompletion->lines = 1;
                $tupleChatCompletion->create_time = $now;
                $tupleChatCompletion->insert();
            } else {
                $tupleChatCompletion->lines = $tupleChatCompletion->lines + 1;;
                $tupleChatCompletion->update();
            }

            $tupleChatCompletionMessage = Be::getTuple('openai_chat_completion_message');
            $tupleChatCompletionMessage->chat_completion_id = $tupleChatCompletion->id;
            $tupleChatCompletionMessage->line = $tupleChatCompletion->lines;
            $tupleChatCompletionMessage->prompt = $prompt;
            $tupleChatCompletionMessage->answer = '处理中.';
            $tupleChatCompletionMessage->times = 0;
            $tupleChatCompletionMessage->is_complete = 0;
            $tupleChatCompletionMessage->create_time = $now;
            $tupleChatCompletionMessage->update_time = $now;
            $tupleChatCompletionMessage->insert();

            $db->commit();

            Be::getService('App.System.Task')->trigger('Openai.ChatCompletion');

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException(($isNew ? '新建' : '编辑') . '会话发生异常！');
        }

        $chatCompletion = $tupleChatCompletion->toObject();
        $chatCompletion->latestMessage = $tupleChatCompletionMessage->toObject();
        $chatCompletion->latestMessage->prompt = nl2br($chatCompletion->latestMessage->prompt);

        $messages = Be::getTable('openai_chat_completion_message')
            ->where('chat_completion_id', $chatCompletion->id)
            ->orderBy('line ASC')
            ->getObjects();
        foreach ($messages as $message) {
            $message->line = (int)$message->line;
            $message->is_complete = (int)$message->is_complete;
            $message->prompt = nl2br($message->prompt);
            $message->answer = $this->formatAnswer($message->answer);
        }
        $chatCompletion->messages = $messages;

        return $chatCompletion;
    }

    /**
     * 等待消息
     *
     * @param string $chatCompletionId 会话ID
     * @param string $chatCompletionMessageId 消息ID
     * @param int $timeout 超时时间
     * @return object
     */
    public function waitMessage(string $chatCompletionId, string $chatCompletionMessageId, int $timeout = 15): object
    {
        $t0 = microtime(1);

        $tupleChatCompletion = Be::getTuple('openai_chat_completion');
        try {
            $tupleChatCompletion->load($chatCompletionId);
        } catch (\Throwable $t) {
            throw new ServiceException('会话（# ' . $chatCompletionId . '）不存在！');
        }

        while (1) {
            $tableChatCompletionMessage = Be::getTuple('openai_chat_completion_message');
            try {
                $tableChatCompletionMessage->load($chatCompletionMessageId);
            } catch (\Throwable $t) {
                throw new ServiceException('会话消息（# ' . $chatCompletionMessageId . '）不存在！');
            }

            $chatCompletionMessage = $tableChatCompletionMessage->toObject();

            $chatCompletionMessage->prompt = nl2br($chatCompletionMessage->prompt);
            $chatCompletionMessage->answer = $this->formatAnswer($chatCompletionMessage->answer);

            if ($chatCompletionMessage->is_complete === 1) {
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

        return $chatCompletionMessage;
    }

    /**
     * 格式化应答回复
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
