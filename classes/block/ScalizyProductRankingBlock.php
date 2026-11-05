<?php
/**
 * @author    Softizy
 * @copyright Softizy
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ScalizyProductRankingBlock implements ScalizyFrontBlock
{
    const SHOWN = 5;
    const PRODUCTS = 200;
    const ROUNDS = 'SCALIZY_PRODUCT_RANKING_ROUNDS';
    const SAMPLE_ROUNDS = 10;

    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public static function settings()
    {
        return ['ms' => 300];
    }

    public static function hooks()
    {
        return ['displayFooterBefore', 'displayNav1'];
    }

    public function enable(array $settings)
    {
        $start = microtime(true);
        $this->rank(self::SAMPLE_ROUNDS);
        $roundMs = (microtime(true) - $start) * 1000 / self::SAMPLE_ROUNDS;

        return Configuration::updateValue(self::ROUNDS, max(1, (int) round($settings['ms'] / $roundMs)));
    }

    public function render(array $settings)
    {
        Context::getContext()->smarty->assign('product_ranking', $this->rank((int) Configuration::get(self::ROUNDS)));

        return $this->module->display($this->module->getLocalPath() . $this->module->name . '.php', 'views/templates/hook/product_ranking.tpl');
    }

    private function rank($rounds)
    {
        $ratings = array_fill(1, self::PRODUCTS, 1500.0);
        for ($round = 0; $round < $rounds; ++$round) {
            for ($first = 1; $first < self::PRODUCTS; ++$first) {
                for ($second = $first + 1; $second <= self::PRODUCTS; ++$second) {
                    $expected = 1 / (1 + pow(10, ($ratings[$second] - $ratings[$first]) / 400));
                    $won = ($first * 7 + $second * 3 + $round) % 5 < 3 ? 1.0 : 0.0;
                    $ratings[$first] += 16 * ($won - $expected);
                    $ratings[$second] -= 16 * ($won - $expected);
                }
            }
        }

        arsort($ratings);

        return array_slice(array_keys($ratings), 0, self::SHOWN);
    }
}
