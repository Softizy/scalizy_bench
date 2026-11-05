# scalizy_bench

Module PrestaShop de banc pour la veille de régression Scalizy. Son code contient **volontairement** deux défauts de performance, pour que l'analyse de code d'une régression puisse aller jusqu'au correctif.

> Ne jamais installer ce module sur une boutique de production.

Compatible PrestaShop 1.7 et 8.

## Défauts

| Hook | Défaut | Scénario du Data Processor | Effet mesuré sur la page d'accueil |
|---|---|---|---|
| `displayFooter` | relit le même produit dans une boucle : une requête par emplacement (N+1) | `sql-n-plus-1` | +300 requêtes |
| `displayHeader` | attente bloquante de 400 ms | `ttfb-hook` | +400 ms de TTFB |

La requête répétée a la signature du scénario `sql-n-plus-1` :

```sql
SELECT pl.`name`, pl.`link_rewrite` FROM `ps_product_lang` pl WHERE pl.`id_product` = 42 AND pl.`id_lang` = 1 AND pl.`id_shop` = 1
```

## Configuration

Chaque défaut s'active ou se coupe dans la configuration du module (back-office, Modules > Scalizy bench), pour mesurer un avant/après. Les deux sont actifs à l'installation.

| Clé | Défaut | Rôle |
|---|---|---|
| `SCALIZY_BENCH_FOOTER_DEFECT` | 1 | active le bloc N+1 du pied de page |
| `SCALIZY_BENCH_HEADER_DEFECT` | 1 | active l'attente de l'en-tête |
| `SCALIZY_BENCH_PRODUCT_ID` | 42 | produit relu |
| `SCALIZY_BENCH_FOOTER_ITEMS` | 300 | emplacements du bloc, donc requêtes par page |
| `SCALIZY_BENCH_HEADER_DELAY_MS` | 400 | durée de l'attente, en ms |

## Installation

```bash
cp -r scalizy_bench <boutique>/modules/
php bin/console prestashop:module install scalizy_bench
```

## Licence

AFL-3.0
