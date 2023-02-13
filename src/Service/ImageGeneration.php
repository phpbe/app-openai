<?php

namespace Be\App\Openai\Service;

use Be\App\ServiceException;
use Be\Be;

class ImageGeneration
{

    /**
     * 获取生成图像列表
     *
     * @return array 获取生成图像列表
     */
    public function getHistory(array $params = []): array
    {
        $tableImageGeneration = Be::getTable('openai_image_generation');

        if (isset($params['is_complete']) && in_array($params['is_complete'], [0, 1])) {
            $tableImageGeneration->where('is_complete', $params['is_complete']);
        }

        $total = $tableImageGeneration->count();

        if (isset($params['orderBy'])) {
            $orderByDir = 'desc';
            if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
                $orderByDir = $params['orderByDir'];
            }

            $tableImageGeneration->orderBy($params['orderBy'], strtoupper($orderByDir));
        } else {
            $tableImageGeneration->orderBy('create_time DESC');
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
        $tableImageGeneration->limit($pageSize);

        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $tableImageGeneration->offset(($page - 1) * $pageSize);

        $rows = $tableImageGeneration->getObjects();
        foreach ($rows as $imageGeneration) {
            $imageGeneration->lines = (int)$imageGeneration->lines;
            $imageGeneration->is_complete = (int)$imageGeneration->is_complete;
        }

        return [
            'total' => $total,
            'pageSize' => $pageSize,
            'page' => $page,
            'rows' => $rows,
        ];
    }

    /**
     * 获取生成图像
     *
     * @return object 生成图像明细
     */
    public function get(string $imageGenerationId): object
    {
        $tupleImageGeneration = Be::getTuple('openai_image_generation');
        try {
            $tupleImageGeneration->load($imageGenerationId);
        } catch (\Throwable $t) {
            throw new ServiceException('生成图像（# ' . $imageGenerationId . '）不存在！');
        }

        $imageGeneration = $tupleImageGeneration->toObject();
        return $imageGeneration;
    }

    /**
     * 提问
     *
     * @param string $prompt
     * @return object
     */
    public function send(string $prompt): object
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new ServiceException('提问内容不可为空！');
        }

        $db = Be::getDb();

        $tupleImageGeneration = Be::getTuple('openai_image_generation');

        $now = date('Y-m-d H:i:s');

        $db->startTransaction();
        try {

            $tupleImageGeneration->prompt = $prompt;
            $tupleImageGeneration->url = '';
            $tupleImageGeneration->local_url = '';
            $tupleImageGeneration->is_complete = 0;
            $tupleImageGeneration->create_time = $now;
            $tupleImageGeneration->update_time = $now;
            $tupleImageGeneration->insert();

            $db->commit();

            Be::getService('App.System.Task')->trigger('Openai.ImageGeneration');

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException(($isNew ? '新建' : '编辑') . '生成图像发生异常！');
        }

        return $tupleImageGeneration->toObject();
    }

    /**
     * 等待消息
     *
     * @param string $imageGenerationId 生成图像ID
     * @param string $imageGenerationMessageId 消息ID
     * @param int $timeout 超时时间
     * @return object
     */
    public function waitMessage(string $imageGenerationId, string $imageGenerationMessageId, int $timeout = 15): object
    {
        $t0 = microtime(1);

        $tupleImageGeneration = Be::getTuple('openai_image_generation');
        try {
            $tupleImageGeneration->load($imageGenerationId);
        } catch (\Throwable $t) {
            throw new ServiceException('生成图像（# ' . $imageGenerationId . '）不存在！');
        }

        if ($tupleImageGeneration->is_complete === 1) {
            throw new ServiceException('生成图像已关闭！');
        }

        while (1) {
            $tableImageGenerationMessage = Be::getTuple('openai_image_generation_message');
            try {
                $tableImageGenerationMessage->load($imageGenerationMessageId);
            } catch (\Throwable $t) {
                throw new ServiceException('生成图像消息（# ' . $imageGenerationMessageId . '）不存在！');
            }

            $imageGenerationMessage = $tableImageGenerationMessage->toObject();
            $imageGenerationMessage->answer = $this->formatAnswer($imageGenerationMessage->answer);

            if ($imageGenerationMessage->is_complete === 1) {
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

        return $imageGenerationMessage;
    }

    /**
     * 关闭
     *
     * @param string $imageGenerationId
     * @return object
     */
    public function close(string $imageGenerationId): object
    {
        $tupleImageGeneration = Be::getTuple('openai_image_generation');
        try {
            $tupleImageGeneration->load($imageGenerationId);
        } catch (\Throwable $t) {
            throw new ServiceException('生成图像（# ' . $imageGenerationId . '）不存在！');
        }

        if ($tupleImageGeneration->is_complete != 1) {
            $tupleImageGeneration->is_complete = 1;;
            $tupleImageGeneration->update_time = date('Y-m-d H:i:s');
            $tupleImageGeneration->update();
        }

        return $tupleImageGeneration->toObject();
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
