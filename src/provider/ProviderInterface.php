<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-14
 * Time: 下午9:26
 */

namespace rabbit\governance\provider;

/**
 * Interface ProviderInterface
 * @package rabbit\governance\provider
 */
interface ProviderInterface
{
    /**
     * @return bool
     */
    public function registerService(): bool;

    /**
     * @param string $serviceName
     * @param string $preFix
     * @return mixed
     */
    public function getServices(string $serviceName): array;
}