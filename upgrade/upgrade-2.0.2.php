<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_2($module)
{
    if (260 === (int) Configuration::get('SCALIZY_BENCH_TTFB_ROUNDS')) {
        Configuration::updateValue(
            'SCALIZY_BENCH_TTFB_ROUNDS',
            Scalizy_Bench::defaultValue(Scalizy_Bench::SIMULATIONS['SCALIZY_BENCH_TTFB']['settings']['rounds'])
        );
    }

    return true;
}
