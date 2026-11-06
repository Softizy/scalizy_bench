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

    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public static function settings()
    {
        return ['products' => 200, 'rounds' => 230];
    }

    public static function hooks()
    {
        return ['displayFooterBefore', 'displayNav1'];
    }

    public function enable(array $settings)
    {
        return true;
    }

    public function render(array $settings)
    {
        Context::getContext()->smarty->assign('product_ranking', $this->rank(max(2, $settings['products']), $settings['rounds']));

        return $this->module->display($this->module->getLocalPath() . $this->module->name . '.php', 'views/templates/hook/product_ranking.tpl');
    }

    private function rank($products, $rounds)
    {
        $ratings = array_fill(1, $products, 1500.0);
        for ($round = 0; $round < $rounds; ++$round) {
            for ($first = 1; $first < $products; ++$first) {
                for ($second = $first + 1; $second <= $products; ++$second) {
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
