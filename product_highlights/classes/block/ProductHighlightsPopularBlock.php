<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductHighlightsPopularBlock implements ProductHighlightsBlock
{
    const TABLE = 'product_highlights_view';

    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public static function createTable()
    {
        return Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (`id_product_view` INT UNSIGNED NOT NULL AUTO_INCREMENT, `id_product` INT UNSIGNED NOT NULL, `id_shop` INT UNSIGNED NOT NULL, `date_add` DATETIME NOT NULL, PRIMARY KEY (`id_product_view`)) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
    }

    public static function dropTable()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE . '`');
    }

    public static function recordView($idProduct, $idShop)
    {
        return Db::getInstance()->insert(self::TABLE, ['id_product' => (int) $idProduct, 'id_shop' => (int) $idShop, 'date_add' => date('Y-m-d H:i:s')]);
    }

    public function render(array $settings)
    {
        $context = Context::getContext();
        $idShop = (int) $context->shop->id;
        $idLang = (int) $context->language->id;
        $popular = $this->popularProducts($idShop, $settings['days'], $settings['shown']);
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
        $context->smarty->assign('highlight_popular_products', $products);

        return $this->module->display($this->module->getLocalPath() . $this->module->name . '.php', 'views/templates/hook/popular.tpl');
    }

    private function popularProducts($idShop, $days, $shown)
    {
        $popular = [];
        $rows = Db::getInstance()->executeS('SELECT `id_product`, COUNT(*) AS `views` FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE `id_shop` = ' . (int) $idShop . ' AND `date_add` >= NOW() - INTERVAL ' . (int) $days . ' DAY GROUP BY `id_product` ORDER BY `views` DESC LIMIT ' . (int) $shown);
        foreach ($rows ?: [] as $row) {
            $popular[(int) $row['id_product']] = (int) $row['views'];
        }

        return $popular;
    }
}
