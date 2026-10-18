<?php
// Totals for the income page. Money comes from DELIVERED orders only.
$incLines = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT COUNT(DISTINCT o.orderinfo_id) AS orders,
                            COALESCE(SUM(ol.quantity), 0) AS units,
                            COALESCE(SUM(ol.unit_price * ol.quantity), 0) AS sales,
                            COALESCE(SUM(ol.unit_cost * ol.quantity), 0) AS cogs
                     FROM orderinfo o INNER JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
                     WHERE o.status = 'delivered' AND " . SALE_DATE_SQL . " BETWEEN ? AND ?", [$incFrom, $incTo]));
$incShipping = (float)(mysqli_fetch_row(mysqli_execute_query($conn, "SELECT COALESCE(SUM(o.shipping), 0) FROM orderinfo o
                               WHERE o.status = 'delivered' AND " . SALE_DATE_SQL . " BETWEEN ? AND ?", [$incFrom, $incTo])) ?? [0])[0];
$incExpenses = (float)(mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT COALESCE(SUM(amount), 0) FROM expense WHERE expense_date BETWEEN ? AND ?', [$incFrom, $incTo])) ?? [0])[0];
$incSales = (float)$incLines['sales'];
$incCogs = (float)$incLines['cogs'];
$incomeSummary = [
    'orders'       => (int)$incLines['orders'],
    'units'        => (int)$incLines['units'],
    'sales'        => $incSales,
    'shipping'     => $incShipping,
    'income'       => $incSales + $incShipping,
    'cogs'         => $incCogs,
    'gross_profit' => $incSales - $incCogs,
    'expenses'     => $incExpenses,
    'net'          => $incSales + $incShipping - $incCogs - $incExpenses,
];
