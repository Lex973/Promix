"""Прайс katalog-dlya-sayta.csv → woo-import.csv для импортёра WooCommerce."""
import csv
import io

rows = list(csv.DictReader(io.open('katalog-dlya-sayta.csv', encoding='utf-8-sig'), delimiter=';'))
PRICE = 'Цена, ₽'

with io.open('woo-import.csv', 'w', encoding='utf-8', newline='') as out:
    w = csv.writer(out)
    w.writerow(['Type', 'SKU', 'Name', 'Published', 'Visibility in catalog', 'In stock?',
                'Regular price', 'Categories', 'Position', 'Brands'])
    for r in rows:
        price = r[PRICE]
        price = price[:-2] if price.endswith('.0') else price
        # Запятая внутри названия категории — иначе импортёр разрежет её на три.
        cat = r['Категория'].replace(',', '\,')
        # Бренд — штатная таксономия WooCommerce («Товары → Бренды»), колонка Brands.
        w.writerow(['simple', r['Артикул'], r['Наименование'], 1, 'visible', 1, price, cat, r['ID'], r['Бренд']])

print(len(rows), 'rows')
