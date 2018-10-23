<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-17
 * Time: 下午8:35
 */

namespace rabbit\governance\trace\exporter;

/**
 * Interface ExportInterface
 * @package rabbit\governance\exporter
 */
interface ExportInterface
{
    /**
     * @param string $data
     * @param string|null $key
     */
    public function export(string $data, string $key = null): void;
}