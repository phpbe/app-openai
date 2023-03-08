<?php

namespace Be\App\Openai\Task;

use Be\Be;
use Be\Task\Task;

/**
 * @BeTask("调用API应答", schedule="* * * * *")
 */
class ChatCompletion extends Task
{

    /**
     * 是否可并行执行
     *
     * @var null|bool
     */
    protected $parallel = false;

    /**
     * 执行超时时间
     *
     * @var null|int
     */
    protected $timeout = 300;

    public function execute()
    {
        $configApi = Be::getConfig('App.Openai.Api');
        $serviceApi = Be::getService('App.Openai.Api');

        $db = Be::getDb();

        $t0 = time();
        do {

            $incomplete = 0;
            $sql = 'SELECT * FROM openai_chat_completion_message WHERE is_complete = 0';
            $messages = $db->getObjects($sql);

            foreach ($messages as $message) {

                $chatMessages = [];

                // 交互式会话
                if ($message->line > 1) {
                    $sql = 'SELECT * FROM openai_chat_completion_message WHERE chat_completion_id = ? ORDER BY line ASC';
                    $ms = $db->getObjects($sql, [$message->chat_completion_id]);
                    foreach ($ms as $m) {
                        $chatMessages[] = [
                            'role' => 'user',
                            'content' => $message->prompt
                        ];

                        $chatMessages[] = [
                            'role' => 'assistant',
                            'content' => $message->answer
                        ];
                    }

                } else {
                    $chatMessages[] = [
                        'role' => 'user',
                        'content' => $message->prompt
                    ];
                }

                $hasError = false;
                try {
                    $answer = $serviceApi->chatCompletion($chatMessages);
                } catch (\Throwable $t) {
                    $answer = $t->getMessage();
                    $hasError = true;
                }

                $obj = new \stdClass();
                $obj->id = $message->id;
                $obj->answer = $answer;
                $obj->times = $message->times + 1;

                $obj->is_complete = 1;
                if ($hasError && $obj->times < $configApi->times) {
                    $obj->is_complete = 0;
                    $incomplete++;
                }

                $obj->update_time = date('Y-m-d H:i:s');
                $db->update('openai_chat_completion_message', $obj);

                $obj = new \stdClass();
                $obj->id = $message->chat_completion_id;
                $obj->update_time = date('Y-m-d H:i:s');
                $db->update('openai_chat_completion', $obj);
            }

            $t1 = time();
            if ($t1 - $t0 > $this->timeout) {
                break;
            }

            if ($incomplete > 0) {
                if (Be::getRuntime()->isSwooleMode()) {
                    \Swoole\Coroutine::sleep(10);
                } else {
                    sleep(10);
                }
            }

        } while ($incomplete > 0);

    }


}
