<?php
// Reload the logged-in person's name / picture into the session (the navbar reads them from there)
if (isset($_SESSION['user_id'])) {
    $freshUser = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT user_id, email, role, username, profile_pic FROM users WHERE user_id = ?', [$_SESSION['user_id']]));
    if ($freshUser) {
        $_SESSION['user_id'] = (int)$freshUser['user_id'];
        $_SESSION['email'] = $freshUser['email'];
        $_SESSION['role'] = $freshUser['role'];
        $_SESSION['username'] = $freshUser['username'];
        $_SESSION['avatar'] = $freshUser['profile_pic'];
    }
}
