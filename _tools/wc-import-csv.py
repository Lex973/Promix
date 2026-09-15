"""Прайс katalog-dlya-sayta.csv → woo-import.csv для импортёра WooCommerce.

Прайс берётся из docs/data/ (папка вне git), результат ложится рядом.
Другой прайс — первым аргументом: python _tools/wc-import-csv.py путь/к/прайсу.csv
"""
import csv
import io
import sys
from pathlib import Path

PRICE_LIST = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(__file__).resolve().parent.parent / 'docs' / 'data' / 'katalog-dlya-sayta.csv'

rows = list(csv.DictReader(io.open(PRICE_LIST, encoding='utf-8-sig'), delimiter=';'))
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

print(len(rows), 'rows')
