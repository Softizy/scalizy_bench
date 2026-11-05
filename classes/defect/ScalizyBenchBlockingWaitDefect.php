<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ScalizyBenchBlockingWaitDefect implements ScalizyBenchDefect
{
    public function __construct(Module $module)
    {
    }

    public static function parameters()
    {
        return ['ms' => 400];
    }

    public function produce(array $parameters)
    {
        usleep($parameters['ms'] * 1000);

        return '';
    }
}
