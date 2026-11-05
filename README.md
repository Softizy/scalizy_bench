# scalizy_bench

Module PrestaShop de banc pour la veille de régression Scalizy. Il produit **sur demande** des défauts de performance réels, pour que l'analyse de code d'une régression puisse aller jusqu'au correctif.

> Ne jamais installer ce module sur une boutique de production.

Compatible PrestaShop 1.7 et 8.

## Neutre par défaut

À l'installation, aucun défaut n'est actif (`SCALIZY_BENCH_DEFECTS` vaut `[]`) : le module n'ajoute ni requête ni attente. On choisit ensuite le ou les défauts à produire, avec leurs paramètres.

## Configuration

Back-office, Modules > Scalizy bench : la clé `SCALIZY_BENCH_DEFECTS` contient une liste JSON de défauts. Chaque défaut a un `type` du catalogue, un `hook` où il se produit, et ses paramètres (valeur par défaut si absents). Une configuration invalide est refusée à l'enregistrement ; une valeur invalide écrite directement en base laisse le module neutre.

```json
[
  {"type": "sql_n_plus_1", "hook": "displayFooter", "iterations": 300, "product_id": 42},
  {"type": "blocking_wait", "hook": "displayHeader", "ms": 400}
]
```

Hooks disponibles : `displayHeader`, `displayTop`, `displayHome`, `displayFooter`.

## Catalogue

| Type | Défaut produit | Paramètres (défaut) | Code | Scénario du Data Processor |
|---|---|---|---|---|
| `sql_n_plus_1` | relit le même produit à chaque itération : une requête par itération (N+1) | `iterations` (300), `product_id` (42) | `classes/defect/ScalizyBenchSqlNPlusOneDefect.php` | `sql-n-plus-1` |
| `blocking_wait` | attente bloquante | `ms` (400) | `classes/defect/ScalizyBenchBlockingWaitDefect.php` | `ttfb-hook` |

La requête répétée par `sql_n_plus_1` a la signature du scénario `sql-n-plus-1` :

```sql
SELECT pl.`name`, pl.`link_rewrite` FROM `ps_product_lang` pl WHERE pl.`id_product` = 42 AND pl.`id_lang` = 1 AND pl.`id_shop` = 1
```

Ajouter un défaut : une classe dans `classes/defect/` qui implémente `ScalizyBenchDefect` (ses paramètres et leur valeur par défaut, puis `produce()`), déclarée dans `Scalizy_Bench::CATALOG`.

## Installation

```bash
cp -r scalizy_bench <boutique>/modules/
php bin/console prestashop:module install scalizy_bench
```

## Licence

AFL-3.0
