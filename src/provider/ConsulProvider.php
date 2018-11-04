<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-14
 * Time: 下午9:28
 */

namespace rabbit\governance\provider;

use rabbit\App;
use rabbit\core\ObjectFactory;
use rabbit\helper\JsonHelper;
use rabbit\server\Server;
use Swlib\Saber;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConsulProvider
 * @package rabbit\governance\provider
 */
class ConsulProvider implements ProviderInterface
{
    use ProviderTrait;
    /**
     * Register path
     */
    const REGISTER_PATH = '/v1/agent/service/register';

    /**
     * Discovery path
     */
    const DISCOVERY_PATH = '/v1/health/service/';

    const DEREGISTER_PATH = '/v1/agent/service/deregister/';

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
     * @var Saber
     */
    private $client;

    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var string
     */
    private $address = "http://consul:8500";

    /**
     * ConsulProvider constructor.
     * @param RequestInterface $client
     * @throws \Exception
     */
    public function __construct()
    {
        $this->services = array_keys(ObjectFactory::get('rpc.services'));
        $this->output = ObjectFactory::get('output', false, App::getLogger());
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
        $url = $this->getDiscoveryUrl($serviceName);
        $nodes = [];
        /**
         * @var Saber\Response $response
         */
        $response = $this->client->get($url);
        if ($response->getStatusCode() === 200) {
            $services = $response->getParsedJsonArray();
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
                                    $url = sprintf('%s%s%s', $this->address, self::DEREGISTER_PATH, $check['ServiceID']);
                                    $this->deRegisterService($url);
                                }
                            }
                        }
                    }
                }
            } else {
                $this->output->writeln(sprintf("can not find service %s from consul:%s", $serviceName, $this->address));
            }
        } else {
            $this->output->writeln(sprintf("consul:%s:%d error,message=", $response->getContent()));
        }
        return $nodes;
    }

    /**
     * @return bool
     */
    public function registerService(): bool
    {
        $url = sprintf('%s%s', $this->address, self::REGISTER_PATH);
        $result = true;
        /**
         * @var Server $rpcserver
         */
        $rpcserver = ObjectFactory::get('rpcserver');
        $rpchost = ObjectFactory::get('rpc.host');
        $appName = ObjectFactory::get('appName');
        foreach ($this->services as $service) {
            $id = sprintf('%s-%s-%s', $appName, $service, $rpchost);
            $this->register['ID'] = $id;
            $this->register['Name'] = $service;
            $this->register['Port'] = $rpcserver->getPort();
            $this->register['Check']['id'] = $id;
            $this->register['Check']['tcp'] = sprintf('%s:%d', $rpchost, $rpcserver->getPort());
            $this->register['Check']['name'] = $service;
            $result &= $this->putService($url);
        }

        return $result;
    }

    /**
     * @param string $url
     * @return bool
     */
    private function putService(string $url): bool
    {
        /**
         * @var Saber\Response $response
         */
        $response = $this->client->put($url, $this->register);
        $output = 'RPC register service %s %s by consul tcp=%s:%d.';
        if ($response->getStatusCode() === 200) {
            $this->output->writeln(sprintf($output, $this->register['Name'], 'success', $this->register['Address'], $this->register['Port']));
            return true;
        } else {
            $this->output->writeln(sprintf($output . 'error=%s', $this->register['Name'], 'failed', $this->register['Address'], $this->register['Port'], $response->getContent()));
            return false;
        }
    }

    /**
     * @param string $url
     * @return bool
     */
    private function deRegisterService(string $url): bool
    {
        /**
         * @var Saber\Response $response
         */
        $response = $this->client->put($url);
        $output = 'RPC deregister service %s %s by consul tcp=%s:%d.';
        if ($response->getStatusCode() === 200) {
            $this->output->writeln(sprintf($output, $this->register['Name'], 'success', $this->register['Address'], $this->register['Port']));
            return true;
        } else {
            $this->output->writeln(sprintf($output . 'error=%s', $this->register['Name'], 'failed', $this->register['Address'], $this->register['Port'], $response->getContent()));
            return false;
        }
    }

    /**
     * @param string $serviceName
     * @param string $preFix
     * @return string
     */
    private function getDiscoveryUrl(string $serviceName): string
    {
        $query = array_filter([
            'passing' => $this->discovery['passing'],
            'dc' => $this->discovery['dc'],
            'near' => $this->discovery['near'],
        ]);

        if (!empty($this->register['Tags'])) {
            $query['tag'] = $this->register['Tags'];
        }

        $queryStr = http_build_query($query);
        $path = sprintf('%s%s', self::DISCOVERY_PATH, $serviceName);

        return sprintf('%s%s?%s', $this->address, $path, $queryStr);
    }
}