<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-14
 * Time: 下午9:28
 */

namespace rabbit\governance\provider;

use rabbit\App;
use rabbit\consul\ConsulResponse;
use rabbit\consul\ServiceFactory;
use rabbit\consul\Services\Agent;
use rabbit\consul\Services\Health;
use rabbit\core\ObjectFactory;
use rabbit\helper\JsonHelper;
use rabbit\server\Server;

/**
 * Class ConsulProvider
 * @package rabbit\governance\provider
 */
class ConsulProvider implements ProviderInterface
{
    use ProviderTrait;

    /**
     * @var array
     */
    private $register;

    /**
     * @var array
     */
    private $discovery;

    /**
     * @var array
     */
    private $services = [];

    /**
     * @var ServiceFactory
     */
    private $factory;
    /**
     * @var string
     */
    private $address = "http://consul:8500";

    /**
     * ConsulProvider constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->services = array_keys(ObjectFactory::get('rpc.services'));
    }

    /**
     * @param string $serviceName
     * @param string $preFix
     * @return array|mixed
     */
    public function getServices(string $serviceName): array
    {
        if ($this->cache) {
            $nodes = $this->getServiceFromCache($serviceName);
            if (!$nodes) {
                $nodes = $this->get($serviceName);
                $this->setServiceToCache([$serviceName => $nodes]);
            }
        } else {
            $nodes = $this->get($serviceName);
        }
        return $nodes;
    }

    /**
     * @param string $serviceName
     * @param string $preFix
     * @return array
     */
    private function get(string $serviceName)
    {
        $query = array_filter([
            'passing' => $this->discovery['passing'],
            'dc' => $this->discovery['dc'],
            'near' => $this->discovery['near'],
        ]);

        if (!empty($this->register['Tags'])) {
            $query['tag'] = $this->register['Tags'];
        }
        /** @var Health $health */
        $health = $this->factory->get('health');
        /** @var ConsulResponse $response */
        $response = $health->service($serviceName, $query);
        $nodes = [];
        if ($response->getStatusCode() === 200) {
            $services = $response->json();
            if ($services) {
                // 数据格式化
                foreach ($services as $service) {
                    if (!isset($service['Service'])) {
                        App::warning("consul[Service] 服务健康节点集合，数据格式不不正确，Data=" . JsonHelper::encode($services));
                        continue;
                    }
                    $serviceInfo = $service['Service'];
                    if (!isset($serviceInfo['Address'], $serviceInfo['Port'])) {
                        App::warning("consul[Address] Or consul[Port] 服务健康节点集合，数据格式不不正确，Data=" . JsonHelper::encode($services));
                        continue;
                    }
                    if (isset($service['Checks'])) {
                        foreach ($service['Checks'] as $check) {
                            if ($check['ServiceName'] === $serviceName) {
                                if ($check['Status'] === 'passing') {
                                    $address = $serviceInfo['Address'];
                                    $port = $serviceInfo['Port'];
                                    $nodes[] = $address . ":" . $port;
                                } else {
                                    $this->deRegisterService($check['ServiceID']);
                                }
                            }
                        }
                    }
                }
            } else {
                App::error(sprintf("can not find service %s from consul:%s", $serviceName, $this->address), 'consul');
            }
        } else {
            App::info(sprintf("consul:%s:%d error,message=", $response->getContent()), 'consul');
        }
        return $nodes;
    }

    /**
     * @return bool
     */
    public function registerService(): bool
    {
        $result = true;
        /**
         * @var Server $rpcserver
         */
        $rpcserver = ObjectFactory::get('rpcserver');
        $rpchost = ObjectFactory::get('rpc.host');
        $appName = ObjectFactory::get('appName');
        foreach ($this->services as $service) {
            $register = $this->register;
            $id = sprintf('%s-%s-%s', $appName, $service, $rpchost);
            $register['ID'] = $id;
            $register['Name'] = $service;
            $this->register['Port'] = $register['Port'] = $rpcserver->getPort();
            $register['Check']['id'] = $id;
            $register['Check']['tcp'] = sprintf('%s:%d', $rpchost, $rpcserver->getPort());
            $register['Check']['name'] = $service;
            $result &= $this->putService($register);
        }

        return $result;
    }

    /**
     * @param string $url
     * @return bool
     */
    private function putService(array $register): bool
    {
        /**
         * @var Agent $agent
         */
        $agent = $this->factory->get('agent');
        /** @var ConsulResponse $response */
        $response = $agent->registerService($register);
        $output = 'RPC register service %s %s by consul.';
        if ($response->getStatusCode() === 200) {
            App::info(sprintf($output, $register['Name'], 'success'), 'consul');
            return true;
        } else {
            App::error(sprintf($output . 'error=%s', $register['Name'], 'failed', $response->getBody()), 'consul');
            return false;
        }
    }

    /**
     * @param string $url
     * @return bool
     */
    private function deRegisterService(string $id): bool
    {
        /** @var Agent $agent */
        $agent = $this->factory->get('agent');
        /** @var ConsulResponse $response */
        $response = $agent->deregisterService($id);
        $output = 'RPC deregister service %s %s by consul.';
        if ($response->getStatusCode() === 200) {
            App::info(sprintf($output, $id, 'success'), 'consul');
            return true;
        } else {
            App::error($this->output->writeln(sprintf($output . 'error=%s', $id, 'failed', $response->getBody())), 'consul');
            return false;
        }
    }
}