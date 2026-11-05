<?php
/**
 * Scalizy bench module: intentional performance defects for the Scalizy regression bench.
 *
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Scalizy_Bench extends Module
{
    const CONFIG_FOOTER_DEFECT = 'SCALIZY_BENCH_FOOTER_DEFECT';
    const CONFIG_HEADER_DEFECT = 'SCALIZY_BENCH_HEADER_DEFECT';
    const CONFIG_PRODUCT_ID = 'SCALIZY_BENCH_PRODUCT_ID';
    const CONFIG_FOOTER_ITEMS = 'SCALIZY_BENCH_FOOTER_ITEMS';
    const CONFIG_HEADER_DELAY_MS = 'SCALIZY_BENCH_HEADER_DELAY_MS';

    const DEFAULTS = [
        self::CONFIG_FOOTER_DEFECT => 1,
        self::CONFIG_HEADER_DEFECT => 1,
        self::CONFIG_PRODUCT_ID => 42,
        self::CONFIG_FOOTER_ITEMS => 300,
        self::CONFIG_HEADER_DELAY_MS => 400,
    ];

    public function __construct()
    {
        $this->name = 'scalizy_bench';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Softizy';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Scalizy bench');
        $this->description = $this->l('Intentional performance defects for the Scalizy regression bench. Never install on a production shop.');
    }

    public function install()
    {
        foreach (self::DEFAULTS as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayFooter');
    }

    public function uninstall()
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    public function hookDisplayHeader($params)
    {
        if (!Configuration::get(self::CONFIG_HEADER_DEFECT)) {
            return '';
        }

        usleep((int) Configuration::get(self::CONFIG_HEADER_DELAY_MS) * 1000);

        return '';
    }

    public function hookDisplayFooter($params)
    {
        if (!Configuration::get(self::CONFIG_FOOTER_DEFECT)) {
            return '';
        }

        $idProduct = (int) Configuration::get(self::CONFIG_PRODUCT_ID);
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $items = [];
        for ($slot = 0; $slot < (int) Configuration::get(self::CONFIG_FOOTER_ITEMS); ++$slot) {
            $rows = Db::getInstance()->executeS('SELECT pl.`name`, pl.`link_rewrite` FROM `' . _DB_PREFIX_ . 'product_lang` pl WHERE pl.`id_product` = ' . $idProduct . ' AND pl.`id_lang` = ' . $idLang . ' AND pl.`id_shop` = ' . $idShop);
            if (!empty($rows)) {
                $items[] = $rows[0];
            }
        }

        $this->context->smarty->assign('scalizy_bench_items', $items);

        return $this->display(__FILE__, 'views/templates/hook/footer.tpl');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitScalizyBench')) {
            Configuration::updateValue(self::CONFIG_FOOTER_DEFECT, (int) Tools::getValue(self::CONFIG_FOOTER_DEFECT));
            Configuration::updateValue(self::CONFIG_HEADER_DEFECT, (int) Tools::getValue(self::CONFIG_HEADER_DEFECT));
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->renderForm();
    }

    private function renderForm()
    {
        $switch = function ($name, $label) {
            return [
                'type' => 'switch',
                'label' => $label,
                'name' => $name,
                'is_bool' => true,
                'values' => [
                    ['id' => $name . '_on', 'value' => 1, 'label' => $this->l('Enabled')],
                    ['id' => $name . '_off', 'value' => 0, 'label' => $this->l('Disabled')],
                ],
            ];
        };

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitScalizyBench';
        $helper->fields_value = [
            self::CONFIG_FOOTER_DEFECT => (int) Configuration::get(self::CONFIG_FOOTER_DEFECT),
            self::CONFIG_HEADER_DEFECT => (int) Configuration::get(self::CONFIG_HEADER_DEFECT),
        ];

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->l('Defects')],
                'input' => [
                    $switch(self::CONFIG_FOOTER_DEFECT, $this->l('Footer: one query per slot (N+1)')),
                    $switch(self::CONFIG_HEADER_DEFECT, $this->l('Header: blocking wait')),
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ]]);
    }
}
