<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ScalizyFeaturedProductBlock implements ScalizyFrontBlock
{
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public static function settings()
    {
        return ['product_id' => 1, 'slots' => 12];
    }

    public static function hooks()
    {
        return ['displayFooter', 'displayHome'];
    }

    public function enable(array $settings)
    {
        return true;
    }

    public function render(array $settings)
    {
        $context = Context::getContext();
        $idProduct = (int) $settings['product_id'];
        $idLang = (int) $context->language->id;
        $idShop = (int) $context->shop->id;
        $slots = [];
        for ($slot = 0; $slot < $settings['slots']; ++$slot) {
            $rows = Db::getInstance()->executeS('SELECT pl.`name`, pl.`link_rewrite` FROM `' . _DB_PREFIX_ . 'product_lang` pl WHERE pl.`id_product` = ' . $idProduct . ' AND pl.`id_lang` = ' . $idLang . ' AND pl.`id_shop` = ' . $idShop);
            if (!empty($rows)) {
                $slots[] = $rows[0];
            }
        }

        $context->smarty->assign('featured_product_slots', $slots);

        return $this->module->display($this->module->getLocalPath() . $this->module->name . '.php', 'views/templates/hook/featured_product.tpl');
    }
}
