<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-17
 * Time: 下午7:50
 */

namespace rabbit\governance\balancer;

use rabbit\core\ObjectFactory;

/**
 * Class BalancerSelecter
 * @package rabbit\governance\balancer
 */
class BalancerSelecter implements BalancerSelectInterface
{
    /**
     * @var BalancerInterface
     */
    private $defaultBalancer;
    /**
     * @var array
     */
    private $serviceBalance = [];

    /**
     * BalancerSelecter constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $services = array_keys(ObjectFactory::get('rpc.services'));
        foreach ($services as $service) {
            $this->serviceBalance[$service] = $this->defaultBalancer;
        }
    }

    /**
     * @param string $service
     * @return BalancerInterface
     */
    public function select(string $service): BalancerInterface
    {
        return isset($this->serviceBalance[$service]) ? $this->serviceBalance[$service] : $this->defaultBalancer;
    }

    /**
     * @param string $service
     * @param BalancerInterface $balancer
     */
    public function setBalancer(string $service, BalancerInterface $balancer): void
    {
        $this->serviceBalance[$service] = $balancer;
    }
}