<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-14
 * Time: 下午11:16
 */

namespace rabbit\governance\provider;

use rabbit\core\ObjectFactory;
use rabbit\governance\Governance;

class ProviderProcess extends BaseProcess
{
    /**
     * @var int
     */
    private $ticket = 10;

    /**
     *
     */
    public function register()
    {
        if (($provider = ObjectFactory::get('provider', null, false)) && !$provider->registerService()) {
            swoole_timer_after(1000, [$this, 'register']);
        }
    }
}