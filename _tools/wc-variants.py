"""Ответ заказчика по склейке (xlsx) → wc-variants.csv для wc-variants.php.

Файл — тот, что ушёл заказчику: лист «Кандидаты». Колонки группы (№,
«Товар после склейки», «Что выбирают», «Склеиваем?») — объединённые ячейки,
значение у первой строки группы. «Склеиваем?»: Да или Частично — склеиваем
без строк, где в колонке «Убрать» стоит «убрать»; Нет или пусто — не
склеиваем. «Товар после склейки» и «Вариант» берутся как есть, заказчик
мог их поправить.

Запуск: python _tools/wc-variants.py ответ.xlsx [--out путь.csv]
По умолчанию результат ложится в docs/data/wc-variants.csv — его же
читает wc-import-csv.py, чтобы не грузить склеенные артикулы простыми товарами.
"""
import argparse
import csv
import io
import sys
from pathlib import Path

from openpyxl import load_workbook

DECISIONS = {'да', 'нет', 'частично', ''}

parser = argparse.ArgumentParser()
parser.add_argument('xlsx', type=Path)
parser.add_argument('--out', type=Path, default=Path(__file__).resolve().parent.parent / 'docs' / 'data' / 'wc-variants.csv')
args = parser.parse_args()

sheet = load_workbook(args.xlsx, read_only=True, data_only=True)['Кандидаты']
rows = sheet.iter_rows(values_only=True)
head = [str(c or '').strip() for c in next(rows)]
col = {name: head.index(name) for name in ('№', 'Товар после склейки', 'Что выбирают', 'Вариант', 'Артикул', 'Склеиваем?', 'Убрать')}


def text(value) -> str:
    # Артикул в xlsx записан текстом, но заказчик мог перебить ячейку числом.
    if isinstance(value, float) and value.is_integer():
        value = int(value)
    return str(value or '').strip()


groups = {}
errors = []
current = None

for n, row in enumerate(rows, start=2):
    if not any(row):
        continue
    # Новая группа — где заполнен №: в объединённой ячейке он только в первой строке.
    if text(row[col['№']]):
        current = {
            'no': text(row[col['№']]),
            'name': text(row[col['Товар после склейки']]),
            'attr': text(row[col['Что выбирают']]),
            'decision': text(row[col['Склеиваем?']]).lower(),
            'rows': [],
        }
        if current['no'] in groups:
            errors.append(f'строка {n}: группа {current["no"]} встречается второй раз')
        groups[current['no']] = current
        if current['decision'] not in DECISIONS:
            errors.append(f'строка {n}: непонятный ответ «{text(row[col["Склеиваем?"]])}» — нужно Да, Нет или Частично')
    if current is None:
        errors.append(f'строка {n}: нет номера группы')
        continue
    drop = text(row[col['Убрать']]).lower()
    if drop not in ('', 'убрать'):
        errors.append(f'строка {n}: в «Убрать» непонятное «{text(row[col["Убрать"]])}» — нужно «убрать» или пусто')
    current['rows'].append({'option': text(row[col['Вариант']]), 'sku': text(row[col['Артикул']]), 'drop': drop == 'убрать', 'line': n})

if errors:
    print('\n'.join(errors))
    sys.exit(1)

out = []
seen = {}
for g in groups.values():
    if g['decision'] not in ('да', 'частично'):
        continue
    keep = [r for r in g['rows'] if not r['drop']]
    if g['decision'] == 'да' and len(keep) < len(g['rows']):
        print(f'группа {g["no"]} «{g["name"]}»: «Да», но есть «убрать» — убираю ' + ', '.join(r['sku'] for r in g['rows'] if r['drop']))
    if len(keep) < 2:
        print(f'группа {g["no"]} «{g["name"]}»: после «убрать» остался {len(keep)} вариант — не склеиваем')
        continue
    for r in keep:
        problem = (not g['name'] and 'нет названия товара') or (not g['attr'] and 'не указано, что выбирают') \
            or (not r['option'] and 'пустой вариант') or (not r['sku'] and 'пустой артикул') \
            or (r['sku'] in seen and f'артикул {r["sku"]} уже в группе {seen[r["sku"]]}')
        if problem:
            errors.append(f'строка {r["line"]}, группа {g["no"]}: {problem}')
        seen[r['sku']] = g['no']
        out.append([g['no'], g['name'], g['attr'], r['option'], r['sku']])
    options = [r['option'] for r in keep]
    if len(set(options)) != len(options):
        errors.append(f'группа {g["no"]} «{g["name"]}»: одинаковые подписи вариантов — {", ".join(options)}')

if errors:
    print('\n'.join(errors))
    sys.exit(1)

args.out.parent.mkdir(parents=True, exist_ok=True)
with io.open(args.out, 'w', encoding='utf-8-sig', newline='') as f:
    w = csv.writer(f, delimiter=';')
    w.writerow(['Group', 'Name', 'Attribute', 'Option', 'SKU'])
    w.writerows(out)

print(f'{len({r[0] for r in out})} групп, {len(out)} вариантов → {args.out}')
