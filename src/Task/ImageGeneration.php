<?php

namespace Be\App\Openai\Task;

use Be\Be;
use Be\Task\Task;

/**
 * @BeTask("调用API生成图像", schedule="* * * * *")
 */
class ImageGeneration extends Task
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
            $sql = 'SELECT * FROM openai_image_generation WHERE is_complete = 0';
            $imageGenerations = $db->getObjects($sql);
            foreach ($imageGenerations as $imageGeneration) {

                $hasError = false;
                try {
                    $options = unserialize($imageGeneration->options);
                    $answer = $serviceApi->imageGeneration($imageGeneration->prompt, $options);
                } catch (\Throwable $t) {
                    $answer = $t->getMessage();
                    $hasError = true;
                }

                $obj = new \stdClass();
                $obj->id = $imageGeneration->id;
                $obj->url = $answer;
                $obj->times = $imageGeneration->times + 1;

                $obj->is_complete = 1;
                if ($hasError && $obj->times < $configApi->times) {
                    $obj->is_complete = 0;
                    $incomplete++;
                }

                $obj->update_time = date('Y-m-d H:i:s');
                $db->update('openai_image_generation', $obj);
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
