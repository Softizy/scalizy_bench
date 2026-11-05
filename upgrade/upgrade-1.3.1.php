<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_1($module)
{
    Configuration::deleteByName('SCALIZY_PRODUCT_RANKING_REFINE_MS');
    Configuration::updateValue('SCALIZY_PRODUCT_RANKING_PRODUCTS', 200);
    Configuration::updateValue('SCALIZY_PRODUCT_RANKING_ROUNDS', 300);

    return true;
}
