<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-16
 * Time: 下午5:49
 */

namespace rabbit\governance\provider;

/**
 * Class BaseProvider
 * @package rabbit\governance\provider
 */
trait ProviderTrait
{
    /**
     * @var int
     */
    private $duration = 10;

    /**
     * @var
     */
    private $cache = [];

    /**
     * @param string $service
     * @return array
     */
    public function getServiceFromCache(string $service): array
    {
        if (isset($this->cache[$service])) {
            list($node, $expire) = $this->cache[$service];
            if (time() < $expire) {
                return $node;
            } else {
                $this->delService($service);
            }
        }
        return [];
    }

    /**
     * @param array $services
     * @throws \Exception
     */
    protected function setServiceToCache(array $services): void
    {
        if ($services && is_array($services)) {
            foreach ($services as $service => $node) {
                $this->cache[$service] = [$node, time() + $this->duration];
            }
        }
    }

    /**
     * @param string $service
     */
    public function delService(string $service): void
    {
        if (isset($this->cache[$service])) {
            unset($this->cache[$service]);
        }
    }
}