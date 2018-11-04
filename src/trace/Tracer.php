<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-13
 * Time: 下午10:35
 */

namespace rabbit\governance\trace;

use rabbit\contract\IdGennerator;
use rabbit\core\Context;
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
    private $isTrace = true;

    /**
     * @var ExportInterface
     */
    private $exporter;

    /**
     * @var IdGennerator
     */
    private $idCreater;

    /**
     * Tracer constructor.
     * @param ExportInterface $exporter
     */
    public function __construct(ExportInterface $exporter, IdGennerator $idCreater)
    {
        $this->exporter = $exporter;
        $this->idCreater = $idCreater;
    }

    /**
     * @param int $traceId
     * @param array $collect
     * @return array
     * @throws \Exception
     */
    public function getCollect(array $newCollect): array
    {
        if (($collect = Context::get('collect')) === null) {
            $collect = [
                'traceId' => $this->idCreater->create(),
                'parentId' => 0,
                'spanId' => 0
            ];
        } else {
            $collect['parentId'] = $collect['spanId'];
            $collect['spanId']++;
        }
        $collect['sendTime'] = floor(microtime(true) * 1000);
        $collect['host'] = ObjectFactory::get('rpc.host');
        $collect['port'] = 80;
        $collect = ArrayHelper::merge($collect, $newCollect);
        Context::set('collect', $collect);
        return $collect;
    }

    /**
     * @param string $traceId
     * @param array $collect
     */
    public function addCollect(array $newCollect): void
    {
        $collect = Context::get('collect');
        $collect = ArrayHelper::merge($collect, $newCollect);
        Context::set('collect', $collect);
    }

    /**
     * @param string $traceId
     */
    public function flushCollect(): void
    {
        if ($this->exporter instanceof ExportInterface) {
            $collect = Context::get('collect');
            $this->exporter->export(JsonHelper::encode($collect, JSON_UNESCAPED_UNICODE));
            Context::delete('collect');
        }
    }
}