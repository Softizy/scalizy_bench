# scalizy_bench — simulation de dégradations de performance

Outil de test et de développement pour PrestaShop 1.7 et 8 : il simule un TTFB, un temps SQL ou un nombre de requêtes SQL dégradés sur le front office. Chaque dégradation est produite par du vrai code fautif (calcul coûteux sans cache, appel HTTP bloquant, requête sans index, N+1), pas par une attente artificielle : c'est ce code qu'une analyse de performance doit pouvoir trouver et corriger.

## À l'installation

Aucune simulation n'est active : les trois cases sont décochées.

## Configuration

Back-office, Modules > Scalizy bench : une section par simulation, avec sa case « Activer », le hook où appliquer la dégradation et son intensité. Les simulations se cochent seules ou ensemble, sur le même hook ou sur des hooks différents.

| Simulation | Code fautif | Réglages (défaut) | Code |
|---|---|---|---|
| Simuler un TTFB dégradé | Cause « calcul » : classement de 200 produits par confrontations deux à deux, recalculé à chaque page sans cache. Cause « appel externe » : HTML d'un widget lu sur une URL à chaque page, sans cache ni délai maximal | hook (`displayFooterBefore`), cause (calcul), tours de classement (230), URL appelée (obligatoire pour la cause « appel externe ») | `classes/block/ScalizyProductRankingBlock.php`, `classes/block/ScalizyRemoteWidgetBlock.php` |
| Simuler un temps SQL dégradé | Comptage des vues des 30 derniers jours sur `ps_scalizy_product_view`, sans index sur la boutique et la date | hook (`displayFooterAfter`), lignes de la table de vues (3 000 000) | `classes/block/ScalizyPopularProductsBlock.php` |
| Simuler un nombre de requêtes SQL dégradé (N+1) | Le même produit relu une fois par emplacement au lieu d'une seule requête | hook (`displayFooter`), nombre de requêtes ajoutées par page (300) | `classes/block/ScalizyFeaturedProductBlock.php` |

Hooks proposés pour chaque simulation : `displayHeader`, `displayNav1`, `displayNavFullWidth`, `displayTop`, `displayHome` (page d'accueil uniquement), `displayFooterBefore`, `displayFooter`, `displayFooterAfter`.

La table de vues est recréée à chaque enregistrement de la simulation de temps SQL cochée (quelques secondes pour 3 millions de lignes).

Temps ajoutés mesurés en local (PrestaShop 8.2 et 1.7.8, page d'accueil, Docker sur un poste de développement) :

| Simulation | Réglage | Effet |
|---|---|---|
| TTFB dégradé, calcul | 230 tours | ~300 ms |
| TTFB dégradé, appel externe | URL qui répond en 400 ms | ~400 ms |
| Temps SQL dégradé | 3 000 000 lignes | ~400 à 500 ms, une requête |
| Requêtes SQL (N+1) | 300 requêtes | +300 requêtes, ~20 à 120 ms |
| Les trois ensemble | défauts | ~0,7 à 1,1 s |

Les réglages d'une simulation cochée sont vérifiés à l'enregistrement : une valeur invalide est refusée avec son message, et rien n'est enregistré. Une simulation dont la configuration en base est invalide ne s'applique pas.

Clés de configuration : `SCALIZY_BENCH_<SIMULATION>_ENABLED`, `SCALIZY_BENCH_<SIMULATION>_HOOK` et `SCALIZY_BENCH_<SIMULATION>_<RÉGLAGE>`, avec `<SIMULATION>` parmi `TTFB` (réglages `CAUSE`, `ROUNDS`, `URL`), `SQL_TIME` (`VIEWS`) et `SQL_QUERIES` (`QUERIES`). La désinstallation supprime ces clés et la table `ps_scalizy_product_view`.

Ajouter une simulation : une entrée dans `Scalizy_Bench::SIMULATIONS` (titre, hook par défaut, réglages) et une classe dans `classes/block/` qui implémente `ScalizyFrontBlock` (`enable()`, appelé à chaque enregistrement de la simulation cochée, puis `render()`).

## Installation

```bash
cp -r scalizy_bench <boutique>/modules/
php bin/console prestashop:module install scalizy_bench
```

Depuis une version antérieure : `php bin/console prestashop:module upgrade scalizy_bench`. En 1.5.0, la mise à jour remplace les réglages par bloc des versions 1.3 et 1.4 par les trois simulations, toutes décochées, et supprime la table de vues.

## Licence

AFL-3.0
