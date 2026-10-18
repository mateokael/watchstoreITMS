<?php
// Reads and validates the watch form (add + edit use the same rules). PHP validation only, no HTML5 attributes.
$d = [
    'brand'            => trim(strip_tags($_POST['brand'] ?? '')),
    'model'            => trim(strip_tags($_POST['model'] ?? '')),
    'description'      => trim(strip_tags($_POST['description'] ?? '')),
    'movement'         => $_POST['movement'] ?? '',
    'category'         => $_POST['category'] ?? '',
    'case_size_mm'     => trim($_POST['case_size_mm'] ?? ''),
    'strap_type'       => trim(strip_tags($_POST['strap_type'] ?? '')),
    'water_resistance' => trim(strip_tags($_POST['water_resistance'] ?? '')),
    'cost_price'       => trim($_POST['cost_price'] ?? ''),
    'sell_price'       => trim($_POST['sell_price'] ?? ''),
    'quantity'         => trim($_POST['quantity'] ?? ''),
    'low_stock_level'  => trim($_POST['low_stock_level'] ?? '3'),
];
$err = [];
if ($d['brand'] === '')                                           $err['brand'] = 'Brand is required.';
elseif (mb_strlen($d['brand']) > 60)                              $err['brand'] = 'Brand is too long (60 characters max).';
if ($d['model'] === '')                                           $err['model'] = 'Model is required.';
elseif (mb_strlen($d['model']) > 100)                             $err['model'] = 'Model is too long (100 characters max).';
if (mb_strlen($d['description']) > 2000)                          $err['description'] = 'Description is too long (2000 characters max).';
if (!in_array($d['movement'], MOVEMENTS, true))                   $err['movement'] = 'Choose a movement type.';
if (!in_array($d['category'], CATEGORIES, true))                  $err['category'] = 'Choose who the watch is for.';
if ($d['case_size_mm'] !== '' && filter_var($d['case_size_mm'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 10, 'max_range' => 70]]) === false) {
    $err['case_size_mm'] = 'Case size must be a whole number between 10 and 70 mm.';
}
if (mb_strlen($d['strap_type']) > 40)                             $err['strap_type'] = 'Strap type is too long (40 characters max).';
if (mb_strlen($d['water_resistance']) > 20)                       $err['water_resistance'] = 'Water resistance is too long (20 characters max).';
if ($d['cost_price'] === '')                                      $err['cost_price'] = 'Cost price is required.';
elseif (!is_numeric($d['cost_price']) || (float)$d['cost_price'] < 0) $err['cost_price'] = 'Cost price must be a number, 0 or more.';
if ($d['sell_price'] === '')                                      $err['sell_price'] = 'Sell price is required.';
elseif (!is_numeric($d['sell_price']) || (float)$d['sell_price'] <= 0) $err['sell_price'] = 'Sell price must be a number greater than 0.';
elseif (is_numeric($d['cost_price']) && (float)$d['sell_price'] < (float)$d['cost_price']) $err['sell_price'] = 'Sell price should not be lower than the cost price.';
if ($d['quantity'] === '')                                        $err['quantity'] = 'Stock is required.';
elseif (filter_var($d['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false)        $err['quantity'] = 'Stock must be a whole number, 0 or more.';
if (filter_var($d['low_stock_level'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) $err['low_stock_level'] = 'Low-stock level must be a whole number, 0 or more.';
