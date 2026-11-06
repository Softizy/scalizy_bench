<?php
/**
 * Test and development tool: degrades the TTFB, SQL time or SQL query count of a target module, through the target's
 * own configuration, so that the slowdown is measured on the target's real faulty code.
 *
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/target/ScalizyBenchProductHighlightsTarget.php';

class Scalizy_Bench extends Module
{
    const TARGETS = [
        ScalizyBenchProductHighlightsTarget::MODULE => ['class' => 'ScalizyBenchProductHighlightsTarget', 'title' => 'product_highlights : Mises en avant produits'],
    ];

    const TARGET = 'SCALIZY_BENCH_TARGET';

    const HOOKS = [
        'displayHeader' => 'displayHeader : en-tête de page (balise head)',
        'displayNav1' => 'displayNav1 : barre de navigation',
        'displayNavFullWidth' => 'displayNavFullWidth : sous la navigation',
        'displayTop' => 'displayTop : haut de page',
        'displayHome' => 'displayHome : page d\'accueil uniquement',
        'displayFooterBefore' => 'displayFooterBefore : avant le pied de page',
        'displayFooter' => 'displayFooter : pied de page',
        'displayFooterAfter' => 'displayFooterAfter : après le pied de page',
    ];

    const SIMULATIONS = [
        'SCALIZY_BENCH_TTFB' => [
            'title' => 'Simuler un TTFB dégradé',
            'desc' => 'Ralentit la génération de chaque page où le hook s\'exécute, sans requête SQL supplémentaire.',
            'hook' => 'displayFooterBefore',
            'settings' => [
                'cause' => ['label' => 'Cause', 'default' => 'compute', 'choices' => [
                    'compute' => 'Calcul PHP coûteux dans le hook (bloc Tendances du module cible)',
                    'remote' => 'Appel HTTP externe bloquant, sans cache ni délai maximal (bloc Bandeau partenaire du module cible)',
                ]],
                'rounds' => ['label' => 'Intensité du calcul : tours de classement', 'default' => 260, 'max' => 5000, 'cause' => 'compute',
                    'desc' => 'Cause « calcul ». 260 tours sur 200 produits ajoutent environ 300 à 350 ms par page.'],
                'url' => ['label' => 'URL appelée', 'default' => '', 'cause' => 'remote',
                    'desc' => 'Cause « appel externe ». La page attend la réponse de cette URL : une URL qui répond en 400 ms ajoute environ 400 ms.'],
            ],
        ],
        'SCALIZY_BENCH_SQL_TIME' => [
            'title' => 'Simuler un temps SQL dégradé',
            'desc' => 'Ajoute à chaque page où le hook s\'exécute une requête lente : le bloc Produits populaires du module cible compte son historique de vues, sans index adapté.',
            'hook' => 'displayFooterAfter',
            'settings' => [
                'views' => ['label' => 'Intensité : lignes de la table de vues', 'default' => 3000000, 'max' => 20000000,
                    'desc' => 'Historique de vues du module cible, recréé à l\'enregistrement (quelques secondes). 3 000 000 de lignes ajoutent environ 400 à 500 ms par page.'],
            ],
        ],
        'SCALIZY_BENCH_SQL_QUERIES' => [
            'title' => 'Simuler un nombre de requêtes SQL dégradé (N+1)',
            'desc' => 'Le bloc Produit à la une du module cible relit le même produit une fois par emplacement, au lieu d\'une seule requête, à chaque page où le hook s\'exécute.',
            'hook' => 'displayFooter',
            'settings' => [
                'queries' => ['label' => 'Nombre de requêtes ajoutées par page', 'default' => 300, 'max' => 10000,
                    'desc' => '300 requêtes ajoutent environ 20 à 120 ms par page selon la boutique.'],
            ],
        ],
    ];

    public function __construct()
    {
        $this->name = 'scalizy_bench';
        $this->tab = 'front_office_features';
        $this->version = '2.0.1';
        $this->author = 'Softizy';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'Scalizy bench : simulation de dégradations';
        $this->description = 'Outil de test et de développement : dégrade le TTFB, le temps SQL ou le nombre de requêtes SQL d\'un module cible, avec son propre code fautif.';
    }

    public function install()
    {
        return parent::install() && $this->resetSimulations();
    }

    public function uninstall()
    {
        $this->neutralizeTarget();
        foreach (array_keys(self::SIMULATIONS) as $config) {
            foreach (self::fieldNames($config) as $name) {
                Configuration::deleteByName($name);
            }
        }
        Configuration::deleteByName(self::TARGET);

        return parent::uninstall();
    }

    public function resetSimulations()
    {
        Configuration::updateValue(self::TARGET, ScalizyBenchProductHighlightsTarget::MODULE);
        foreach (self::SIMULATIONS as $config => $simulation) {
            Configuration::updateValue($config . '_ENABLED', 0);
            Configuration::updateValue($config . '_HOOK', $simulation['hook']);
            foreach ($simulation['settings'] as $name => $setting) {
                Configuration::updateValue($config . '_' . strtoupper($name), $setting['default']);
            }
        }

        return true;
    }

    public function neutralizeTarget()
    {
        $target = self::installedTarget((string) Configuration::get(self::TARGET));
        if (null !== $target) {
            foreach (array_keys(self::SIMULATIONS) as $config) {
                $target->apply($config, false, '', []);
            }
        }

        return true;
    }

    private static function installedTarget($module)
    {
        if (!isset(self::TARGETS[$module]) || !Module::isInstalled($module)) {
            return null;
        }
        $class = self::TARGETS[$module]['class'];

        return new $class();
    }

    /**
     * @param array<string, string> $values hook and settings of the simulation, as entered
     *
     * @return array{hook: string, settings: array<string, int|string>}
     *
     * @throws InvalidArgumentException when the hook or a setting is not one the simulation accepts
     */
    public static function parseSimulation($config, array $values)
    {
        if (!isset(self::HOOKS[$values['hook']])) {
            throw new InvalidArgumentException('hook inconnu');
        }

        $definitions = self::SIMULATIONS[$config]['settings'];
        $cause = isset($definitions['cause']) ? (string) $values['cause'] : null;
        if (null !== $cause && !isset($definitions['cause']['choices'][$cause])) {
            throw new InvalidArgumentException('cause inconnue');
        }

        $settings = [];
        foreach ($definitions as $name => $setting) {
            $value = trim((string) $values[$name]);
            if (isset($setting['choices'])) {
                $settings[$name] = $value;
            } elseif (is_int($setting['default'])) {
                if (!ctype_digit($value) || (int) $value < 1 || (int) $value > $setting['max']) {
                    throw new InvalidArgumentException(sprintf('« %s » doit être un nombre entier entre 1 et %d', $setting['label'], $setting['max']));
                }
                $settings[$name] = (int) $value;
            } elseif (isset($setting['cause']) && $setting['cause'] !== $cause) {
                $settings[$name] = $value;
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
        foreach (array_keys(self::SIMULATIONS[$config]['settings']) as $name) {
            $values[$name] = (string) Configuration::get($config . '_' . strtoupper($name));
        }

        return $values;
    }

    private static function fieldNames($config)
    {
        $names = ['enabled' => $config . '_ENABLED', 'hook' => $config . '_HOOK'];
        foreach (array_keys(self::SIMULATIONS[$config]['settings']) as $name) {
            $names[$name] = $config . '_' . strtoupper($name);
        }

        return $names;
    }

    public function getContent()
    {
        if (!Tools::isSubmit('submitScalizyBench')) {
            return $this->renderForm();
        }

        $submitted = [];
        $errors = [];
        $module = (string) Tools::getValue(self::TARGET);
        $target = self::installedTarget($module);
        if (null === $target) {
            $errors[] = sprintf('Module cible : « %s » n\'est pas un module cible compatible installé.', $module);
        }
        foreach (self::SIMULATIONS as $config => $simulation) {
            $enabled = (bool) Tools::getValue($config . '_ENABLED_on');
            $values = [];
            foreach (self::fieldNames($config) as $key => $name) {
                if ('enabled' !== $key) {
                    $values[$key] = (string) Tools::getValue($name);
                }
            }
            if ($enabled) {
                try {
                    self::parseSimulation($config, $values);
                } catch (InvalidArgumentException $e) {
                    $errors[] = sprintf('%s : %s.', $simulation['title'], $e->getMessage());
                }
            }
            $submitted[$config] = ['enabled' => $enabled, 'values' => $values];
        }

        if ([] !== $errors) {
            return $this->displayError($errors) . $this->renderForm();
        }

        Configuration::updateValue(self::TARGET, $module);
        foreach ($submitted as $config => $simulation) {
            Configuration::updateValue($config . '_ENABLED', $simulation['enabled'] ? 1 : 0);
            if (!$simulation['enabled']) {
                $target->apply($config, false, '', []);
                continue;
            }
            foreach (self::fieldNames($config) as $key => $name) {
                if ('enabled' !== $key) {
                    Configuration::updateValue($name, $simulation['values'][$key]);
                }
            }
            $values = self::parseSimulation($config, $simulation['values']);
            $target->apply($config, true, $values['hook'], $values['settings']);
        }

        return $this->displayConfirmation('Simulations enregistrées.') . $this->renderForm();
    }

    private function renderForm()
    {
        $targets = [];
        foreach (self::TARGETS as $module => $target) {
            if (Module::isInstalled($module)) {
                $targets[$module] = $target['title'];
            }
        }
        $forms = [['form' => [
            'legend' => ['title' => 'Module cible à dégrader'],
            'input' => [[
                'type' => 'select',
                'label' => 'Module cible',
                'name' => self::TARGET,
                'desc' => [] === $targets ? 'Aucun module cible compatible n\'est installé : installez product_highlights.' : 'Les dégradations activées ci-dessous s\'appliquent à ce module, avec son propre code.',
                'options' => ['query' => self::options($targets), 'id' => 'id', 'name' => 'name'],
            ]],
            'submit' => ['title' => 'Enregistrer'],
        ]]];
        $fieldsValue = [self::TARGET => Configuration::get(self::TARGET)];
        foreach (self::SIMULATIONS as $config => $simulation) {
            $inputs = [[
                'type' => 'checkbox',
                'label' => 'Simulation',
                'name' => $config . '_ENABLED',
                'desc' => $simulation['desc'],
                'values' => ['query' => [['id' => 'on', 'name' => 'Activer : ' . $simulation['title']]], 'id' => 'id', 'name' => 'name'],
            ], [
                'type' => 'select',
                'label' => 'Hook où appliquer la dégradation',
                'name' => $config . '_HOOK',
                'options' => ['query' => self::options(self::HOOKS), 'id' => 'id', 'name' => 'name'],
            ]];
            foreach ($simulation['settings'] as $name => $setting) {
                $input = ['label' => $setting['label'], 'name' => $config . '_' . strtoupper($name)];
                if (isset($setting['choices'])) {
                    $inputs[] = $input + ['type' => 'select', 'options' => ['query' => self::options($setting['choices']), 'id' => 'id', 'name' => 'name']];
                } else {
                    $inputs[] = $input + ['type' => 'text', 'desc' => $setting['desc']];
                }
            }
            foreach (self::fieldNames($config) as $key => $name) {
                $fieldsValue['enabled' === $key ? $name . '_on' : $name] = Configuration::get($name);
            }
            $forms[] = ['form' => [
                'legend' => ['title' => $simulation['title']],
                'input' => $inputs,
                'submit' => ['title' => 'Enregistrer'],
            ]];
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitScalizyBench';
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
