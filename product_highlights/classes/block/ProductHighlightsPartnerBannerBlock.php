<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductHighlightsPartnerBannerBlock implements ProductHighlightsBlock
{
    const BANNER_HTML = 'PRODUCT_HIGHLIGHTS_BANNER_HTML';
    const BANNER_FETCHED_AT = 'PRODUCT_HIGHLIGHTS_BANNER_FETCHED_AT';

    public function __construct(Module $module)
    {
    }

    public function render(array $settings)
    {
        $banner = Tools::file_get_contents($settings['url']);
        if (false === $banner) {
            return (string) Configuration::get(self::BANNER_HTML);
        }

        Configuration::updateValue(self::BANNER_HTML, $banner, true);
        Configuration::updateValue(self::BANNER_FETCHED_AT, time());

        return $banner;
    }
}
