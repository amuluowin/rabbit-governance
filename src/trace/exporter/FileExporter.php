<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/20
 * Time: 14:16
 */

namespace rabbit\governance\trace\exporter;

use rabbit\files\FileTarget;

/**
 * Class FileExporter
 * @package rabbit\governance\exporter
 */
class FileExporter implements ExportInterface
{
    /**
     * @var FileTarget $target
     */
    private $target;

    public function __construct(FileTarget $target)
    {
        $this->target = $target;
    }

    /**
     * @param string $data
     * @param string|null $key
     */
    public function export(string $data, string $key = null): void
    {
        $this->target->export($data);
    }
}