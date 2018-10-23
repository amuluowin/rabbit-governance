<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-14
 * Time: 下午9:28
 */

namespace rabbit\governance\provider;

use Psr\Http\Message\RequestInterface;
use rabbit\core\ObjectFactory;
use rabbit\server\Server;

class ConsulProvider extends BaseProvider implements ProviderInterface
{
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
     * @var int
     */
    private $checkType = 1;

    /**
     * @var string
     */
    private $servicePrefix = 'service-';

    /**
     * @var array
     */
    private $services = [];

    /**
     * @var RequestInterface
     */
    private $client;

    /**
     * ConsulProvider constructor.
     * @param RequestInterface $client
     * @throws \Exception
     */
    public function __construct(RequestInterface $client)
    {
        $this->services = array_keys(ObjectFactory::get('rpc.services'));
        $this->client = $client;
    }

    /**
     * @param string $serviceName
     * @param string $preFix
     * @return array|mixed
     */
    public function getServices(string $serviceName): array
    {
        $nodes = $this->getServiceFromCache($serviceName);
        if ($nodes) {
            return $nodes;
        } else {
            $nodes = $this->get($serviceName, $preFix);
            $this->setServiceToCache([$serviceName => $nodes]);
            return $nodes;
        }
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
         * @var Response $response
         */
        $response = $this->client->get($url)->send();
        if ($response->getIsOk()) {
            $services = $response->getData();
            if (is_array($services)) {
                // 数据格式化
                foreach ($services as $service) {
                    if (!isset($service['Service'])) {
                        Yii::warning("consul[Service] 服务健康节点集合，数据格式不不正确，Data=" . VarDumper::export($services));
                        continue;
                    }
                    $serviceInfo = $service['Service'];
                    if (!isset($serviceInfo['Address'], $serviceInfo['Port'])) {
                        Yii::warning("consul[Address] Or consul[Port] 服务健康节点集合，数据格式不不正确，Data=" . VarDumper::export($services));
                        continue;
                    }
                    if (isset($service['Checks'])) {
                        foreach ($service['Checks'] as $check) {
                            if ($check['ServiceName'] === $preFix . $serviceName) {
                                if ($check['Status'] === 'passing') {
                                    $address = $serviceInfo['Address'];
                                    $port = $serviceInfo['Port'];
                                    $nodes[] = [$address, $port];
                                } else {
                                    $url = sprintf('%s:%d%s%s', $this->client->address, $this->client->port, self::DEREGISTER_PATH, $check['ServiceID']);
                                    $this->deRegisterService($url);
                                }
                            }
                        }
                    }
                }
            } else {
                Output::writeln(sprintf("can not find service %s from consul:%s:%d", $serviceName, $this->client->address, $this->client->port), Output::LIGHT_RED);
            }
        } else {
            Output::writeln(sprintf("consul:%s:%d error,message=", $response->getContent()), Output::LIGHT_RED);
        }
        return $nodes;
    }

    /**
     * @return bool
     */
    public function registerService(): bool
    {
        $url = sprintf('%s:%d%s', $this->client->address, $this->client->port, self::REGISTER_PATH);
        $result = true;
        /**
         * @var Server $rpcserver
         */
        $rpcserver = ObjectFactory::get('rpcserver');
        $rpchost = ObjectFactory::get('rpc.host');
        $appName = ObjectFactory::get('appName');
        foreach ($this->services as $service) {
            $service = $this->servicePrefix . $service;
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
         * @var Response $response
         */
        $response = Yii::$app->httpclient->put($url, $this->register)->setFormat(Client::FORMAT_JSON)->send();
        $output = 'RPC register service %s %s by consul tcp=%s:%d.';
        if ($response->getIsOk()) {
            Output::writeln(sprintf($output, $this->register['Name'], 'success', $this->register['Address'], $this->register['Port']), Output::LIGHT_GREEN);
            return true;
        } else {
            Output::writeln(sprintf($output . 'error=%s', $this->register['Name'], 'failed', $this->register['Address'], $this->register['Port'], $response->getContent()), Output::LIGHT_RED);
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
         * @var Response $response
         */
        $response = Yii::$app->httpclient->put($url)->setFormat(Client::FORMAT_JSON)->send();
        $output = 'RPC deregister service %s %s by consul tcp=%s:%d.';
        if ($response->getIsOk()) {
            Output::writeln(sprintf($output, $this->register['Name'], 'success', $this->register['Address'], $this->register['Port']), Output::LIGHT_GREEN);
            return true;
        } else {
            Output::writeln(sprintf($output . 'error=%s', $this->register['Name'], 'failed', $this->register['Address'], $this->register['Port'], $response->getContent()), Output::LIGHT_RED);
            return false;
        }
    }

    /**
     * @param string $serviceName
     * @param string $preFix
     * @return string
     */
    private function getDiscoveryUrl(string $serviceName, string $preFix): string
    {
        $serviceName = $preFix . $serviceName;
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

        return sprintf('%s:%d%s?%s', $this->client->address, $this->client->port, $path, $queryStr);
    }
}