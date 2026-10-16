"""Export published WooCommerce product names and SKUs through read-only SSH."""

import base64
import json
import subprocess
from datetime import datetime
from pathlib import Path

from openpyxl import Workbook, load_workbook
from openpyxl.styles import Alignment, Font, PatternFill
from openpyxl.worksheet.table import Table, TableStyleInfo


def main():
    php = """
$products = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC']);
$rows = [];
foreach ($products as $product) {
    $rows[] = ['id' => $product->ID, 'name' => $product->post_title, 'sku' => (string) get_post_meta($product->ID, '_sku', true)];
}
echo wp_json_encode(['site' => home_url(), 'count' => count($rows), 'products' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    payload = base64.b64encode(php.encode()).decode()
    remote = f"cd ~/public_html && wp eval 'eval(base64_decode(\"{payload}\"));'"
    result = subprocess.run(
        [
            'ssh', '-i', 'C:/Users/lexfv/.ssh/promix_timeweb',
            '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=20',
            'ce098228@vh460.timeweb.ru', remote,
        ],
        capture_output=True, check=True, encoding='utf-8', timeout=120,
    )
    data = json.loads(result.stdout)
    assert data['site'].rstrip('/') in ('http://promix-kazan.ru', 'https://promix-kazan.ru'), data['site']
    products = sorted(data['products'], key=lambda p: (p['name'].casefold(), p['id']))
    assert products and len(products) == data['count']
    assert len({p['id'] for p in products}) == len(products)

    root = Path(__file__).resolve().parents[1]
    destination = root / 'docs' / 'exports'
    destination.mkdir(parents=True, exist_ok=True)
    stamp = datetime.now().strftime('%Y-%m-%d')
    output = destination / f'PROMIX-товары-{stamp}.xlsx'
    workbook = Workbook()
    sheet = workbook.active
    sheet.title = 'Товары'
    sheet.append(['Название', 'Артикул'])
    for product in products:
        sheet.append([product['name'], product['sku']])
        for cell in sheet[sheet.max_row]:
            cell.data_type = 's'
            cell.number_format = '@'
            cell.alignment = Alignment(vertical='top', wrap_text=True)
        sheet.row_dimensions[sheet.max_row].height = 32
    for cell in sheet[1]:
        cell.font = Font(bold=True, color='FFFFFF')
        cell.fill = PatternFill('solid', fgColor='263746')
    sheet.row_dimensions[1].height = 24
    sheet.column_dimensions['A'].width = 100
    sheet.column_dimensions['B'].width = 24
    sheet.freeze_panes = 'A2'
    table = Table(displayName='PromixProducts', ref=f'A1:B{sheet.max_row}')
    table.tableStyleInfo = TableStyleInfo(name='TableStyleMedium2', showRowStripes=True)
    sheet.add_table(table)
    sheet.sheet_properties.pageSetUpPr.fitToPage = True
    sheet.page_setup.orientation = 'landscape'
    sheet.page_setup.paperSize = sheet.PAPERSIZE_A4
    sheet.page_setup.fitToWidth = 1
    sheet.page_setup.fitToHeight = 0
    sheet.print_title_rows = '1:1'
    workbook.save(output)

    check = load_workbook(output)
    actual = list(check.active.values)
    assert actual[0] == ('Название', 'Артикул')
    assert actual[1:] == [(p['name'], p['sku'] or None) for p in products]
    assert check.active.max_column == 2
    check.close()
    print(json.dumps({'file': str(output), 'products': len(products), 'missing_skus': sum(not p['sku'] for p in products)}, ensure_ascii=False))


if __name__ == '__main__':
    main()
