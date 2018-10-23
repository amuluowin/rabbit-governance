<?php

namespace rabbit\governance\balancer;

/**
 * Class RoundRobinBalancer
 * @package rabbit\governance\balancer
 */
class RoundRobinBalancer implements BalancerInterface
{
    /**
     * @var int
     */
    private $lastIndex = 0;

    /**
     * @param array $serviceList
     * @return string
     */
    public function getCurrentService(array $serviceList): string
    {
        $currentIndex = $this->lastIndex + 1;
        if ($currentIndex + 1 > count($serviceList)) {
            $currentIndex = 0;
        }

        $this->lastIndex = $currentIndex;
        return $serviceList[$currentIndex];
    }
}
