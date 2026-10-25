<?php
// Shows the messages kept in the session, then clears them.
$alertTypes = ['message' => 'danger', 'warning' => 'warning', 'info' => 'info', 'success' => 'success'];
foreach ($alertTypes as $alertKey => $alertClass) {
    if (isset($_SESSION[$alertKey])) {
        echo '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars((string)$_SESSION[$alertKey], ENT_QUOTES, 'UTF-8')
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION[$alertKey]);
    }
}
