#!/usr/bin/env bash
set -euo pipefail

usage() {
    cat <<'EOF'
Usage: scripts/generate-pool.sh --columns <spec> [options]

  -c, --columns   Comma separated <formatter>:<column> pairs (required)
  -s, --size      Number of pool rows (default: 1000)
  -l, --locale    Faker locale (default: fr_FR)
  -S, --seed      Seed making the output reproducible (default: $ANONYMIZER_SEED)
  -t, --table     Pool table name (default: _anon_pool)
  -d, --dialect   mysql or postgres (default: mysql)
  -o, --output    Write to this file instead of stdout
  -h, --help      Show this help
EOF
}

columns=""
size=1000
locale="fr_FR"
seed="${ANONYMIZER_SEED:-}"
table="_anon_pool"
dialect="mysql"
output=""

while [ $# -gt 0 ]; do
    case "$1" in
        -c|--columns) columns="$2"; shift 2 ;;
        -s|--size) size="$2"; shift 2 ;;
        -l|--locale) locale="$2"; shift 2 ;;
        -S|--seed) seed="$2"; shift 2 ;;
        -t|--table) table="$2"; shift 2 ;;
        -d|--dialect) dialect="$2"; shift 2 ;;
        -o|--output) output="$2"; shift 2 ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
    esac
done

if [ -z "$columns" ]; then
    echo "Missing required --columns" >&2
    usage >&2
    exit 1
fi

if [ -z "$seed" ]; then
    echo "Missing seed: pass --seed or set ANONYMIZER_SEED" >&2
    exit 1
fi

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
project_dir="$(cd "$script_dir/.." && pwd)"

if ! command -v php >/dev/null 2>&1; then
    echo "php binary not found in PATH" >&2
    exit 1
fi

if [ ! -f "$project_dir/vendor/autoload.php" ]; then
    echo "vendor/autoload.php not found: run make install first" >&2
    exit 1
fi

if ! php -r "require '$project_dir/vendor/autoload.php'; exit(class_exists(Faker\Factory::class) ? 0 : 1);"; then
    echo "fakerphp/faker is not installed: run make install (it is a require-dev dependency)" >&2
    exit 1
fi

args=(
    "$project_dir/scripts/generate-pool.php"
    "--columns=$columns"
    "--size=$size"
    "--locale=$locale"
    "--seed=$seed"
    "--table=$table"
    "--dialect=$dialect"
)

if [ -n "$output" ]; then
    mkdir -p "$(dirname "$output")"
    php "${args[@]}" > "$output"
else
    php "${args[@]}"
fi
