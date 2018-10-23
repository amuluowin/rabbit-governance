<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/11
 * Time: 20:18
 */

namespace rabbit\governance\trace\exporter;

use rabbit\App;
use Yii;
use yii\base\Component;

/**
 * Class SeaslogExporter
 * @package rabbit\governance\exporter
 */
class SeaslogExporter implements ExportInterface
{
    /**
     * @param string $data
     * @param string|null $key
     */
    public function export(string $data, string $key = null): void
    {
        App::info($data, 'trace');
    }
}