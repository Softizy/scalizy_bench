<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ScalizyPopularProductsBlock implements ScalizyFrontBlock
{
    const TABLE = 'scalizy_product_view';
    const SHOWN = 5;
    const DAYS = 30;
    const HISTORY_SECONDS = 7776000;
    const FIRST_IMPORT = 1000;
    const MAX_VIEWS = 16000000;

    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public static function settings()
    {
        return ['ms' => 400];
    }

    public static function hooks()
    {
        return ['displayFooterAfter', 'displayNavFullWidth'];
    }

    public function enable(array $settings)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . self::TABLE;
        $db->execute('CREATE TABLE IF NOT EXISTS `' . $table . '` (`id_product_view` INT UNSIGNED NOT NULL AUTO_INCREMENT, `id_product` INT UNSIGNED NOT NULL, `id_shop` INT UNSIGNED NOT NULL, `date_add` DATETIME NOT NULL, PRIMARY KEY (`id_product_view`)) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
        $db->execute('TRUNCATE TABLE `' . $table . '`');

        $idShop = (int) Context::getContext()->shop->id;
        $products = array_map('intval', array_column($db->executeS('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_shop` = ' . $idShop . ' AND `active` = 1'), 'id_product'));
        if ([] === $products) {
            $products = [1];
        }
        $rows = [];
        for ($view = 0; $view < self::FIRST_IMPORT; ++$view) {
            $rows[] = ['id_product' => $products[$view % count($products)], 'id_shop' => $idShop, 'date_add' => date('Y-m-d H:i:s', time() - mt_rand(0, self::HISTORY_SECONDS))];
        }
        $db->insert(self::TABLE, $rows);

        $views = self::FIRST_IMPORT;
        $ms = $this->measure($idShop);
        while ($ms < $settings['ms'] && $views < self::MAX_VIEWS) {
            $db->execute('INSERT INTO `' . $table . '` (`id_product`, `id_shop`, `date_add`) SELECT `id_product`, `id_shop`, NOW() - INTERVAL FLOOR(RAND() * ' . self::HISTORY_SECONDS . ') SECOND FROM `' . $table . '`');
            $views *= 2;
            $ms = $this->measure($idShop);
        }
        if ($ms > $settings['ms'] && $views > self::FIRST_IMPORT) {
            $db->execute('DELETE FROM `' . $table . '` WHERE `id_product_view` > ' . (int) ceil($views * $settings['ms'] / $ms));
        }

        return true;
    }

    public function render(array $settings)
    {
        $context = Context::getContext();
        $idShop = (int) $context->shop->id;
        $idLang = (int) $context->language->id;
        $popular = $this->popularProducts($idShop);
        $names = [];
        if ([] !== $popular) {
            foreach (Db::getInstance()->executeS('SELECT `id_product`, `name` FROM `' . _DB_PREFIX_ . 'product_lang` WHERE `id_product` IN (' . implode(',', array_keys($popular)) . ') AND `id_lang` = ' . $idLang . ' AND `id_shop` = ' . $idShop) as $row) {
                $names[(int) $row['id_product']] = $row['name'];
            }
        }

        $products = [];
        foreach ($popular as $idProduct => $views) {
            $products[] = ['name' => isset($names[$idProduct]) ? $names[$idProduct] : '#' . $idProduct, 'views' => $views];
        }
        $context->smarty->assign('popular_products', $products);

        return $this->module->display($this->module->getLocalPath() . $this->module->name . '.php', 'views/templates/hook/popular_products.tpl');
    }

    public static function dropTable()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE . '`');
    }

    private function popularProducts($idShop)
    {
        $popular = [];
        $rows = Db::getInstance()->executeS('SELECT `id_product`, COUNT(*) AS `views` FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE `id_shop` = ' . (int) $idShop . ' AND `date_add` >= NOW() - INTERVAL ' . self::DAYS . ' DAY GROUP BY `id_product` ORDER BY `views` DESC LIMIT ' . self::SHOWN);
        foreach ($rows ?: [] as $row) {
            $popular[(int) $row['id_product']] = (int) $row['views'];
        }

        return $popular;
    }

    private function measure($idShop)
    {
        $start = microtime(true);
        $this->popularProducts($idShop);

        return (microtime(true) - $start) * 1000;
    }
}
