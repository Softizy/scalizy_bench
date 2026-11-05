<?php
/**
 * Front office blocks: a featured product repeated across slots and a remote widget.
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
require_once __DIR__ . '/classes/block/ScalizyRemoteWidgetBlock.php';

class Scalizy_Bench extends Module
{
    const CONFIG_BLOCKS = 'SCALIZY_FRONT_BLOCKS';

    const HOOKS = ['displayHeader', 'displayTop', 'displayHome', 'displayFooter'];

    const BLOCKS = [
        'featured_product' => 'ScalizyFeaturedProductBlock',
        'remote_widget' => 'ScalizyRemoteWidgetBlock',
    ];

    public function __construct()
    {
        $this->name = 'scalizy_bench';
        $this->tab = 'front_office_features';
        $this->version = '1.2.1';
        $this->author = 'Softizy';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Scalizy front blocks');
        $this->description = $this->l('Featured product and remote widget blocks for the front office.');
    }

    public function install()
    {
        Configuration::updateValue(self::CONFIG_BLOCKS, '[]');

        return parent::install() && $this->registerHook(self::HOOKS);
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CONFIG_BLOCKS);

        return parent::uninstall();
    }

    public function hookDisplayHeader($params)
    {
        return $this->renderBlocks('displayHeader');
    }

    public function hookDisplayTop($params)
    {
        return $this->renderBlocks('displayTop');
    }

    public function hookDisplayHome($params)
    {
        return $this->renderBlocks('displayHome');
    }

    public function hookDisplayFooter($params)
    {
        return $this->renderBlocks('displayFooter');
    }

    private function renderBlocks($hook)
    {
        try {
            $blocks = self::parseBlocks((string) Configuration::get(self::CONFIG_BLOCKS));
        } catch (InvalidArgumentException $e) {
            return '';
        }

        $html = '';
        foreach ($blocks as $block) {
            if ($block['hook'] === $hook) {
                $class = self::BLOCKS[$block['type']];
                $html .= (new $class($this))->render($block['settings']);
            }
        }

        return $html;
    }

    /**
     * @return array<int, array{type: string, hook: string, settings: array<string, int|string>}>
     *
     * @throws InvalidArgumentException when the configuration names a block, hook or setting the module does not know
     */
    public static function parseBlocks($json)
    {
        $entries = json_decode($json, true);
        if (!is_array($entries) || array_values($entries) !== $entries) {
            throw new InvalidArgumentException('The blocks must be a JSON list.');
        }

        $blocks = [];
        foreach ($entries as $index => $entry) {
            $type = is_array($entry) && isset($entry['type']) && is_string($entry['type']) ? $entry['type'] : null;
            if (null === $type || !array_key_exists($type, self::BLOCKS)) {
                throw new InvalidArgumentException(sprintf('Block %d: type must be one of %s.', $index, implode(', ', array_keys(self::BLOCKS))));
            }
            $class = self::BLOCKS[$type];
            $hook = isset($entry['hook']) ? $entry['hook'] : null;
            if (!in_array($hook, $class::hooks(), true)) {
                throw new InvalidArgumentException(sprintf('Block %d: %s displays on %s.', $index, $type, implode(', ', $class::hooks())));
            }
            $settings = array_merge($class::settings(), array_diff_key($entry, ['type' => true, 'hook' => true]));
            foreach ($settings as $name => $value) {
                if (!self::isValidSetting($class::settings(), $name, $value)) {
                    throw new InvalidArgumentException(sprintf('Block %d: %s takes the settings %s.', $index, $type, self::describeSettings($class::settings())));
                }
            }
            $blocks[] = ['type' => $type, 'hook' => $hook, 'settings' => $settings];
        }

        return $blocks;
    }

    private static function isValidSetting(array $defaults, $name, $value)
    {
        if (!array_key_exists($name, $defaults)) {
            return false;
        }
        if (is_int($defaults[$name])) {
            return is_int($value) && $value >= 0;
        }

        return is_string($value) && Validate::isAbsoluteUrl($value) && '' !== $value;
    }

    private static function describeSettings(array $defaults)
    {
        $described = [];
        foreach ($defaults as $name => $default) {
            $described[] = is_int($default) ? sprintf('%s (integer, %d)', $name, $default) : sprintf('%s (URL, required)', $name);
        }

        return implode(', ', $described);
    }

    public function getContent()
    {
        $output = '';
        $json = (string) Configuration::get(self::CONFIG_BLOCKS);
        if (Tools::isSubmit('submitScalizyFrontBlocks')) {
            $json = trim((string) Tools::getValue(self::CONFIG_BLOCKS));
            try {
                self::parseBlocks($json);
                Configuration::updateValue(self::CONFIG_BLOCKS, $json);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } catch (InvalidArgumentException $e) {
                $output .= $this->displayError($e->getMessage());
            }
        }

        return $output . $this->renderForm($json);
    }

    private function renderForm($json)
    {
        $blocks = [];
        foreach (self::BLOCKS as $type => $class) {
            $blocks[] = sprintf('%s on %s: %s', $type, implode(', ', $class::hooks()), self::describeSettings($class::settings()));
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitScalizyFrontBlocks';
        $helper->fields_value = [self::CONFIG_BLOCKS => $json];

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->l('Blocks')],
                'input' => [[
                    'type' => 'textarea',
                    'label' => $this->l('Blocks to display (JSON list, [] for none)'),
                    'name' => self::CONFIG_BLOCKS,
                    'rows' => 8,
                    'desc' => sprintf(
                        'Blocks — %s. Example: [{"type": "featured_product", "hook": "displayFooter", "product_id": 1, "slots": 12}]',
                        implode(' ; ', $blocks)
                    ),
                ]],
                'submit' => ['title' => $this->l('Save')],
            ],
        ]]);
    }
}
