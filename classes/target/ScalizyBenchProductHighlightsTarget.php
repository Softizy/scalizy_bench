<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Degrades the product_highlights module: each simulation of the bench turns on one of its blocks, on the hook
 * and at the intensity chosen, through its own configuration. The faulty code is the module's, never the bench's.
 */
class ScalizyBenchProductHighlightsTarget
{
    const MODULE = 'product_highlights';
    const FEATURED = 'PRODUCT_HIGHLIGHTS_FEATURED';
    const POPULAR = 'PRODUCT_HIGHLIGHTS_POPULAR';
    const TRENDING = 'PRODUCT_HIGHLIGHTS_TRENDING';
    const BANNER = 'PRODUCT_HIGHLIGHTS_BANNER';
    const VIEW_TABLE = 'product_highlights_view';
    const FEATURED_PRODUCT_ID = 1;
    const RANKED_PRODUCTS = 200;
    const POPULAR_DAYS = 30;
    const POPULAR_SHOWN = 5;
    const VIEW_HISTORY_SECONDS = 7776000;
    const FIRST_VIEWS = 1000;

    /**
     * @param string $simulation key of Scalizy_Bench::SIMULATIONS
     * @param bool $enabled
     * @param string $hook
     * @param array<string, int|string> $settings
     */
    public function apply($simulation, $enabled, $hook, array $settings)
    {
        switch ($simulation) {
            case 'SCALIZY_BENCH_SQL_QUERIES':
                $this->setBlock(self::FEATURED, $enabled, $hook, ['PRODUCT_ID' => self::FEATURED_PRODUCT_ID, 'SLOTS' => $enabled ? $settings['queries'] : 0]);
                break;
            case 'SCALIZY_BENCH_SQL_TIME':
                $this->setBlock(self::POPULAR, $enabled, $hook, ['DAYS' => self::POPULAR_DAYS, 'SHOWN' => self::POPULAR_SHOWN]);
                $this->fillViews($enabled ? $settings['views'] : 0);
                break;
            case 'SCALIZY_BENCH_TTFB':
                $compute = $enabled && 'compute' === $settings['cause'];
                $remote = $enabled && 'remote' === $settings['cause'];
                $this->setBlock(self::TRENDING, $compute, $hook, ['PRODUCTS' => self::RANKED_PRODUCTS, 'PASSES' => $compute ? $settings['rounds'] : 0]);
                $this->setBlock(self::BANNER, $remote, $hook, ['URL' => $remote ? $settings['url'] : '']);
                break;
        }
    }

    private function setBlock($config, $enabled, $hook, array $settings)
    {
        Configuration::updateValue($config . '_ENABLED', $enabled ? 1 : 0);
        if (!$enabled) {
            return;
        }
        Configuration::updateValue($config . '_HOOK', $hook);
        foreach ($settings as $name => $value) {
            Configuration::updateValue($config . '_' . $name, $value);
        }
    }

    private function fillViews($views)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . self::VIEW_TABLE;
        $db->execute('TRUNCATE TABLE `' . $table . '`');
        if ($views < 1) {
            return;
        }

        $idShop = (int) Context::getContext()->shop->id;
        $products = array_map('intval', array_column($db->executeS('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_shop` = ' . $idShop . ' AND `active` = 1'), 'id_product'));
        if ([] === $products) {
            $products = [1];
        }
        $rows = [];
        for ($view = 0; $view < min(self::FIRST_VIEWS, $views); ++$view) {
            $rows[] = ['id_product' => $products[$view % count($products)], 'id_shop' => $idShop, 'date_add' => date('Y-m-d H:i:s', time() - mt_rand(0, self::VIEW_HISTORY_SECONDS))];
        }
        $db->insert(self::VIEW_TABLE, $rows);

        $filled = count($rows);
        while ($filled < $views) {
            $db->execute('INSERT INTO `' . $table . '` (`id_product`, `id_shop`, `date_add`) SELECT `id_product`, `id_shop`, NOW() - INTERVAL FLOOR(RAND() * ' . self::VIEW_HISTORY_SECONDS . ') SECOND FROM `' . $table . '` LIMIT ' . (int) ($views - $filled));
            $filled = min($filled * 2, $views);
        }
    }
}
