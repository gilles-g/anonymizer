# db-anonymizer

Bibliothèque PHP pure (pas de bundle Symfony, pas de container DI) qui anonymise une base de
données par `UPDATE` SQL ciblés, table par table, sans jamais faire remonter de donnée réelle en
PHP. `src/DumpLoader/AnonymizeLoadedDatabase` implémente le contrat `DumpLoader\Step\OptionalStep`
exposé par le bundle `gilles-g/dump-loader`, pour qui veut l'utiliser comme étape d'un chargement de
dump — c'est le seul point de couplage avec ce bundle, et il reste optionnel (`require-dev` +
`suggest`).

## Conventions du domaine

- Classes `final readonly`, pas d'héritage, composition uniquement.
- `TableName` / `ColumnName` : constructeur privé + `::of()`. Jamais de string nu pour un identifiant.
- `SqlExpression` : jamais de `toSql(): string`. Texte structurel, identifiants et littéraux liés
  restent séparés jusqu'au rendu par le `Dialect`.
- `AnonymizationStatement` est une structure rendue par le dialecte, jamais une chaîne assemblée
  dans le domaine.
- Objets nuls plutôt que des paramètres `?X $x = null` (`RowFilter::all()`).
- Le port du domaine ne s'appelle jamais `Connection` (collision avec `Doctrine\DBAL\Connection`).
- Pas de transaction dans le port d'exécution : le plan doit rester rejouable, pas atomique.

## Outillage

`make help` avant toute commande. `make quality` pose le style + PHPStan level max + la suite de
tests ; `make coverage` exige 100 % des statements.
