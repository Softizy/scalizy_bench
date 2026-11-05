<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ScalizyRemoteWidgetBlock implements ScalizyFrontBlock
{
    public function __construct(Module $module)
    {
    }

    public static function settings()
    {
        return ['url' => ''];
    }

    public function render(array $settings)
    {
        $widget = Tools::file_get_contents($settings['url']);

        return false === $widget ? '' : $widget;
    }
}
