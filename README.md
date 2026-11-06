# scalizy_bench — Scalizy front blocks

Module PrestaShop qui ajoute quatre blocs au front office : un produit à la une répété sur plusieurs emplacements, les produits les plus vus des 30 derniers jours, un classement de produits, et un widget HTML récupéré sur une URL distante.

Compatible PrestaShop 1.7 et 8.

## À l'installation

Aucun bloc n'est affiché : les quatre cases sont décochées.

## Configuration

Back-office, Modules > Scalizy front blocks : une section par bloc, avec sa case à cocher, son hook et ses réglages. On peut cocher un, plusieurs ou aucun bloc.

| Case | Bloc | Hooks | Réglages (défaut) | Code |
|---|---|---|---|---|
| Featured product | Le nom du produit à la une sur chaque emplacement | `displayFooter`, `displayHome` | `product_id` (1), `slots` (12) | `classes/block/ScalizyFeaturedProductBlock.php` |
| Popular products | Les 5 produits les plus vus des 30 derniers jours, comptés dans l'historique des vues (`ps_scalizy_product_view`) | `displayFooterAfter`, `displayNavFullWidth` | `views` (3 000 000) | `classes/block/ScalizyPopularProductsBlock.php` |
| Product ranking | Les 5 premiers produits, classés par confrontations deux à deux | `displayFooterBefore`, `displayNav1` | `products` (200), `rounds` (230) | `classes/block/ScalizyProductRankingBlock.php` |
| Remote widget | Le HTML servi par l'URL du widget, ou sa dernière copie si l'URL ne répond pas | `displayHeader`, `displayTop` | `url` (obligatoire) | `classes/block/ScalizyRemoteWidgetBlock.php` |

`views` est la taille de l'historique des vues, recréé à chaque enregistrement du bloc coché (quelques secondes pour 3 millions de vues). `products` et `rounds` sont le nombre de produits classés et le nombre de tours de confrontations.

Temps d'affichage mesurés en local (PrestaShop 8.2 et 1.7.8, page d'accueil, Docker sur un poste de développement) :

| Bloc | Réglage | Temps ajouté |
|---|---|---|
| Featured product | `slots` 300 | ~30 ms, 300 requêtes |
| Popular products | `views` 2 000 000 / 2 500 000 / 3 000 000 | ~240 ms / ~300 ms / ~400 à 500 ms |
| Product ranking | `products` 200, `rounds` 230 | ~300 ms |
| Remote widget | URL qui répond en 400 ms | ~400 ms |

Les réglages d'un bloc coché sont vérifiés à l'enregistrement : une valeur invalide est refusée avec son message, et rien n'est enregistré. Un bloc dont la configuration en base est invalide n'est pas affiché.

Clés de configuration : `SCALIZY_<BLOC>_ENABLED`, `SCALIZY_<BLOC>_HOOK` et `SCALIZY_<BLOC>_<RÉGLAGE>`, avec `<BLOC>` parmi `FEATURED_PRODUCT`, `POPULAR_PRODUCTS`, `PRODUCT_RANKING` et `REMOTE_WIDGET`. La désinstallation supprime ces clés et la table `ps_scalizy_product_view`.

Ajouter un bloc : une classe dans `classes/block/` qui implémente `ScalizyFrontBlock` (ses réglages et leur valeur par défaut, ses hooks, `enable()` appelé à chaque enregistrement du bloc coché, puis `render()`), déclarée dans `Scalizy_Bench::BLOCKS`.

## Installation

```bash
cp -r scalizy_bench <boutique>/modules/
php bin/console prestashop:module install scalizy_bench
```

Depuis la 1.2.x : `php bin/console prestashop:module upgrade scalizy_bench` remplace `SCALIZY_FRONT_BLOCKS` par les nouvelles clés, tous blocs décochés. Depuis la 1.4.0 : la même commande remplace les réglages `ms` par `views` (Popular products) et par `products` et `rounds` (Product ranking), et recrée l'historique des vues si Popular products est coché.

## Licence

AFL-3.0
