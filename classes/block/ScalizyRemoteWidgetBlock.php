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
    const WIDGET_HTML = 'SCALIZY_FRONT_WIDGET_HTML';
    const WIDGET_FETCHED_AT = 'SCALIZY_FRONT_WIDGET_FETCHED_AT';

    public function __construct(Module $module)
    {
    }

    public static function settings()
    {
        return ['url' => ''];
    }

    public static function hooks()
    {
        return ['displayHeader', 'displayTop'];
    }

    public function render(array $settings)
    {
        $widget = Tools::file_get_contents($settings['url']);
        if (false === $widget) {
            return (string) Configuration::get(self::WIDGET_HTML);
        }

        Configuration::updateValue(self::WIDGET_HTML, $widget, true);
        Configuration::updateValue(self::WIDGET_FETCHED_AT, time());

        return $widget;
    }
}
