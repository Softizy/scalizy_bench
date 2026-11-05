<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_0($module)
{
    Configuration::deleteByName('SCALIZY_FRONT_BLOCKS');

    return $module->registerHook(Scalizy_Bench::HOOKS) && $module->resetBlocks();
}
