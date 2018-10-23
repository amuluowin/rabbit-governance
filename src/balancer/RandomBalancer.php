<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-17
 * Time: 下午7:43
 */

namespace rabbit\governance\balancer;

/**
 * Class RandomBalancer
 * @package rabbit\governance\balancer
 */
class RandomBalancer implements BalancerInterface
{
    /**
     * @param array $serviceList
     * @param mixed ...$params
     * @return mixed
     */
    public function getCurrentService(array $serviceList): string
    {
        $randIndex = array_rand($serviceList);
        return $serviceList[$randIndex];
    }
}