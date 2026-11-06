<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_0($module)
{
    foreach (array_keys(Scalizy_Bench::HOOKS) as $hook) {
        $module->unregisterHook($hook);
    }
    $previous = [
        'SCALIZY_FRONT_BLOCKS', 'SCALIZY_FRONT_WIDGET_HTML', 'SCALIZY_FRONT_WIDGET_FETCHED_AT',
        'SCALIZY_FEATURED_PRODUCT', 'SCALIZY_POPULAR_PRODUCTS', 'SCALIZY_PRODUCT_RANKING', 'SCALIZY_REMOTE_WIDGET',
    ];
    foreach ($previous as $prefix) {
        Db::getInstance()->delete('configuration', 'name = \'' . pSQL($prefix) . '\' OR name LIKE \'' . pSQL($prefix) . '\_%\'');
    }
    Configuration::loadConfiguration();
    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'scalizy_product_view`');

    return $module->resetSimulations();
}
