<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_0($module)
{
    Configuration::deleteByName('SCALIZY_PRODUCT_RANKING_PRODUCTS');
    Configuration::updateValue('SCALIZY_PRODUCT_RANKING_MS', 300);
    Configuration::updateValue('SCALIZY_POPULAR_PRODUCTS_ENABLED', 0);
    Configuration::updateValue('SCALIZY_POPULAR_PRODUCTS_HOOK', 'displayFooterAfter');
    Configuration::updateValue('SCALIZY_POPULAR_PRODUCTS_MS', 400);
    if (Configuration::get('SCALIZY_PRODUCT_RANKING_ENABLED')) {
        (new ScalizyProductRankingBlock($module))->enable(['ms' => 300]);
    }

    return $module->registerHook(Scalizy_Bench::HOOKS);
}
