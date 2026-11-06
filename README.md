# scalizy_bench — simulation de dégradations de performance

Outil de test et de développement pour PrestaShop 1.7 et 8. Le dépôt contient deux modules :

- `scalizy_bench` (racine du dépôt) : le panneau de pilotage. Il simule un TTFB, un temps SQL ou un nombre de requêtes SQL dégradés sur le front office, en configurant un module cible. Il n'a aucun hook de vitrine ni aucun code fautif : le temps mesuré est celui du module cible.
- `product_highlights` (dossier `product_highlights/`) : le module cible, « Mises en avant produits ». Il affiche un produit à la une, les produits populaires, des tendances et un bandeau partenaire. Son code contient de vrais défauts de performance (calcul coûteux sans cache, appel HTTP bloquant, requête sans index, N+1), pas d'attente artificielle : c'est ce code qu'une analyse de performance doit trouver et corriger. Il fonctionne seul, avec son propre formulaire de configuration.

## À l'installation

Rien n'est actif : les trois simulations du bench et les quatre blocs de `product_highlights` sont désactivés.

## Configuration du bench

Back-office, Modules > Scalizy bench :
- « Module cible à dégrader » : seuls les modules cibles compatibles et installés sont proposés, `product_highlights` pour l'instant ;
- une section par simulation, avec sa case « Activer », le hook où appliquer la dégradation et son intensité. Les simulations se cochent seules ou ensemble.

L'enregistrement écrit la configuration du module cible. Une simulation décochée désactive le bloc correspondant de la cible.

| Simulation | Bloc de `product_highlights` activé | Réglages du bench (défaut) | Code fautif |
|---|---|---|---|
| Simuler un TTFB dégradé, cause « calcul » | Tendances : classement de 200 produits par confrontations deux à deux, recalculé à chaque page sans cache | hook (`displayFooterBefore`), tours de classement (260 sur PrestaShop 8, 290 sur 1.7) | `product_highlights/classes/block/ProductHighlightsTrendingBlock.php` |
| Simuler un TTFB dégradé, cause « appel externe » | Bandeau partenaire : HTML lu sur une URL à chaque page, sans cache ni délai maximal | hook, URL appelée (obligatoire) | `product_highlights/classes/block/ProductHighlightsPartnerBannerBlock.php` |
| Simuler un temps SQL dégradé | Produits populaires : comptage des vues des 30 derniers jours sur `ps_product_highlights_view`, sans index sur la boutique et la date | hook (`displayFooterAfter`), lignes de l'historique de vues (3 000 000) | `product_highlights/classes/block/ProductHighlightsPopularBlock.php` |
| Simuler un nombre de requêtes SQL dégradé (N+1) | Produit à la une : le même produit relu une fois par emplacement | hook (`displayFooter`), nombre de requêtes ajoutées par page (300) | `product_highlights/classes/block/ProductHighlightsFeaturedBlock.php` |

Hooks proposés : `displayHeader`, `displayNav1`, `displayNavFullWidth`, `displayTop`, `displayHome` (page d'accueil uniquement), `displayFooterBefore`, `displayFooter`, `displayFooterAfter`. Le TTFB n'a qu'une cause à la fois.

L'historique de vues de la cible est recréé à chaque enregistrement de la simulation de temps SQL cochée (quelques secondes pour 3 millions de lignes), et vidé quand elle est décochée.

Temps ajoutés mesurés en local (PrestaShop 8.2 et 1.7.8, page d'accueil, Docker sur un poste de développement) :

| Simulation | Réglage | Effet |
|---|---|---|
| TTFB dégradé, calcul | 260 tours (8.2), 290 tours (1.7.8) | ~350 ms |
| TTFB dégradé, appel externe | URL qui répond en 400 ms | ~400 ms |
| Temps SQL dégradé | 3 000 000 lignes | ~400 à 500 ms, une requête |
| Requêtes SQL (N+1) | 300 requêtes | +300 requêtes, ~20 à 140 ms |
| Les trois ensemble | défauts | ~0,7 à 0,9 s |

Les réglages d'une simulation cochée sont vérifiés à l'enregistrement : une valeur invalide est refusée avec son message, et rien n'est enregistré.

Clés de configuration du bench : `SCALIZY_BENCH_TARGET`, `SCALIZY_BENCH_<SIMULATION>_ENABLED`, `SCALIZY_BENCH_<SIMULATION>_HOOK` et `SCALIZY_BENCH_<SIMULATION>_<RÉGLAGE>`, avec `<SIMULATION>` parmi `TTFB` (réglages `CAUSE`, `ROUNDS`, `URL`), `SQL_TIME` (`VIEWS`) et `SQL_QUERIES` (`QUERIES`). La désinstallation du bench remet la cible à neutre et supprime ces clés.

Ajouter un module cible : une entrée dans `Scalizy_Bench::TARGETS` et une classe dans `classes/target/` dont `apply()` traduit chaque simulation en réglages de la cible.

## Installation

```bash
cp -r scalizy_bench <boutique>/modules/
ln -s scalizy_bench/product_highlights <boutique>/modules/product_highlights   # ou une copie du dossier
php bin/console prestashop:module install product_highlights
php bin/console prestashop:module install scalizy_bench
```

Depuis une version antérieure du bench : `php bin/console prestashop:module upgrade scalizy_bench` (PrestaShop 8). En 2.0.0, la mise à jour retire les hooks et la table de vues du bench, supprime les réglages des versions 1.x et laisse les trois simulations décochées. Le code fautif est désormais dans `product_highlights`, à installer avant de réactiver une simulation. En 2.0.1, la mise à jour passe les tours de classement de 230 à 260 s'ils étaient restés à 230. En 2.0.2, sur PrestaShop 1.7 seulement, elle les passe de 260 à 290 s'ils étaient restés à 260.

## Licence

AFL-3.0
