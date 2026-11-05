<?php
/**
 * Front office blocks: a featured product repeated across slots, the popular products, a product ranking and a remote widget.
 *
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/ScalizyFrontBlock.php';
require_once __DIR__ . '/classes/block/ScalizyFeaturedProductBlock.php';
require_once __DIR__ . '/classes/block/ScalizyPopularProductsBlock.php';
require_once __DIR__ . '/classes/block/ScalizyProductRankingBlock.php';
require_once __DIR__ . '/classes/block/ScalizyRemoteWidgetBlock.php';

class Scalizy_Bench extends Module
{
    const HOOKS = ['displayHeader', 'displayNav1', 'displayNavFullWidth', 'displayTop', 'displayHome', 'displayFooterBefore', 'displayFooter', 'displayFooterAfter'];

    const BLOCKS = [
        'SCALIZY_FEATURED_PRODUCT' => ['class' => 'ScalizyFeaturedProductBlock', 'title' => 'Featured product', 'checkbox' => 'sql-n-plus-1'],
        'SCALIZY_POPULAR_PRODUCTS' => ['class' => 'ScalizyPopularProductsBlock', 'title' => 'Popular products', 'checkbox' => 'sql-slow-query'],
        'SCALIZY_PRODUCT_RANKING' => ['class' => 'ScalizyProductRankingBlock', 'title' => 'Product ranking', 'checkbox' => 'ttfb-hook'],
        'SCALIZY_REMOTE_WIDGET' => ['class' => 'ScalizyRemoteWidgetBlock', 'title' => 'Remote widget', 'checkbox' => 'ttfb-external-call'],
    ];

    public function __construct()
    {
        $this->name = 'scalizy_bench';
        $this->tab = 'front_office_features';
        $this->version = '1.4.0';
        $this->author = 'Softizy';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Scalizy front blocks');
        $this->description = $this->l('Featured product, popular products, product ranking and remote widget blocks for the front office.');
    }

    public function install()
    {
        return parent::install() && $this->registerHook(self::HOOKS) && $this->resetBlocks();
    }

    public function uninstall()
    {
        foreach (self::BLOCKS as $config => $block) {
            foreach (self::fieldNames($config, $block['class']) as $name) {
                Configuration::deleteByName($name);
            }
        }
        Configuration::deleteByName(ScalizyRemoteWidgetBlock::WIDGET_HTML);
        Configuration::deleteByName(ScalizyRemoteWidgetBlock::WIDGET_FETCHED_AT);
        Configuration::deleteByName(ScalizyProductRankingBlock::ROUNDS);
        ScalizyPopularProductsBlock::dropTable();

        return parent::uninstall();
    }

    public function resetBlocks()
    {
        foreach (self::BLOCKS as $config => $block) {
            $class = $block['class'];
            $hooks = $class::hooks();
            Configuration::updateValue($config . '_ENABLED', 0);
            Configuration::updateValue($config . '_HOOK', $hooks[0]);
            foreach ($class::settings() as $name => $default) {
                Configuration::updateValue($config . '_' . strtoupper($name), $default);
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

    private function renderBlocks($hook)
    {
        $html = '';
        foreach (self::BLOCKS as $config => $block) {
            if (!Configuration::get($config . '_ENABLED')) {
                continue;
            }
            try {
                $values = self::parseBlock($block['class'], self::storedValues($config, $block['class']));
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
    public static function parseBlock($class, array $values)
    {
        if (!in_array($values['hook'], $class::hooks(), true)) {
            throw new InvalidArgumentException(sprintf('displays on %s', implode(', ', $class::hooks())));
        }

        $settings = [];
        foreach ($class::settings() as $name => $default) {
            $value = trim((string) $values[$name]);
            if (is_int($default)) {
                if (!ctype_digit($value)) {
                    throw new InvalidArgumentException(sprintf('%s must be a whole number', $name));
                }
                $settings[$name] = (int) $value;
            } else {
                if ('' === $value || !Validate::isAbsoluteUrl($value)) {
                    throw new InvalidArgumentException(sprintf('%s must be an absolute URL', $name));
                }
                $settings[$name] = $value;
            }
        }

        return ['hook' => $values['hook'], 'settings' => $settings];
    }

    private static function storedValues($config, $class)
    {
        $values = ['hook' => (string) Configuration::get($config . '_HOOK')];
        foreach (array_keys($class::settings()) as $name) {
            $values[$name] = (string) Configuration::get($config . '_' . strtoupper($name));
        }

        return $values;
    }

    private static function fieldNames($config, $class)
    {
        $names = ['hook' => $config . '_HOOK'];
        foreach (array_keys($class::settings()) as $name) {
            $names[$name] = $config . '_' . strtoupper($name);
        }

        return ['enabled' => $config . '_ENABLED'] + $names;
    }

    public function getContent()
    {
        if (!Tools::isSubmit('submitScalizyFrontBlocks')) {
            return $this->renderForm();
        }

        $submitted = [];
        $errors = [];
        foreach (self::BLOCKS as $config => $block) {
            $enabled = (bool) Tools::getValue($config . '_ENABLED_on');
            $values = [];
            foreach (self::fieldNames($config, $block['class']) as $key => $name) {
                if ('enabled' !== $key) {
                    $values[$key] = (string) Tools::getValue($name);
                }
            }
            if ($enabled) {
                try {
                    self::parseBlock($block['class'], $values);
                } catch (InvalidArgumentException $e) {
                    $errors[] = sprintf('%s (%s): %s.', $block['title'], $block['checkbox'], $e->getMessage());
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
                foreach (self::fieldNames($config, self::BLOCKS[$config]['class']) as $key => $name) {
                    if ('enabled' !== $key) {
                        Configuration::updateValue($name, $block['values'][$key]);
                    }
                }
                $class = self::BLOCKS[$config]['class'];
                $values = self::parseBlock($class, $block['values']);
                (new $class($this))->enable($values['settings']);
            }
        }

        return $this->displayConfirmation($this->l('Settings updated')) . $this->renderForm();
    }

    private function renderForm()
    {
        $forms = [];
        $fieldsValue = [];
        foreach (self::BLOCKS as $config => $block) {
            $class = $block['class'];
            $inputs = [[
                'type' => 'checkbox',
                'label' => $this->l('Display'),
                'name' => $config . '_ENABLED',
                'values' => ['query' => [['id' => 'on', 'name' => $block['checkbox']]], 'id' => 'id', 'name' => 'name'],
            ], [
                'type' => 'select',
                'label' => $this->l('Hook'),
                'name' => $config . '_HOOK',
                'options' => ['query' => array_map(function ($hook) {
                    return ['id' => $hook, 'name' => $hook];
                }, $class::hooks()), 'id' => 'id', 'name' => 'name'],
            ]];
            foreach ($class::settings() as $name => $default) {
                $inputs[] = ['type' => 'text', 'label' => $name, 'name' => $config . '_' . strtoupper($name), 'desc' => is_int($default) ? $this->l('Whole number') : $this->l('Absolute URL')];
            }
            foreach (self::fieldNames($config, $class) as $key => $name) {
                $fieldsValue['enabled' === $key ? $name . '_on' : $name] = Configuration::get($name);
            }
            $forms[] = ['form' => [
                'legend' => ['title' => $block['title']],
                'input' => $inputs,
                'submit' => ['title' => $this->l('Save')],
            ]];
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitScalizyFrontBlocks';
        $helper->fields_value = $fieldsValue;

        return $helper->generateForm($forms);
    }
}
