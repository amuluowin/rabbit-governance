<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-17
 * Time: 下午8:36
 */

namespace rabbit\governance\trace\exporter;

use rabbit\core\ObjectFactory;

/**
 * Class KafkaExporter
 * @package rabbit\governance\exporter
 */
class KafkaExporter implements ExportInterface
{
    /**
     * @var string
     */
    public $topic = 'trace';

    /**
     * @param $data
     * @param string|null $key
     */
    public function export(string $data, string $key = null): void
    {
        /**
         * @var Kafka $kafka
         */
        if (($kafka = ObjectFactory::get('kafka', false)) !== null) {
            $kafka->send([
                [
                    'topic' => $this->topic,
                    'value' => $data,
                    'key' => $key,
                ],
            ]);
        }
    }
}