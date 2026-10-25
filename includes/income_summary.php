<?php
// Totals for the income page. Money comes from DELIVERED orders only.
$incLines = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT COUNT(*) AS orders,
                            COALESCE(SUM(units), 0) AS units,
                            COALESCE(SUM(sales), 0) AS sales,
                            COALESCE(SUM(cogs), 0) AS cogs,
                            COALESCE(SUM(shipping), 0) AS shipping
                     FROM (
                         SELECT orderinfo_id, SUM(quantity) AS units,
                                SUM(unit_price * quantity) AS sales,
                                SUM(unit_cost * quantity) AS cogs,
                                MAX(shipping) AS shipping
                         FROM v_order_details
                         WHERE status = 'delivered' AND DATE(date_delivered) BETWEEN ? AND ?
                         GROUP BY orderinfo_id
                     ) AS delivered_orders", [$incFrom, $incTo]));
$incExpenseRow = mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT SUM(amount) FROM expense WHERE expense_date BETWEEN ? AND ?', [$incFrom, $incTo])) ?? [0];

$incShipping = (float)($incLines['shipping'] ?? 0);
$incExpenses = (float)($incExpenseRow[0] ?? 0);
$incSales = (float)($incLines['sales'] ?? 0);
$incCogs = (float)($incLines['cogs'] ?? 0);
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
