<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_1($module)
{
    Configuration::deleteByName('SCALIZY_POPULAR_PRODUCTS_MS');
    Configuration::deleteByName('SCALIZY_PRODUCT_RANKING_MS');
    Configuration::updateValue('SCALIZY_POPULAR_PRODUCTS_VIEWS', 3000000);
    Configuration::updateValue('SCALIZY_PRODUCT_RANKING_PRODUCTS', 200);
    Configuration::updateValue('SCALIZY_PRODUCT_RANKING_ROUNDS', 230);
    if (Configuration::get('SCALIZY_POPULAR_PRODUCTS_ENABLED')) {
        (new ScalizyPopularProductsBlock($module))->enable(['views' => 3000000]);
    }

    return true;
}
