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
        return ['products' => 60, 'refine_ms' => 400];
    }

    public static function hooks()
    {
        return ['displayFooterBefore', 'displayNav1'];
    }

    public function render(array $settings)
    {
        $products = max(2, (int) $settings['products']);
        $ratings = array_fill(1, $products, 1500.0);
        $deadline = microtime(true) + $settings['refine_ms'] / 1000;
        mt_srand($products);
        do {
            $first = mt_rand(1, $products);
            $second = mt_rand(1, $products);
            if ($first === $second) {
                continue;
            }
            $expected = 1 / (1 + pow(10, ($ratings[$second] - $ratings[$first]) / 400));
            $won = ($first * 7 + $second * 3) % 5 < 3 ? 1.0 : 0.0;
            $ratings[$first] += 16 * ($won - $expected);
            $ratings[$second] -= 16 * ($won - $expected);
        } while (microtime(true) < $deadline);

        arsort($ratings);

        Context::getContext()->smarty->assign('product_ranking', array_slice(array_keys($ratings), 0, self::SHOWN));

        return $this->module->display($this->module->getLocalPath() . $this->module->name . '.php', 'views/templates/hook/product_ranking.tpl');
    }
}
