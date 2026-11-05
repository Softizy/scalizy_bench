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
     * @return array<int, string> hooks the block can be displayed on
     */
    public static function hooks();

    /**
     * Prepares what the block reads once it is displayed, each time its settings are saved.
     *
     * @param array<string, int|string> $settings
     *
     * @return bool
     */
    public function enable(array $settings);

    /**
     * @param array<string, int|string> $settings
     *
     * @return string
     */
    public function render(array $settings);
}
