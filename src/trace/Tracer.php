<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-13
 * Time: 下午10:35
 */

namespace rabbit\governance\trace;

use rabbit\core\ObjectFactory;
use rabbit\governance\trace\exporter\ExportInterface;
use rabbit\helper\ArrayHelper;
use rabbit\helper\JsonHelper;

/**
 * Class Tracer
 * @package rabbit\governance\trace
 */
class Tracer implements TraceInterface
{
    /**
     * @var bool
     */
    public $isTrace = true;

    /**
     * @var array
     */
    public $collect = [];

    /**
     * @var ExportInterface
     */
    public $exporter;

    /**
     * Tracer constructor.
     * @param ExportInterface $exporter
     */
    public function __construct(ExportInterface $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * @param string $traceId
     * @param array $collect
     * @return array
     */
    public function getCollect(string $traceId, array $collect): array
    {
        if (isset($this->collect[$traceId])) {
            $this->collect[$traceId]['parentId'] = $this->collect[$traceId]['spanId'];
            $this->collect[$traceId]['spanId']++;
        } else {
            $this->collect[$traceId] = [
                'traceId' => $traceId,
                'spanId' => 0
            ];
        }
        $this->collect[$traceId]['time'] = time();
        $this->collect[$traceId]['host'] = ObjectFactory::get('rpc.host');
        $this->collect[$traceId]['port'] = 80;
        $this->collect[$traceId] = ArrayHelper::merge($this->collect[$traceId], $collect);
        return $this->collect[$traceId];
    }

    /**
     * @param string $traceId
     * @param array $collect
     */
    public function addCollect(string $traceId, array $collect): void
    {
        $this->collect[$traceId] = ArrayHelper::merge($this->collect[$traceId], $collect);
    }

    /**
     * @param string $traceId
     */
    public function flushCollect(string $traceId): void
    {
        if ($this->exporter instanceof ExportInterface) {
            $this->exporter->export(JsonHelper::encode($this->collect[$traceId], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * @param int|null $traceId
     */
    public function release(?int $traceId = null)
    {
        if ($traceId) {
            unset($this->collect[$traceId]);
        } else {
            $this->collect = [];
        }
    }
}