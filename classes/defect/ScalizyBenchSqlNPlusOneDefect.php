<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ScalizyBenchSqlNPlusOneDefect implements ScalizyBenchDefect
{
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public static function parameters()
    {
        return ['iterations' => 300, 'product_id' => 42];
    }

    public function produce(array $parameters)
    {
        $context = Context::getContext();
        $idProduct = (int) $parameters['product_id'];
        $idLang = (int) $context->language->id;
        $idShop = (int) $context->shop->id;
        $items = [];
        for ($slot = 0; $slot < $parameters['iterations']; ++$slot) {
            $rows = Db::getInstance()->executeS('SELECT pl.`name`, pl.`link_rewrite` FROM `' . _DB_PREFIX_ . 'product_lang` pl WHERE pl.`id_product` = ' . $idProduct . ' AND pl.`id_lang` = ' . $idLang . ' AND pl.`id_shop` = ' . $idShop);
            if (!empty($rows)) {
                $items[] = $rows[0];
            }
        }

        $context->smarty->assign('scalizy_bench_items', $items);

        return $this->module->display($this->module->getLocalPath() . $this->module->name . '.php', 'views/templates/hook/items.tpl');
    }
}
