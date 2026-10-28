<?php
// Profile page, Facebook style: picture on the left, credentials on the right.
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');
$uid = (int)$_SESSION['user_id'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $form = $_POST['form'] ?? '';

    if ($form === 'avatar') {
        $oldPic = (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT profile_pic FROM users WHERE user_id = ?', [$uid])) ?? [null])[0];
        if (isset($_POST['remove'])) {
            mysqli_execute_query($conn, 'UPDATE users SET profile_pic = NULL WHERE user_id = ?', [$uid]);
            if ($oldPic && is_file(ROOT_DIR . '/' . $oldPic)) { unlink(ROOT_DIR . '/' . $oldPic); }
            $_SESSION['success'] = 'Profile picture removed.';
        } else {
            $upFile = $_FILES['avatar'] ?? ['error' => UPLOAD_ERR_NO_FILE];
            $upFolder = 'avatars';
            $upPrefix = 'user_';
            include('../includes/upload_one.php');
            if ($upError) {
                $_SESSION['message'] = $upError;
            } elseif (!$upPath) {
                $_SESSION['warning'] = 'Choose a picture first.';
            } else {
                mysqli_execute_query($conn, 'UPDATE users SET profile_pic = ? WHERE user_id = ?', [$upPath, $uid]);
                if ($oldPic && is_file(ROOT_DIR . '/' . $oldPic)) {
                    unlink(ROOT_DIR . '/' . $oldPic);
                }
                $_SESSION['success'] = 'Profile picture updated.';
            }
        }
        include('../includes/refresh_session.php');
        header('Location: ' . BASE_URL . '/user/profile.php');
        exit();
    }

    if ($form === 'account') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $current  = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT email, password FROM users WHERE user_id = ?', [$uid]));
        $emailChanged = strcasecmp($email, $current['email']) !== 0;
        $error = null;
        if (!preg_match('/^[A-Za-z0-9._-]{3,20}$/', $username)) {
            $error = 'Username must be 3 to 20 characters: letters, numbers, dots, dashes or underscores.';
        } elseif (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM users WHERE username = ? AND user_id <> ?', [$username, $uid]))) {
            $error = 'That username is already taken. Please pick another one.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $error = 'Enter a valid email address.';
        } elseif ($emailChanged && mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM users WHERE email = ? AND user_id <> ?', [$email, $uid]))) {
            $error = 'That email is already used by another account.';
        } elseif ($emailChanged && !password_verify((string)($_POST['current_password'] ?? ''), $current['password'])) {
            $error = 'To change your email, type your current password.';
        }
        if ($error) {
            $_SESSION['old'] = ['username' => $username, 'email' => $email];
            $_SESSION['message'] = $error;
        } else {
            mysqli_execute_query($conn, 'UPDATE users SET username = ?, email = ? WHERE user_id = ?', [$username, $email, $uid]);
            include('../includes/refresh_session.php');
            $_SESSION['success'] = 'Account details saved.';
        }
        header('Location: ' . BASE_URL . '/user/profile.php');
        exit();
    }

    if ($form === 'password') {
        $row = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT email, username, password FROM users WHERE user_id = ?', [$uid]));
        $new = (string)($_POST['new_password'] ?? '');
        if (!$row || !password_verify((string)($_POST['current_password'] ?? ''), $row['password'])) {
            $_SESSION['message'] = 'Your current password is not correct.';
        } elseif (strlen($new) < 8) {
            $_SESSION['message'] = 'The new password needs at least 8 characters.';
        } elseif ($new !== (string)($_POST['confirm_password'] ?? '')) {
            $_SESSION['message'] = 'The new passwords do not match.';
        } else {
            mysqli_execute_query($conn, 'UPDATE users SET password = ? WHERE user_id = ?', [password_hash($new, PASSWORD_BCRYPT), $uid]);
            $accountEmailType = 'changed';
            $accountEmail = $row['email'];
            $accountName = $row['username'];
            include('../includes/account_email.php');
            $_SESSION['success'] = 'Password changed.';
        }
        header('Location: ' . BASE_URL . '/user/profile.php');
        exit();
    }

    $customer = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM customer WHERE user_id = ?', [$uid]));
    $d = [
        'title'       => trim($_POST['title'] ?? ''),
        'fname'       => trim(strip_tags($_POST['fname'] ?? '')),
        'lname'       => trim(strip_tags($_POST['lname'] ?? '')),
        'addressline' => trim(strip_tags($_POST['addressline'] ?? '')),
        'town'        => trim(strip_tags($_POST['town'] ?? '')),
        'zipcode'     => trim($_POST['zipcode'] ?? ''),
        'phone'       => trim($_POST['phone'] ?? ''),
    ];
    $error = null;
    if (!in_array($d['title'], ['', 'Mr', 'Ms', 'Mrs', 'Dr'], true)) {
        $error = 'Choose a title from the list.';
    } elseif ($d['fname'] === '' || $d['lname'] === '' || $d['addressline'] === '' || $d['town'] === '') {
        $error = 'First name, last name, address and town are required.';
    } elseif (strlen($d['fname']) > 50 || strlen($d['lname']) > 50 || strlen($d['addressline']) > 120 || strlen($d['town']) > 60) {
        $error = 'One of the fields is too long.';
    } elseif (!preg_match('/^[A-Za-z0-9 \-]{3,10}$/', $d['zipcode'])) {
        $error = 'Enter a valid zip code (3 to 10 letters or digits).';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $d['phone'])) {
        $error = 'Enter a valid phone number (7 to 20 digits).';
    }
    if ($error) {
        $_SESSION['old'] = $d;
        $_SESSION['message'] = $error;
        header('Location: ' . BASE_URL . '/user/profile.php');
        exit();
    }

    if ($customer) {
        mysqli_execute_query($conn, 'UPDATE customer SET title=?, fname=?, lname=?, addressline=?, town=?, zipcode=?, phone=? WHERE user_id=?', [$d['title'], $d['fname'], $d['lname'], $d['addressline'], $d['town'], $d['zipcode'], $d['phone'], $uid]);
    } else {
        mysqli_execute_query($conn, 'INSERT INTO customer (title, fname, lname, addressline, town, zipcode, phone, user_id) VALUES (?,?,?,?,?,?,?,?)', [$d['title'], $d['fname'], $d['lname'], $d['addressline'], $d['town'], $d['zipcode'], $d['phone'], $uid]);
    }
    unset($_SESSION['old'], $_SESSION['errors']);
    $_SESSION['success'] = 'Delivery details saved.';
    $next = $_SESSION['next'] ?? null;
    unset($_SESSION['next']);
    if ($next && str_starts_with($next, BASE_URL . '/') && !str_starts_with($next, '//')) {
        header('Location: ' . $next);
    } else {
        header('Location: ' . BASE_URL . '/user/profile.php');
    }
    exit();
}

$me       = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT username, email, role, profile_pic, created_at FROM users WHERE user_id = ?', [$uid]));
$customer = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM customer WHERE user_id = ?', [$uid]));
$orders   = (int)(mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT COUNT(*) FROM orderinfo o JOIN customer c ON c.customer_id = o.customer_id WHERE c.user_id = ?', [$uid])) ?? [null])[0];

$pageTitle = 'My profile';
include('../includes/header.php');
?>
<div class="row g-4">

  <div class="col-lg-4">
    <div class="panel profile-card p-0">
      <div class="profile-cover"></div>
      <div class="text-center px-3 pb-4">
        <div class="profile-avatar"><?php [$avatarPath, $avatarName, $avatarSize, $avatarClass] = array_pad([$me['profile_pic'], $me['username'], 140, 'avatar-xl'], 4, ''); include(ROOT_DIR . '/includes/avatar.php'); ?></div>
        <h1 class="h2 mt-2 mb-0"><?= htmlspecialchars((string)$me['username']) ?></h1>
        <p class="text-secondary mb-2"><?= htmlspecialchars((string)$me['email']) ?></p>
        <?php if ($me['role'] === 'admin'): ?><span class="badge bg-dark mb-2">Store admin</span><?php elseif ($me['role'] === 'employee'): ?><span class="badge bg-secondary mb-2">Store employee</span><?php endif; ?>
        <p class="small text-secondary mb-3">Member since <?= htmlspecialchars((string)(date('F Y', strtotime($me['created_at'])))) ?> &middot; <?= $orders ?> order<?= $orders === 1 ? '' : 's' ?></p>

        <form method="post" enctype="multipart/form-data" class="text-start">
<input type="hidden" name="form" value="avatar">
          <label class="form-label small" for="avatar">Change picture</label>
          <input type="file" class="form-control form-control-sm mb-2" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
          <div class="form-text mb-2">JPG, PNG or WEBP, up to 3 MB.</div>
          <img id="avatar-preview" class="avatar-upload-preview mb-2" alt="Selected profile photo preview" hidden>
          <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" type="submit">Upload</button>
            <?php if ($me['profile_pic']): ?>
              <button class="btn btn-outline-danger btn-sm" type="submit" name="remove" value="1" formnovalidate onclick="return confirm('Remove your profile picture?');">Remove</button>
            <?php endif; ?>
          </div>

        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <form class="panel mb-4" method="post" novalidate>
<input type="hidden" name="form" value="account">
      <h2 class="h4 mb-3">Account</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="username">Username</label>
          <input class="form-control" id="username" name="username" value="<?= htmlspecialchars((string)($_SESSION['old']['username'] ?? ($me['username']))) ?>" required minlength="3" maxlength="20" autocomplete="username">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="email">Email</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars((string)($_SESSION['old']['email'] ?? ($me['email']))) ?>" required maxlength="100" autocomplete="email">
        </div>
        <div class="col-12">
          <label class="form-label" for="ac_pw">Current password <span class="text-secondary small">(only needed if you change your email)</span></label>
          <input type="password" class="form-control" id="ac_pw" name="current_password" autocomplete="current-password">
        </div>
      </div>
      <button class="btn btn-primary mt-3" type="submit">Save account</button>
    </form>

    <form class="panel mb-4" method="post" novalidate>
<input type="hidden" name="form" value="delivery">
      <h2 class="h4 mb-3">Delivery details</h2>
      <?php if (!$customer): ?><p class="text-secondary">Add these once and checkout will use them.</p><?php endif; ?>
      <div class="row g-3">
        <div class="col-4 col-md-3">
          <label class="form-label" for="title">Title</label>
          <select class="form-select" id="title" name="title">
            <?php foreach (['', 'Mr', 'Ms', 'Mrs', 'Dr'] as $t): ?>
              <option value="<?= $t ?>" <?= htmlspecialchars((string)($_SESSION['old']['title'] ?? ($customer['title'] ?? ''))) === $t ? 'selected' : '' ?>><?= $t === '' ? 'None' : $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-8 col-md-4"><label class="form-label" for="fname">First name</label><input class="form-control" id="fname" name="fname" value="<?= htmlspecialchars((string)($_SESSION['old']['fname'] ?? ($customer['fname'] ?? ''))) ?>" required maxlength="50"></div>
        <div class="col-12 col-md-5"><label class="form-label" for="lname">Last name</label><input class="form-control" id="lname" name="lname" value="<?= htmlspecialchars((string)($_SESSION['old']['lname'] ?? ($customer['lname'] ?? ''))) ?>" required maxlength="50"></div>
        <div class="col-12"><label class="form-label" for="addressline">Address</label><input class="form-control" id="addressline" name="addressline" value="<?= htmlspecialchars((string)($_SESSION['old']['addressline'] ?? ($customer['addressline'] ?? ''))) ?>" required maxlength="120"></div>
        <div class="col-7"><label class="form-label" for="town">Town or city</label><input class="form-control" id="town" name="town" value="<?= htmlspecialchars((string)($_SESSION['old']['town'] ?? ($customer['town'] ?? ''))) ?>" required maxlength="60"></div>
        <div class="col-5"><label class="form-label" for="zipcode">Zip code</label><input class="form-control" id="zipcode" name="zipcode" value="<?= htmlspecialchars((string)($_SESSION['old']['zipcode'] ?? ($customer['zipcode'] ?? ''))) ?>" required maxlength="10"></div>
        <div class="col-12"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="<?= htmlspecialchars((string)($_SESSION['old']['phone'] ?? ($customer['phone'] ?? ''))) ?>" required maxlength="20" inputmode="tel"></div>
      </div>
      <button class="btn btn-primary mt-3" type="submit">Save delivery details</button>
    </form>

    <form class="panel" method="post" novalidate>
<input type="hidden" name="form" value="password">
      <h2 class="h4 mb-3">Change password</h2>
      <div class="row g-3">
        <div class="col-12"><label class="form-label" for="cp">Current password</label><input type="password" class="form-control" id="cp" name="current_password" required autocomplete="current-password"></div>
        <div class="col-md-6"><label class="form-label" for="np">New password</label><input type="password" class="form-control" id="np" name="new_password" required minlength="8" autocomplete="new-password"></div>
        <div class="col-md-6"><label class="form-label" for="np2">Confirm new password</label><input type="password" class="form-control" id="np2" name="confirm_password" required autocomplete="new-password"></div>
      </div>
      <button class="btn btn-outline-primary mt-3" type="submit">Change password</button>
    </form>
  </div>
</div>

<script>
(() => {
  const input = document.getElementById('avatar');
  const preview = document.getElementById('avatar-preview');
  if (!input || !preview) return;

  let previewUrl = null;
  input.addEventListener('change', () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = null;
    preview.hidden = true;
    preview.removeAttribute('src');

    const file = input.files && input.files[0];
    if (file) {
      previewUrl = URL.createObjectURL(file);
      preview.src = previewUrl;
      preview.hidden = false;
    }
  });
})();
</script>
<?php unset($_SESSION['old'], $_SESSION['errors']); include('../includes/footer.php'); ?>
