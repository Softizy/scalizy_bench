<?php
/**
 * Scalizy bench module: performance defects produced on demand for the Scalizy regression bench.
 *
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/ScalizyBenchDefect.php';
require_once __DIR__ . '/classes/defect/ScalizyBenchSqlNPlusOneDefect.php';
require_once __DIR__ . '/classes/defect/ScalizyBenchBlockingWaitDefect.php';

class Scalizy_Bench extends Module
{
    const CONFIG_DEFECTS = 'SCALIZY_BENCH_DEFECTS';

    const HOOKS = ['displayHeader', 'displayTop', 'displayHome', 'displayFooter'];

    const CATALOG = [
        'sql_n_plus_1' => 'ScalizyBenchSqlNPlusOneDefect',
        'blocking_wait' => 'ScalizyBenchBlockingWaitDefect',
    ];

    public function __construct()
    {
        $this->name = 'scalizy_bench';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Softizy';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Scalizy bench');
        $this->description = $this->l('Performance defects produced on demand for the Scalizy regression bench. Never install on a production shop.');
    }

    public function install()
    {
        Configuration::updateValue(self::CONFIG_DEFECTS, '[]');

        return parent::install() && $this->registerHook(self::HOOKS);
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CONFIG_DEFECTS);

        return parent::uninstall();
    }

    public function hookDisplayHeader($params)
    {
        return $this->produceDefects('displayHeader');
    }

    public function hookDisplayTop($params)
    {
        return $this->produceDefects('displayTop');
    }

    public function hookDisplayHome($params)
    {
        return $this->produceDefects('displayHome');
    }

    public function hookDisplayFooter($params)
    {
        return $this->produceDefects('displayFooter');
    }

    private function produceDefects($hook)
    {
        try {
            $defects = self::parseDefects((string) Configuration::get(self::CONFIG_DEFECTS));
        } catch (InvalidArgumentException $e) {
            return '';
        }

        $html = '';
        foreach ($defects as $defect) {
            if ($defect['hook'] === $hook) {
                $class = self::CATALOG[$defect['type']];
                $html .= (new $class($this))->produce($defect['parameters']);
            }
        }

        return $html;
    }

    /**
     * @return array<int, array{type: string, hook: string, parameters: array<string, int>}>
     *
     * @throws InvalidArgumentException when the configuration names a defect, hook or parameter the module does not know
     */
    public static function parseDefects($json)
    {
        $entries = json_decode($json, true);
        if (!is_array($entries) || array_values($entries) !== $entries) {
            throw new InvalidArgumentException('The defects must be a JSON list.');
        }

        $defects = [];
        foreach ($entries as $index => $entry) {
            $type = is_array($entry) && isset($entry['type']) && is_string($entry['type']) ? $entry['type'] : null;
            if (null === $type || !array_key_exists($type, self::CATALOG)) {
                throw new InvalidArgumentException(sprintf('Defect %d: type must be one of %s.', $index, implode(', ', array_keys(self::CATALOG))));
            }
            $hook = isset($entry['hook']) ? $entry['hook'] : null;
            if (!in_array($hook, self::HOOKS, true)) {
                throw new InvalidArgumentException(sprintf('Defect %d: hook must be one of %s.', $index, implode(', ', self::HOOKS)));
            }
            $class = self::CATALOG[$type];
            $parameters = $class::parameters();
            foreach (array_diff_key($entry, ['type' => true, 'hook' => true]) as $name => $value) {
                if (!array_key_exists($name, $parameters) || !is_int($value) || $value < 0) {
                    throw new InvalidArgumentException(sprintf('Defect %d: %s takes the non-negative integer parameters %s.', $index, $type, implode(', ', array_keys($parameters))));
                }
                $parameters[$name] = $value;
            }
            $defects[] = ['type' => $type, 'hook' => $hook, 'parameters' => $parameters];
        }

        return $defects;
    }

    public function getContent()
    {
        $output = '';
        $json = (string) Configuration::get(self::CONFIG_DEFECTS);
        if (Tools::isSubmit('submitScalizyBench')) {
            $json = trim((string) Tools::getValue(self::CONFIG_DEFECTS));
            try {
                self::parseDefects($json);
                Configuration::updateValue(self::CONFIG_DEFECTS, $json);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } catch (InvalidArgumentException $e) {
                $output .= $this->displayError($e->getMessage());
            }
        }

        return $output . $this->renderForm($json);
    }

    private function renderForm($json)
    {
        $catalog = [];
        foreach (self::CATALOG as $type => $class) {
            $parameters = [];
            foreach ($class::parameters() as $name => $default) {
                $parameters[] = $name . ' (' . $default . ')';
            }
            $catalog[] = $type . ': ' . implode(', ', $parameters);
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitScalizyBench';
        $helper->fields_value = [self::CONFIG_DEFECTS => $json];

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->l('Defects')],
                'input' => [[
                    'type' => 'textarea',
                    'label' => $this->l('Defects to produce (JSON list, [] for none)'),
                    'name' => self::CONFIG_DEFECTS,
                    'rows' => 8,
                    'desc' => sprintf(
                        'Catalog — %s. Hooks — %s. Example: [{"type": "sql_n_plus_1", "hook": "displayFooter", "iterations": 300}]',
                        implode(' ; ', $catalog),
                        implode(', ', self::HOOKS)
                    ),
                ]],
                'submit' => ['title' => $this->l('Save')],
            ],
        ]]);
    }
}
