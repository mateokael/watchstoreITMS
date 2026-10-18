<?php
// Reads ?from=YYYY-MM-DD&to=YYYY-MM-DD (the date pickers). Default: first day of this month to today.
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$fromCheck = DateTime::createFromFormat('Y-m-d', (string)$from);
if (!$fromCheck || $fromCheck->format('Y-m-d') !== $from) {
    $from = date('Y-m-01');
}
$toCheck = DateTime::createFromFormat('Y-m-d', (string)$to);
if (!$toCheck || $toCheck->format('Y-m-d') !== $to) {
    $to = date('Y-m-d');
}
if ($from > $to) {
    $swap = $from;
    $from = $to;
    $to = $swap;
}
