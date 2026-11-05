# scalizy_bench — Scalizy front blocks

Module PrestaShop qui ajoute quatre blocs au front office : un produit à la une répété sur plusieurs emplacements, les produits les plus vus des 30 derniers jours, un classement de produits, et un widget HTML récupéré sur une URL distante.

Compatible PrestaShop 1.7 et 8.

## À l'installation

Aucun bloc n'est affiché : les quatre cases sont décochées.

## Configuration

Back-office, Modules > Scalizy front blocks : une section par bloc, avec sa case à cocher, son hook et ses réglages. On peut cocher un, plusieurs ou aucun bloc.

| Case | Bloc | Hooks | Réglages (défaut) | Code |
|---|---|---|---|---|
| `sql-n-plus-1` | Featured product : le nom du produit à la une sur chaque emplacement | `displayFooter`, `displayHome` | `product_id` (1), `slots` (12) | `classes/block/ScalizyFeaturedProductBlock.php` |
| `sql-slow-query` | Popular products : les 5 produits les plus vus des 30 derniers jours, comptés dans l'historique des vues (`ps_scalizy_product_view`) | `displayFooterAfter`, `displayNavFullWidth` | `ms` (400) | `classes/block/ScalizyPopularProductsBlock.php` |
| `ttfb-hook` | Product ranking : les 5 premiers de 200 produits classés par confrontations deux à deux | `displayFooterBefore`, `displayNav1` | `ms` (300) | `classes/block/ScalizyProductRankingBlock.php` |
| `ttfb-external-call` | Remote widget : le HTML servi par l'URL du widget, ou sa dernière copie si l'URL ne répond pas | `displayHeader`, `displayTop` | `url` (obligatoire) | `classes/block/ScalizyRemoteWidgetBlock.php` |

`ms` est la durée visée pour le bloc, mesurée sur la boutique à chaque enregistrement : Popular products recrée son historique de vues jusqu'à ce que son comptage prenne ce temps (quelques secondes pour ~2 millions de vues), Product ranking en déduit son nombre de tours (`SCALIZY_PRODUCT_RANKING_ROUNDS`).

Les réglages d'un bloc coché sont vérifiés à l'enregistrement : une valeur invalide est refusée avec son message, et rien n'est enregistré. Un bloc dont la configuration en base est invalide n'est pas affiché.

Clés de configuration : `SCALIZY_<BLOC>_ENABLED`, `SCALIZY_<BLOC>_HOOK` et `SCALIZY_<BLOC>_<RÉGLAGE>`, avec `<BLOC>` parmi `FEATURED_PRODUCT`, `POPULAR_PRODUCTS`, `PRODUCT_RANKING` et `REMOTE_WIDGET`. La désinstallation supprime ces clés et la table `ps_scalizy_product_view`.

Ajouter un bloc : une classe dans `classes/block/` qui implémente `ScalizyFrontBlock` (ses réglages et leur valeur par défaut, ses hooks, `enable()` appelé à chaque enregistrement du bloc coché, puis `render()`), déclarée dans `Scalizy_Bench::BLOCKS`.

## Installation

```bash
cp -r scalizy_bench <boutique>/modules/
php bin/console prestashop:module install scalizy_bench
```

Depuis la 1.2.x : `php bin/console prestashop:module upgrade scalizy_bench` remplace `SCALIZY_FRONT_BLOCKS` par les nouvelles clés, tous blocs décochés. Depuis la 1.3.0 : la même commande remplace `refine_ms` par `products` et `rounds`. Depuis la 1.3.1 : elle remplace `products` et `rounds` par `ms` (300), ajoute Popular products décoché et recalibre le classement s'il est coché.

## Licence

AFL-3.0
