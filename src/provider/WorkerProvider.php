<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/24
 * Time: 3:23
 */

namespace rabbit\governance\provider;


use GuzzleHttp\DefaultHandler;
use rabbit\server\WorkerHandlerInterface;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;

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
        DefaultHandler::setDefaultHandler(SwooleHandler::class);
    }

}