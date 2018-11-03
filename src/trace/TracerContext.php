<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/3
 * Time: 15:30
 */

namespace rabbit\governance\trace;

use rabbit\core\ContextTrait;

/**
 * Class TracerContext
 * @package rabbit\governance\trace
 */
class TracerContext
{
    use ContextTrait;

    /**
     * @var array
     */
    private static $context = [];
}