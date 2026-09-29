#!/usr/bin/env bash
# Комплект для переноса на сервер → docs/deploy/ (папка вне git).
#
# Запуск из корня темы: bash _tools/build-deploy.sh
#
# Кладёт рядом всё, что перечислено в DEPLOY.md («Что взять с собой»):
# архив темы из текущего коммита, CSV для импорта, собранные заново из прайса,
# служебные скрипты и текст политики. Архив собирается из коммита, а не из
# рабочей папки, поэтому незакоммиченные правки темы — повод остановиться.
set -euo pipefail

cd "$(dirname "$0")/.."

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "Есть незакоммиченные изменения — архив разошёлся бы с рабочей папкой:" >&2
    git status --short --untracked-files=no >&2
    exit 1
fi

out=docs/deploy
rm -rf "$out"
mkdir -p "$out"

# Переводы строк — как в репозитории (LF), без оглядки на core.autocrlf
# машины: иначе на Windows в архив уходят CRLF и суммы не сходятся.
git -c core.autocrlf=false archive --format=zip -o "$out/promix.zip" HEAD

# CSV — заново из прайса, чтобы на сервер ушли тексты текущего генератора.
python _tools/wc-import-csv.py
python _tools/wc-descriptions.py
python _tools/wc-categories.py >/dev/null
python _tools/wc-decor.py
cp docs/data/woo-import.csv docs/data/woo-descriptions.csv docs/data/wc-categories.csv docs/data/decor/decor.csv "$out/"

# Образцы цвета AlterItaly — рядом с decor.csv, в папке img/ (DEPLOY.md, шаг 6.4).
mkdir -p "$out/img"
cp docs/data/decor/img/*.jpg docs/data/decor/img/*.png "$out/img/"

# В скриптах ключ остаётся плейсхолдером: с ним они не запускаются,
# настоящий ключ вписывается перед загрузкой на сервер (DEPLOY.md, шаги 5 и 6).
cp _tools/wc-setup.php _tools/wc-recategorize.php _tools/wc-decor.php _tools/wc-import.php _content/privacy-policy.md DEPLOY.md "$out/"

rows=$(( $(wc -l < "$out/woo-import.csv") - 1 ))

{
    echo "Комплект PROMIX для переноса"
    echo "Коммит: $(git log -1 --format='%h %s')"
    echo "Ветка:  $(git rev-parse --abbrev-ref HEAD)"
    echo "Собран: $(date '+%Y-%m-%d %H:%M')"
    echo "Товаров в woo-import.csv: $rows"
    echo
    echo "Порядок — DEPLOY.md. Перед загрузкой вписать:"
    echo "  wc-setup.php, wc-recategorize.php, wc-decor.php — свой ключ вместо СЕКРЕТ (12+ латинских букв и цифр);"
    echo "  privacy-policy.md — [ОГРН] и [ДАТА];"
    echo "  wp-config.php на сервере — константы из шага 7."
    echo
    echo "SHA-256:"
    (cd "$out" && sha256sum promix.zip woo-import.csv woo-descriptions.csv wc-categories.csv decor.csv wc-setup.php wc-recategorize.php wc-decor.php wc-import.php)
} > "$out/СОСТАВ.txt"

echo "Готово: $out"
ls -la "$out"
