<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-17
 * Time: 下午7:56
 */

namespace rabbit\governance\balancer;

/**
 * Interface BalancerSelectInterface
 * @package rabbit\governance\balancer
 */
interface BalancerSelectInterface
{
    /**
     * @param string $service
     * @return mixed
     */
    public function select(string $service): BalancerInterface;

    /**
     * @param string $service
     * @param BalancerInterface $balancer
     * @return mixed
     */
    public function setBalancer(string $service, BalancerInterface $balancer): void;
}