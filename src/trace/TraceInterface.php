<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-14
 * Time: 上午12:00
 */

namespace rabbit\governance\trace;

/**
 * Interface TraceInterface
 * @package rabbit\governance\trace
 */
interface TraceInterface
{
    /**
     * @param int $traceId
     * @param array $collect
     * @return array
     */
    public function getCollect(int $traceId,array $collect):array;

    /**
     * @param int $traceId
     * @param array $collect
     * @return mixed
     */
    public function addCollect(int $traceId, array $collect):void;

    /**
     * @param int $traceId
     */
    public function flushCollect(int $traceId):void;

    /**
     * @param int|null $traceId
     */
    public function release(?int $traceId):void;
}