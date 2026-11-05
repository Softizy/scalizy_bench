# scalizy_bench — Scalizy front blocks

Module PrestaShop qui ajoute des blocs au front office : un produit à la une répété sur plusieurs emplacements, et un widget HTML récupéré sur une URL distante.

Compatible PrestaShop 1.7 et 8.

## À l'installation

Aucun bloc n'est affiché : `SCALIZY_FRONT_BLOCKS` vaut `[]`.

## Configuration

Back-office, Modules > Scalizy front blocks : `SCALIZY_FRONT_BLOCKS` contient une liste JSON de blocs. Chaque bloc a un `type`, le `hook` où il s'affiche (parmi ceux de son type), et ses réglages (valeur par défaut si absents). Une configuration invalide est refusée à l'enregistrement et l'ancienne valeur est conservée ; une valeur invalide écrite directement en base n'affiche aucun bloc.

```json
[
  {"type": "featured_product", "hook": "displayFooter", "product_id": 42, "slots": 12},
  {"type": "remote_widget", "hook": "displayHeader", "url": "https://widgets.example.com/banner"}
]
```

## Blocs

| Type | Affiche | Hooks | Réglages (défaut) | Code |
|---|---|---|---|---|
| `featured_product` | le nom du produit à la une sur chaque emplacement | `displayHome`, `displayFooter` | `product_id` (1), `slots` (12) | `classes/block/ScalizyFeaturedProductBlock.php` |
| `remote_widget` | le HTML servi par l'URL du widget, ou sa dernière copie si l'URL ne répond pas | `displayHeader`, `displayTop` | `url` (obligatoire) | `classes/block/ScalizyRemoteWidgetBlock.php` |

Ajouter un bloc : une classe dans `classes/block/` qui implémente `ScalizyFrontBlock` (ses réglages et leur valeur par défaut, ses hooks, puis `render()`), déclarée dans `Scalizy_Bench::BLOCKS`.

## Installation

```bash
cp -r scalizy_bench <boutique>/modules/
php bin/console prestashop:module install scalizy_bench
```

## Licence

AFL-3.0
