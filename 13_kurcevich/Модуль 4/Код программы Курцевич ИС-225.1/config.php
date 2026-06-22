<?php
define('SITE_NAME', 'Бухгалтерия');
define('SITE_SUBTITLE', 'Система управления бухгалтерией');
define('THEME_COLOR_PRIMARY', '#43A047');
define('THEME_COLOR_SECONDARY', '#1B5E20');

$TABLE_CONFIGS = [
    'customer' => [
        'title' => 'Заказчики',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'name' => 'Наименование', 'snils' => 'ИНН', 'address' => 'Адрес', 'phone' => 'Телефон'],
        'form_fields' => [
            'id' => ['type' => 'text', 'label' => 'ID', 'required' => true],
            'name' => ['type' => 'text', 'label' => 'Наименование', 'required' => true],
            'snils' => ['type' => 'text', 'label' => 'ИНН', 'required' => false],
            'address' => ['type' => 'textarea', 'label' => 'Адрес', 'required' => false],
            'phone' => ['type' => 'text', 'label' => 'Телефон', 'required' => false],
            'salesman' => ['type' => 'checkbox', 'label' => 'Продавец', 'required' => false],
            'buyer' => ['type' => 'checkbox', 'label' => 'Покупатель', 'required' => false],
        ],
        'joins' => []
    ],
    'material' => [
        'title' => 'Материалы',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'name' => 'Наименование', 'unit' => 'Ед. изм.', 'code' => 'Код'],
        'form_fields' => [
            'name' => ['type' => 'text', 'label' => 'Наименование', 'required' => true],
            'unit' => ['type' => 'text', 'label' => 'Единица измерения', 'required' => true],
            'code' => ['type' => 'text', 'label' => 'Код', 'required' => false],
        ],
        'joins' => []
    ],
    'product' => [
        'title' => 'Продукция',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'name' => 'Наименование', 'unit' => 'Ед. изм.', 'markup_percent' => 'Наценка %'],
        'form_fields' => [
            'name' => ['type' => 'text', 'label' => 'Наименование', 'required' => true],
            'unit' => ['type' => 'text', 'label' => 'Единица измерения', 'required' => true],
            'markup_percent' => ['type' => 'number', 'label' => 'Наценка (%)', 'required' => false, 'step' => '0.01', 'default' => '0'],
        ],
        'joins' => []
    ],
    'bill_of_materials' => [
        'title' => 'Спецификации',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'product_id' => 'ID Продукции', 'material_id' => 'ID Материала', 'quantity' => 'Кол-во'],
        'form_fields' => [
            'product_id' => ['type' => 'select', 'label' => 'Продукция', 'required' => true, 'options_table' => 'product', 'options_display' => 'name', 'options_value' => 'id'],
            'material_id' => ['type' => 'select', 'label' => 'Материал', 'required' => true, 'options_table' => 'material', 'options_display' => 'name', 'options_value' => 'id'],
            'quantity' => ['type' => 'number', 'label' => 'Количество', 'required' => true, 'step' => '0.0001'],
        ],
        'joins' => [
            'product_name' => ['table' => 'product', 'display_column' => 'name', 'foreign_key' => 'product_id'],
            'material_name' => ['table' => 'material', 'display_column' => 'name', 'foreign_key' => 'material_id']
        ]
    ],
    'customer_order' => [
        'title' => 'Заказы покупателей',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'number' => 'Номер', 'date' => 'Дата', 'customer_name' => 'Заказчик'],
        'form_fields' => [
            'number' => ['type' => 'text', 'label' => 'Номер заказа', 'required' => true],
            'date' => ['type' => 'date', 'label' => 'Дата', 'required' => true],
            'customer_id' => ['type' => 'select', 'label' => 'Заказчик', 'required' => true, 'options_table' => 'customer', 'options_display' => 'name', 'options_value' => 'id'],
        ],
        'joins' => [
            'customer_name' => ['table' => 'customer', 'display_column' => 'name', 'foreign_key' => 'customer_id']
        ]
    ],
    'order_item' => [
        'title' => 'Позиции заказов',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'order_number' => 'Заказ', 'product_name' => 'Продукция', 'quantity' => 'Кол-во', 'price_at_sale' => 'Цена', 'total' => 'Сумма'],
        'form_fields' => [
            'order_id' => ['type' => 'select', 'label' => 'Заказ', 'required' => true, 'options_table' => 'customer_order', 'options_display' => 'number', 'options_value' => 'id'],
            'product_id' => ['type' => 'select', 'label' => 'Продукция', 'required' => true, 'options_table' => 'product', 'options_display' => 'name', 'options_value' => 'id'],
            'quantity' => ['type' => 'number', 'label' => 'Количество', 'required' => true, 'step' => '0.001'],
            'price_at_sale' => ['type' => 'number', 'label' => 'Цена продажи', 'required' => true, 'step' => '0.01'],
        ],
        'joins' => [
            'order_number' => ['table' => 'customer_order', 'display_column' => 'number', 'foreign_key' => 'order_id'],
            'product_name' => ['table' => 'product', 'display_column' => 'name', 'foreign_key' => 'product_id']
        ]
    ],
    'production' => [
        'title' => 'Производство',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'number' => 'Номер', 'date' => 'Дата'],
        'form_fields' => [
            'number' => ['type' => 'text', 'label' => 'Номер производства', 'required' => true],
            'date' => ['type' => 'date', 'label' => 'Дата', 'required' => true],
        ],
        'joins' => []
    ],
    'product_output' => [
        'title' => 'Выпуск продукции',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'production_number' => 'Производство', 'product_name' => 'Продукция', 'quantity' => 'Кол-во', 'unit' => 'Ед. изм.', 'code' => 'Код'],
        'form_fields' => [
            'production_id' => ['type' => 'select', 'label' => 'Производство', 'required' => true, 'options_table' => 'production', 'options_display' => 'number', 'options_value' => 'id'],
            'product_id' => ['type' => 'select', 'label' => 'Продукция', 'required' => true, 'options_table' => 'product', 'options_display' => 'name', 'options_value' => 'id'],
            'quantity' => ['type' => 'number', 'label' => 'Количество', 'required' => true, 'step' => '0.001'],
            'unit' => ['type' => 'text', 'label' => 'Единица измерения', 'required' => true],
            'code' => ['type' => 'text', 'label' => 'Код продукции', 'required' => false],
        ],
        'joins' => [
            'production_number' => ['table' => 'production', 'display_column' => 'number', 'foreign_key' => 'production_id'],
            'product_name' => ['table' => 'product', 'display_column' => 'name', 'foreign_key' => 'product_id']
        ]
    ],
    'material_price' => [
        'title' => 'Цены материалов',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'material_name' => 'Материал', 'price' => 'Цена', 'valid_from' => 'Действует с'],
        'form_fields' => [
            'material_id' => ['type' => 'select', 'label' => 'Материал', 'required' => true, 'options_table' => 'material', 'options_display' => 'name', 'options_value' => 'id'],
            'price' => ['type' => 'number', 'label' => 'Цена', 'required' => true, 'step' => '0.01'],
            'valid_from' => ['type' => 'date', 'label' => 'Действует с', 'required' => true],
        ],
        'joins' => [
            'material_name' => ['table' => 'material', 'display_column' => 'name', 'foreign_key' => 'material_id']
        ]
    ],
    'calculated_product_price' => [
        'title' => 'Расчетные цены',
        'admin_only' => false,
        'columns' => ['id' => 'ID', 'product_name' => 'Продукция', 'calculated_price' => 'Цена', 'valid_from' => 'Действует с'],
        'form_fields' => [
            'product_id' => ['type' => 'select', 'label' => 'Продукция', 'required' => true, 'options_table' => 'product', 'options_display' => 'name', 'options_value' => 'id'],
            'calculated_price' => ['type' => 'number', 'label' => 'Цена', 'required' => true, 'step' => '0.01'],
            'valid_from' => ['type' => 'date', 'label' => 'Действует с', 'required' => true],
        ],
        'joins' => [
            'product_name' => ['table' => 'product', 'display_column' => 'name', 'foreign_key' => 'product_id']
        ]
    ],
];

function getTableConfig($table_name) { 
    global $TABLE_CONFIGS; 
    return $TABLE_CONFIGS[$table_name] ?? null; 
}

function getMainMenu() { 
    global $TABLE_CONFIGS; 
    $menu = []; 
    
    $order = ['customer', 'material', 'product', 'bill_of_materials', 'customer_order', 'order_item', 'production', 'product_output', 'material_price', 'calculated_product_price'];
    
    foreach ($order as $table) {
        if (isset($TABLE_CONFIGS[$table])) {
            $menu[] = ['url' => "routes.php?table={$table}&action=list", 'title' => $TABLE_CONFIGS[$table]['title']];
        }
    }
    
    return $menu; 
}

$order_statuses = ['new' => 'Новый', 'in_progress' => 'В работе', 'completed' => 'Завершён', 'cancelled' => 'Отменён'];
$user_statuses = ['active' => 'Активен', 'blocked' => 'Заблокирован'];
?>