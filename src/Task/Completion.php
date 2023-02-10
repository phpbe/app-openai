<?php
namespace Be\App\Openai\Task;

use Be\Be;
use Be\Task\Task;

/**
 * @BeTask("调用API解决问签")
 */
class Completion extends Task
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
        $sql = 'SELECT * FROM openai_completion_session_message WHERE is_complete = 0';
        $messages = $db->getYieldObjects($sql);

        do {
            $incomplete = 0;
            foreach ($messages as $message) {

                // 交互式会话
                if ($message->line > 1) {
                    $prompt = '';
                    $sql = 'SELECT * FROM openai_completion_session_message WHERE completion_session_id = ? ORDER BY line ASC';
                    $messages = $db->getObjects($sql, [$message->completion_session_id]);
                    foreach ($messages as $m) {
                        $prompt .= 'Q: ' . $m->question . "\n\n";
                        $prompt .= 'A: ' . $m->answer . "\n\n";
                    }

                } else {
                    $prompt = $message->question;
                }

                $hasError = false;
                try {
                    $answer = $serviceApi->completion($prompt);
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
                $db->update('openai_completion_session_message', $obj);

                $obj = new \stdClass();
                $obj->id = $message->completion_session_id ;
                $obj->update_time = date('Y-m-d H:i:s');
                $db->update('openai_completion_session', $obj);
            }

        } while($incomplete > 0);


        // 结整超过1天的的会话
        $sql = 'SELECT * FROM openai_completion_session WHERE is_complete = 0';
        $sessions = $db->getYieldObjects($sql);
        foreach ($sessions as $session) {
            if (strlen($session->update_time) - time() > 3600) {
                $obj = new \stdClass();
                $obj->id = $message->completion_session_id ;
                $obj->is_complete = 1;
                $obj->update_time = date('Y-m-d H:i:s');
                $db->update('openai_completion_session', $obj);
            }
        }

    }


}
