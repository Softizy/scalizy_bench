<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

interface ScalizyFrontBlock
{
    /**
     * @return array<string, int|string> settings of the block, with their default value
     */
    public static function settings();

    /**
     * @param array<string, int|string> $settings
     *
     * @return string
     */
    public function render(array $settings);
}
