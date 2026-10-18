<?php
// Admin: customers and employees (list, search, add, edit, deactivate, delete).
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

$q    = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? 'all';
$where  = ['u.user_id <> ?'];
$params = [(int)$_SESSION['user_id']];
if (in_array($role, ['user', 'employee', 'admin'], true)) {
    $where[] = 'u.role = ?';
    $params[] = $role;
}
if ($q !== '') {
    $where[] = '(u.username LIKE ? OR u.email LIKE ? OR c.fname LIKE ? OR c.lname LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}

$rows = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT u.user_id, u.username, u.email, u.role, u.status, u.created_at, c.fname, c.lname, c.phone,
                       COUNT(o.orderinfo_id) AS orders
                FROM users u
                LEFT JOIN customer c ON c.user_id = u.user_id
                LEFT JOIN orderinfo o ON o.customer_id = c.customer_id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY u.user_id, u.username, u.email, u.role, u.status, u.created_at, c.fname, c.lname, c.phone
                ORDER BY u.role, u.username', $params), MYSQLI_ASSOC);

$pageTitle = 'Customers & employees';
include('../includes/header.php');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="mb-0">Customers &amp; employees <small class="text-secondary fs-5">(<?= count($rows) ?>)</small></h1>
  <a href="<?= BASE_URL ?>/admin/user_edit.php" class="btn btn-brass"><i class="fa-solid fa-plus"></i> Add customer or employee</a>
</div>
<p class="text-secondary">Change an active account's role from the Type column. Employees can confirm orders and restock watches only; admins can do everything.</p>

<form method="get" class="d-flex flex-wrap gap-2 mb-3">
  <input type="search" class="form-control" style="max-width:280px" name="q" value="<?= htmlspecialchars((string)$q) ?>" placeholder="Search name, username or email" aria-label="Search">
  <select class="form-select" style="max-width:180px" name="role" aria-label="Filter by type">
    <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>Everyone</option>
    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Customers</option>
    <option value="employee" <?= $role === 'employee' ? 'selected' : '' ?>>Employees</option>
    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admins</option>
  </select>
  <button class="btn btn-primary" type="submit">Search</button>
</form>

<div class="panel p-0 table-responsive">
  <table class="table table-hover mb-0 align-middle">
    <thead><tr><th scope="col">Username</th><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Type</th><th scope="col" class="num text-end">Orders</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-secondary py-4">Nobody matches.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): $uid = (int)$r['user_id']; ?>
      <tr class="<?= $r['status'] === 'active' ? '' : 'row-inactive' ?>">
        <td><?= htmlspecialchars((string)$r['username']) ?></td>
        <td><?= htmlspecialchars((string)(trim(($r['fname'] ?? '') . ' ' . ($r['lname'] ?? '')) ?: '-')) ?></td>
        <td><?= htmlspecialchars((string)$r['email']) ?></td>
        <td>
          <?php if ($r['status'] === 'active'): ?>
            <form method="post" action="<?= BASE_URL ?>/admin/user_role.php" class="d-flex gap-1">
<input type="hidden" name="user_id" value="<?= $uid ?>">
              <select class="form-select form-select-sm" name="role" aria-label="Role for <?= htmlspecialchars((string)$r['username']) ?>" style="min-width:120px">
                <?php foreach (['user' => 'Customer', 'employee' => 'Employee', 'admin' => 'Admin'] as $v => $label): ?>
                  <option value="<?= $v ?>" <?= $r['role'] === $v ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-outline-primary" type="submit">Set</button>
            </form>
          <?php else: ?>
            <span class="badge bg-light text-dark border"><?= htmlspecialchars((string)(ucfirst($r['role'] === 'user' ? 'customer' : $r['role']))) ?></span>
          <?php endif; ?>
        </td>
        <td class="num text-end"><?= (int)$r['orders'] ?></td>
        <td><?= $r['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
        <td class="text-end">
          <div class="d-flex gap-1 justify-content-end">
            <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL . '/admin/user_edit.php?id=' . $uid ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
            <form method="post" action="<?= BASE_URL ?>/admin/user_status.php">
<input type="hidden" name="user_id" value="<?= $uid ?>">
              <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $r['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
            </form>
            <form method="post" action="<?= BASE_URL ?>/admin/user_delete.php" onsubmit="return confirm('Delete this account for good? This cannot be undone.');">
<input type="hidden" name="user_id" value="<?= $uid ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-regular fa-trash-can"></i> Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="small text-secondary mt-2">A customer who already has orders can't be deleted (the orders and reports need them). Deactivate the account instead: they can no longer log in.</p>
<?php include('../includes/footer.php'); ?>
