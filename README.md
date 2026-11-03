# gilles-g/db-anonymizer

Framework-agnostic PHP library that anonymizes database columns through table-scoped SQL `UPDATE`
statements. No bundle, no DI container — plain constructors.

## Usage

```php
$plan = new AnonymizationPlan(
    PoolAlias::of('_anon_pool'),
    Seed::of($_ENV['ANONYMIZER_SEED']),
    1000,
    [
        new TableAnonymization(
            TableName::of('users'),
            ColumnName::of('id'),
            ColumnName::of('id'),
            RowFilter::all(),
            [new ColumnRule(ColumnName::of('email'), $emailMask)],
        ),
    ],
);

(new Anonymizer($executor))->run($plan);
(new AnonymizationScript($dialect))->render($plan);
```

`Anonymizer::run()` executes the plan; `AnonymizationScript::render()` renders it as a replayable
`.sql` file instead. `PlanValidation::problemsOf($plan, $schemaInspector)` checks every table and
column exists before anything runs.

Values are drawn from a pool table generated once (`scripts/generate-pool.sh`) and joined on for
every row, so the size of the generated SQL depends on the schema, never on the row count.

## dump-loader integration

`AnonymizeStepFactory` implements [gilles-g/dump-loader](https://github.com/gilles-g/dump-loader)'s
`StepFactory` contract: the application describes the plan under `dump_loader.steps.anonymization`
and the bundle turns it into the `anonymize_database` step, an optional one checked by default.

```yaml
dump_loader:
    steps:
        anonymization:
            seed: '%env(ANONYMIZER_SEED)%'
            pool: _anon_pool
            pool_size: 1000
            tables:
                users:
                    identity: id
                    where: "role <> 'admin'"
                    columns:
                        email: { mask: email }
```

`pool` and `pool_size` are optional (`_anon_pool` and `1000`), and so are `identity` (`id`),
`pool_key` (the identity) and `where` (every row) on a table. Each column takes the options of the
`MaskFactory` whose `supports()` accepts its `mask` name.

Register the factory and give it the executor and the masks; autoconfiguration tags it:

```php
$services->set(AnonymizationPlanFactory::class)->args([tagged_iterator(MaskFactory::class)]);
$services->set(AnonymizeStepFactory::class)->autoconfigure()->args([service(StatementExecutor::class), service(AnonymizationPlanFactory::class)]);
```

The plan is built when the step is first needed, so a malformed `tables` entry fails the dashboard
with its reason rather than the container build.

To use `AnonymizeLoadedDatabase` without the configuration, build the plan yourself and tag it with
`DumpLoader\Step\Step`.

`gilles-g/dump-loader` is `require-dev` + `suggest` — this package works without it.

## Anonymization vs pseudonymization

`MD5(email)` or `LEFT(phone, 3)` are pseudonymization, not anonymization (GDPR): reversible, still
personal data. The result's anonymity depends entirely on the masks you configure.

## Development

```bash
make help
```

`make install`, then `make quality` and `make coverage` (100% of statements, enforced by CI).
