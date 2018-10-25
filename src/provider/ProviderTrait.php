<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-16
 * Time: 下午5:49
 */

namespace rabbit\governance\provider;

use rabbit\core\ObjectFactory;

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
    private $cache;

    /**
     * @param string $service
     * @return array
     */
    public function getServiceFromCache(string $service): array
    {
        $result = $this->cache->get($service);
        return is_array($result) ? $result : [];
    }

    /**
     * @param array $services
     * @throws \Exception
     */
    protected function setServiceToCache(array $services): void
    {
        if ($services && is_array($services)) {
            foreach ($services as $service => $node) {
                $this->cache->set($service, $node, $this->duration);
            }
        }
    }

    /**
     * @param string $service
     * @return bool
     * @throws \Exception
     */
    public function delService(string $service): bool
    {
        return $this->cache->delete($service);
    }
}