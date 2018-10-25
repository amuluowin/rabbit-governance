<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 18-5-14
 * Time: 下午11:16
 */

namespace rabbit\governance\provider;

use rabbit\core\ObjectFactory;
use rabbit\core\Timer;
use rabbit\governance\Governance;
use rabbit\process\AbstractProcess;
use rabbit\process\Process;

class ProviderProcess extends AbstractProcess
{
    /**
     * @var int
     */
    private $ticket = 1;

    public function run(Process $process): void
    {
        $this->register();
    }

    /**
     *
     */
    public function register()
    {
        /** @var ProviderInterface $provider */
        if (($provider = ObjectFactory::get('provider', false)) && !$provider->registerService()) {
            /** @var Timer $timer */
            if (($timer = ObjectFactory::get('timer', false)) !== null) {
                $timer->addAfterTimer('consul', $this->ticket * 1000, [$this, 'register']);
            } else {
                swoole_timer_after($this->ticket * 1000, [$this, 'register']);
            }
        }
    }
}