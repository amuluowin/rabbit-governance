<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-17
 * Time: 下午7:43
 */

namespace rabbit\governance\balancer;

/**
 * Interface BalancerInterface
 * @package rabbit\governance\balancer
 */
interface BalancerInterface
{
    /**
     * @param array $serviceList
     * @param mixed ...$params
     * @return mixed
     */
    public function getCurrentService(array $serviceList): string;
}