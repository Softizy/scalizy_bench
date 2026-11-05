<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

interface ScalizyBenchDefect
{
    /**
     * @return array<string, int> parameters of the defect, with their default value
     */
    public static function parameters();

    /**
     * @param array<string, int> $parameters
     *
     * @return string
     */
    public function produce(array $parameters);
}
