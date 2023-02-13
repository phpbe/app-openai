<?php

namespace Be\App\Openai\Task;

use Be\Be;
use Be\Task\Task;

/**
 * @BeTask("调用API应签", schedule="* * * * *")
 */
class TextCompletion extends Task
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
    protected $timeout = 600;

    public function execute()
    {
        $configApi = Be::getConfig('App.Openai.Api');
        $serviceApi = Be::getService('App.Openai.Api');

        $db = Be::getDb();

        $t0 = time();
        do {
            $incomplete = 0;
            $sql = 'SELECT * FROM openai_text_completion_message WHERE is_complete = 0';
            $messages = $db->getObjects($sql);
            foreach ($messages as $message) {

                // 交互式会话
                if ($message->line > 1) {
                    $prompt = '';
                    $sql = 'SELECT * FROM openai_text_completion_message WHERE text_completion_id = ? ORDER BY line ASC';
                    $messages = $db->getObjects($sql, [$message->text_completion_id]);
                    foreach ($messages as $m) {
                        $prompt .= 'Q: ' . $m->prompt . "\n\n";
                        $prompt .= 'A: ' . $m->answer . "\n\n";
                    }

                } else {
                    $prompt = $message->prompt;
                }

                $hasError = false;
                try {
                    $answer = $serviceApi->textCompletion($prompt);
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
                $db->update('openai_text_completion_message', $obj);

                $obj = new \stdClass();
                $obj->id = $message->text_completion_id;
                $obj->update_time = date('Y-m-d H:i:s');
                $db->update('openai_text_completion', $obj);
            }

            $t1 = time();
            if ($t1 - $t0 > $this->timeout) {
                break;
            }

            if ($incomplete > 0) {
                if (Be::getRuntime()->isSwooleMode()) {
                    \Swoole\Coroutine::sleep(1);
                } else {
                    sleep(1);
                }
            }

        } while ($incomplete > 0);


        // 结整超过1天的的会话
        $sql = 'SELECT * FROM openai_text_completion WHERE is_complete = 0';
        $textCompletions = $db->getObjects($sql);
        foreach ($textCompletions as $textCompletion) {
            if (strlen($textCompletion->update_time) - time() > 3600) {
                $obj = new \stdClass();
                $obj->id = $textCompletion->text_completion_id;
                $obj->is_complete = 1;
                $obj->update_time = date('Y-m-d H:i:s');
                $db->update('openai_text_completion', $obj);
            }
        }

    }


}
