<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_0($module)
{
    $previous = [
        'SCALIZY_FEATURED_PRODUCT' => ['PRODUCT_ID', 'SLOTS'],
        'SCALIZY_POPULAR_PRODUCTS' => ['VIEWS'],
        'SCALIZY_PRODUCT_RANKING' => ['PRODUCTS', 'ROUNDS'],
        'SCALIZY_REMOTE_WIDGET' => ['URL'],
    ];
    foreach ($previous as $config => $settings) {
        foreach (array_merge(['ENABLED', 'HOOK'], $settings) as $name) {
            Configuration::deleteByName($config . '_' . $name);
        }
    }
    ScalizyPopularProductsBlock::dropTable();

    return $module->resetSimulations();
}
