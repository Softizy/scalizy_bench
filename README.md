# scalizy_bench — Scalizy front blocks

Module PrestaShop qui ajoute trois blocs au front office : un produit à la une répété sur plusieurs emplacements, un classement de produits, et un widget HTML récupéré sur une URL distante.

Compatible PrestaShop 1.7 et 8.

## À l'installation

Aucun bloc n'est affiché : les trois cases sont décochées.

## Configuration

Back-office, Modules > Scalizy front blocks : une section par bloc, avec sa case à cocher, son hook et ses réglages. On peut cocher un, plusieurs ou aucun bloc.

| Case | Bloc | Hooks | Réglages (défaut) | Code |
|---|---|---|---|---|
| SQL | Featured product : le nom du produit à la une sur chaque emplacement | `displayFooter`, `displayHome` | `product_id` (1), `slots` (12) | `classes/block/ScalizyFeaturedProductBlock.php` |
| Hook | Product ranking : les 5 premiers produits d'un classement par confrontations deux à deux, sur `rounds` tours | `displayFooterBefore`, `displayNav1` | `products` (200), `rounds` (300) | `classes/block/ScalizyProductRankingBlock.php` |
| TTFB | Remote widget : le HTML servi par l'URL du widget, ou sa dernière copie si l'URL ne répond pas | `displayHeader`, `displayTop` | `url` (obligatoire) | `classes/block/ScalizyRemoteWidgetBlock.php` |

Les réglages d'un bloc coché sont vérifiés à l'enregistrement : une valeur invalide est refusée avec son message, et rien n'est enregistré. Un bloc dont la configuration en base est invalide n'est pas affiché.

Clés de configuration : `SCALIZY_<BLOC>_ENABLED`, `SCALIZY_<BLOC>_HOOK` et `SCALIZY_<BLOC>_<RÉGLAGE>`, avec `<BLOC>` parmi `FEATURED_PRODUCT`, `PRODUCT_RANKING` et `REMOTE_WIDGET`.

Ajouter un bloc : une classe dans `classes/block/` qui implémente `ScalizyFrontBlock` (ses réglages et leur valeur par défaut, ses hooks, puis `render()`), déclarée dans `Scalizy_Bench::BLOCKS`.

## Installation

```bash
cp -r scalizy_bench <boutique>/modules/
php bin/console prestashop:module install scalizy_bench
```

Depuis la 1.2.x : `php bin/console prestashop:module upgrade scalizy_bench` remplace `SCALIZY_FRONT_BLOCKS` par les nouvelles clés, tous blocs décochés. Depuis la 1.3.0 : la même commande remplace `refine_ms` par `products` et `rounds`.

## Licence

AFL-3.0
