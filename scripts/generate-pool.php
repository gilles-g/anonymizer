<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

/**
 * @return array<string, string>
 */
function parse_options(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (!str_starts_with($arg, '--') || !str_contains($arg, '=')) {
            fwrite(STDERR, "Unexpected argument: {$arg}\n");
            exit(1);
        }
        [$name, $value] = explode('=', substr($arg, 2), 2);
        $options[$name] = $value;
    }

    foreach (['columns', 'size', 'locale', 'seed', 'table', 'dialect'] as $required) {
        if (!isset($options[$required])) {
            fwrite(STDERR, "Missing --{$required}\n");
            exit(1);
        }
    }

    return $options;
}

/**
 * @return array<int, array{formatter: string, column: string}>
 */
function parse_columns(string $spec): array
{
    $columns = [];
    foreach (explode(',', $spec) as $pair) {
        if (!str_contains($pair, ':')) {
            fwrite(STDERR, "Invalid column spec: {$pair} (expected formatter:column)\n");
            exit(1);
        }
        [$formatter, $column] = explode(':', $pair, 2);
        $columns[] = ['formatter' => $formatter, 'column' => $column];
    }

    return $columns;
}

function quote_identifier(string $dialect, string $identifier): string
{
    return 'postgres' === $dialect ? '"'.$identifier.'"' : '`'.$identifier.'`';
}

function sql_type_for(array $values): string
{
    $width = 0;
    foreach ($values as $value) {
        $width = max($width, mb_strlen((string) $value));
    }

    if ($width > 255) {
        return 'TEXT';
    }

    $width = (int) (16 * ceil(max($width, 1) / 16));

    return "VARCHAR({$width})";
}

$options = parse_options($argv);
$columns = parse_columns($options['columns']);
$size = (int) $options['size'];
$dialect = $options['dialect'];
$table = $options['table'];

$faker = Faker\Factory::create($options['locale']);
$faker->seed(crc32($options['seed']));

$rows = [];
for ($n = 0; $n < $size; ++$n) {
    $row = ['n' => $n];
    foreach ($columns as $spec) {
        $row[$spec['column']] = $faker->{$spec['formatter']}();
    }
    $rows[] = $row;
}

$columnTypes = ['n' => 'INT PRIMARY KEY'];
foreach ($columns as $spec) {
    $columnTypes[$spec['column']] = sql_type_for(array_column($rows, $spec['column']));
}

$definitions = [];
foreach ($columnTypes as $column => $type) {
    $definitions[] = quote_identifier($dialect, $column).' '.$type;
}

echo 'DROP TABLE IF EXISTS '.quote_identifier($dialect, $table).";\n";
echo 'CREATE TEMPORARY TABLE '.quote_identifier($dialect, $table).' ('.implode(', ', $definitions).");\n";

$columnNames = implode(', ', array_map(
    static fn (string $column): string => quote_identifier($dialect, $column),
    array_keys($columnTypes),
));

foreach (array_chunk($rows, 250) as $batch) {
    $values = [];
    foreach ($batch as $row) {
        $literals = [];
        foreach ($row as $value) {
            $literals[] = is_int($value) ? (string) $value : "'".str_replace("'", "''", (string) $value)."'";
        }
        $values[] = '('.implode(', ', $literals).')';
    }

    echo 'INSERT INTO '.quote_identifier($dialect, $table)." ({$columnNames}) VALUES ".implode(', ', $values).";\n";
}
