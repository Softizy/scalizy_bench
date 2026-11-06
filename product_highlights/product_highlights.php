<?php
/**
 * Product highlights for the storefront: featured product, popular products, trending products and a partner banner.
 *
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/ProductHighlightsBlock.php';
require_once __DIR__ . '/classes/block/ProductHighlightsFeaturedBlock.php';
require_once __DIR__ . '/classes/block/ProductHighlightsPopularBlock.php';
require_once __DIR__ . '/classes/block/ProductHighlightsTrendingBlock.php';
require_once __DIR__ . '/classes/block/ProductHighlightsPartnerBannerBlock.php';

class Product_Highlights extends Module
{
    const HOOKS = [
        'displayHeader' => 'En-tête de page (displayHeader)',
        'displayNav1' => 'Barre de navigation (displayNav1)',
        'displayNavFullWidth' => 'Sous la navigation (displayNavFullWidth)',
        'displayTop' => 'Haut de page (displayTop)',
        'displayHome' => 'Page d\'accueil (displayHome)',
        'displayFooterBefore' => 'Avant le pied de page (displayFooterBefore)',
        'displayFooter' => 'Pied de page (displayFooter)',
        'displayFooterAfter' => 'Après le pied de page (displayFooterAfter)',
    ];

    const BLOCKS = [
        'PRODUCT_HIGHLIGHTS_FEATURED' => [
            'class' => 'ProductHighlightsFeaturedBlock',
            'title' => 'Produit à la une',
            'hook' => 'displayFooter',
            'settings' => [
                'product_id' => ['label' => 'Produit mis en avant (identifiant)', 'default' => 1],
                'slots' => ['label' => 'Nombre d\'emplacements', 'default' => 4],
            ],
        ],
        'PRODUCT_HIGHLIGHTS_POPULAR' => [
            'class' => 'ProductHighlightsPopularBlock',
            'title' => 'Produits populaires',
            'hook' => 'displayFooterAfter',
            'settings' => [
                'days' => ['label' => 'Période de calcul (jours)', 'default' => 30],
                'shown' => ['label' => 'Nombre de produits affichés', 'default' => 5],
            ],
        ],
        'PRODUCT_HIGHLIGHTS_TRENDING' => [
            'class' => 'ProductHighlightsTrendingBlock',
            'title' => 'Tendances',
            'hook' => 'displayFooterBefore',
            'settings' => [
                'products' => ['label' => 'Nombre de produits classés', 'default' => 20],
                'passes' => ['label' => 'Passes de calcul du classement', 'default' => 10],
            ],
        ],
        'PRODUCT_HIGHLIGHTS_BANNER' => [
            'class' => 'ProductHighlightsPartnerBannerBlock',
            'title' => 'Bandeau partenaire',
            'hook' => 'displayHeader',
            'settings' => [
                'url' => ['label' => 'URL du bandeau', 'default' => ''],
            ],
        ],
    ];

    public function __construct()
    {
        $this->name = 'product_highlights';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Softizy';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'Mises en avant produits';
        $this->description = 'Produit à la une, produits populaires, tendances et bandeau partenaire sur la vitrine.';
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook(array_merge(array_keys(self::HOOKS), ['displayFooterProduct']))
            && ProductHighlightsPopularBlock::createTable()
            && $this->resetBlocks();
    }

    public function uninstall()
    {
        foreach (array_keys(self::BLOCKS) as $config) {
            foreach (self::fieldNames($config) as $name) {
                Configuration::deleteByName($name);
            }
        }
        Configuration::deleteByName(ProductHighlightsPartnerBannerBlock::BANNER_HTML);
        Configuration::deleteByName(ProductHighlightsPartnerBannerBlock::BANNER_FETCHED_AT);
        ProductHighlightsPopularBlock::dropTable();

        return parent::uninstall();
    }

    public function resetBlocks()
    {
        foreach (self::BLOCKS as $config => $block) {
            Configuration::updateValue($config . '_ENABLED', 0);
            Configuration::updateValue($config . '_HOOK', $block['hook']);
            foreach ($block['settings'] as $name => $setting) {
                Configuration::updateValue($config . '_' . strtoupper($name), $setting['default']);
            }
        }

        return true;
    }

    public function hookDisplayHeader($params)
    {
        return $this->renderBlocks('displayHeader');
    }

    public function hookDisplayNav1($params)
    {
        return $this->renderBlocks('displayNav1');
    }

    public function hookDisplayNavFullWidth($params)
    {
        return $this->renderBlocks('displayNavFullWidth');
    }

    public function hookDisplayTop($params)
    {
        return $this->renderBlocks('displayTop');
    }

    public function hookDisplayHome($params)
    {
        return $this->renderBlocks('displayHome');
    }

    public function hookDisplayFooterBefore($params)
    {
        return $this->renderBlocks('displayFooterBefore');
    }

    public function hookDisplayFooter($params)
    {
        return $this->renderBlocks('displayFooter');
    }

    public function hookDisplayFooterAfter($params)
    {
        return $this->renderBlocks('displayFooterAfter');
    }

    public function hookDisplayFooterProduct($params)
    {
        $idProduct = (int) Tools::getValue('id_product');
        if (Configuration::get('PRODUCT_HIGHLIGHTS_POPULAR_ENABLED') && $idProduct > 0) {
            ProductHighlightsPopularBlock::recordView($idProduct, (int) $this->context->shop->id);
        }

        return '';
    }

    private function renderBlocks($hook)
    {
        $html = '';
        foreach (self::BLOCKS as $config => $block) {
            if (!Configuration::get($config . '_ENABLED')) {
                continue;
            }
            try {
                $values = self::parseBlock($config, self::storedValues($config));
            } catch (InvalidArgumentException $e) {
                continue;
            }
            if ($values['hook'] === $hook) {
                $class = $block['class'];
                $html .= (new $class($this))->render($values['settings']);
            }
        }

        return $html;
    }

    /**
     * @param array<string, string> $values hook and settings of the block, as entered
     *
     * @return array{hook: string, settings: array<string, int|string>}
     *
     * @throws InvalidArgumentException when the hook or a setting is not one the block accepts
     */
    public static function parseBlock($config, array $values)
    {
        if (!isset(self::HOOKS[$values['hook']])) {
            throw new InvalidArgumentException('emplacement inconnu');
        }

        $settings = [];
        foreach (self::BLOCKS[$config]['settings'] as $name => $setting) {
            $value = trim((string) $values[$name]);
            if (is_int($setting['default'])) {
                if (!ctype_digit($value) || (int) $value < 1) {
                    throw new InvalidArgumentException(sprintf('« %s » doit être un nombre entier positif', $setting['label']));
                }
                $settings[$name] = (int) $value;
            } elseif ('' === $value || !Validate::isAbsoluteUrl($value)) {
                throw new InvalidArgumentException(sprintf('« %s » doit être une URL absolue', $setting['label']));
            } else {
                $settings[$name] = $value;
            }
        }

        return ['hook' => $values['hook'], 'settings' => $settings];
    }

    private static function storedValues($config)
    {
        $values = ['hook' => (string) Configuration::get($config . '_HOOK')];
        foreach (array_keys(self::BLOCKS[$config]['settings']) as $name) {
            $values[$name] = (string) Configuration::get($config . '_' . strtoupper($name));
        }

        return $values;
    }

    public static function fieldNames($config)
    {
        $names = ['enabled' => $config . '_ENABLED', 'hook' => $config . '_HOOK'];
        foreach (array_keys(self::BLOCKS[$config]['settings']) as $name) {
            $names[$name] = $config . '_' . strtoupper($name);
        }

        return $names;
    }

    public function getContent()
    {
        if (!Tools::isSubmit('submitProductHighlights')) {
            return $this->renderForm();
        }

        $submitted = [];
        $errors = [];
        foreach (self::BLOCKS as $config => $block) {
            $enabled = (bool) Tools::getValue($config . '_ENABLED_on');
            $values = [];
            foreach (self::fieldNames($config) as $key => $name) {
                if ('enabled' !== $key) {
                    $values[$key] = (string) Tools::getValue($name);
                }
            }
            if ($enabled) {
                try {
                    self::parseBlock($config, $values);
                } catch (InvalidArgumentException $e) {
                    $errors[] = sprintf('%s : %s.', $block['title'], $e->getMessage());
                }
            }
            $submitted[$config] = ['enabled' => $enabled, 'values' => $values];
        }

        if ([] !== $errors) {
            return $this->displayError($errors) . $this->renderForm();
        }

        foreach ($submitted as $config => $block) {
            Configuration::updateValue($config . '_ENABLED', $block['enabled'] ? 1 : 0);
            if ($block['enabled']) {
                foreach (self::fieldNames($config) as $key => $name) {
                    if ('enabled' !== $key) {
                        Configuration::updateValue($name, $block['values'][$key]);
                    }
                }
            }
        }

        return $this->displayConfirmation('Paramètres enregistrés.') . $this->renderForm();
    }

    private function renderForm()
    {
        $forms = [];
        $fieldsValue = [];
        foreach (self::BLOCKS as $config => $block) {
            $inputs = [[
                'type' => 'checkbox',
                'label' => 'Affichage',
                'name' => $config . '_ENABLED',
                'values' => ['query' => [['id' => 'on', 'name' => 'Afficher le bloc « ' . $block['title'] . ' »']], 'id' => 'id', 'name' => 'name'],
            ], [
                'type' => 'select',
                'label' => 'Emplacement',
                'name' => $config . '_HOOK',
                'options' => ['query' => self::options(self::HOOKS), 'id' => 'id', 'name' => 'name'],
            ]];
            foreach ($block['settings'] as $name => $setting) {
                $inputs[] = ['type' => 'text', 'label' => $setting['label'], 'name' => $config . '_' . strtoupper($name)];
            }
            foreach (self::fieldNames($config) as $key => $name) {
                $fieldsValue['enabled' === $key ? $name . '_on' : $name] = Configuration::get($name);
            }
            $forms[] = ['form' => [
                'legend' => ['title' => $block['title']],
                'input' => $inputs,
                'submit' => ['title' => 'Enregistrer'],
            ]];
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitProductHighlights';
        $helper->fields_value = $fieldsValue;

        return $helper->generateForm($forms);
    }

    private static function options(array $labels)
    {
        $options = [];
        foreach ($labels as $id => $name) {
            $options[] = ['id' => $id, 'name' => $name];
        }

        return $options;
    }
}
