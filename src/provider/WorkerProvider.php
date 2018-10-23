<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/24
 * Time: 3:23
 */

namespace rabbit\governance\provider;


use rabbit\server\WorkerHandlerInterface;

/**
 * Class WorkerProvider
 * @package rabbit\governance\provider
 */
class WorkerProvider implements WorkerHandlerInterface
{
    /**
     * @param int $worker_id
     */
    public function handle(int $worker_id): void
    {
        // TODO: Implement handle() method.
    }

}