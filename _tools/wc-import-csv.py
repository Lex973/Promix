"""Прайс katalog-dlya-sayta.csv → woo-import.csv для импортёра WooCommerce.

Прайс берётся из docs/data/ (папка вне git), результат ложится рядом.
Другой прайс — первым аргументом: python _tools/wc-import-csv.py путь/к/прайсу.csv

Артикулы из wc-variants.csv рядом с прайсом (их склеивает wc-variants.php)
сюда не попадают: импорт с «Обновлять существующие» нашёл бы по артикулу
вариант и пересохранил его простым товаром. На новом сервере, где склейки
ещё не было, нужен полный список — флаг --all, потом wc-variants.php.
"""
import csv
import io
import sys
from pathlib import Path

from excluded import keep

ARGS = [a for a in sys.argv[1:] if a != '--all']
PRICE_LIST = Path(ARGS[0]) if ARGS else Path(__file__).resolve().parent.parent / 'docs' / 'data' / 'katalog-dlya-sayta.csv'

rows = list(csv.DictReader(io.open(PRICE_LIST, encoding='utf-8-sig'), delimiter=';'))
skipped = [r for r in rows if not keep(r)]
rows = [r for r in rows if keep(r)]

VARIANTS = PRICE_LIST.with_name('wc-variants.csv')
merged = set()
if '--all' not in sys.argv and VARIANTS.exists():
    merged = {r['SKU'].strip() for r in csv.DictReader(io.open(VARIANTS, encoding='utf-8-sig'), delimiter=';')}
in_variants = [r for r in rows if r['Артикул'].strip() in merged]
rows = [r for r in rows if r['Артикул'].strip() not in merged]
# Колонка цены в выгрузках называлась и «Цена», и «Цена, ₽» — ищем по началу.
PRICE = next(k for k in rows[0] if k.startswith('Цена'))

with io.open(PRICE_LIST.with_name('woo-import.csv'), 'w', encoding='utf-8', newline='') as out:
    w = csv.writer(out)
    w.writerow(['Type', 'SKU', 'Name', 'Published', 'Visibility in catalog', 'In stock?',
                'Regular price', 'Categories', 'Position', 'Brands'])
    for r in rows:
        price = r[PRICE]
        price = price[:-2] if price.endswith('.0') else price
        # Запятая внутри названия категории — иначе импортёр разрежет её на три.
        cat = r['Категория'].replace(',', '\\,')
        # Бренд — штатная таксономия WooCommerce («Товары → Бренды»), колонка Brands.
        w.writerow(['simple', r['Артикул'], r['Наименование'], 1, 'visible', 1, price, cat, r['ID'], r['Бренд']])

print(len(rows), 'rows' + (f', skipped by excluded.py: {len(skipped)}' if skipped else '')
      + (f', skipped as variants (wc-variants.csv, --all to keep): {len(in_variants)}' if in_variants else ''))
