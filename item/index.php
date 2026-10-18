<?php
session_start();
include('../includes/config.php');
include('../includes/guard_staff.php');

$q    = trim($_GET['q'] ?? '');
$show = $_GET['show'] ?? 'active';          // active | archived | all
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(i.brand LIKE ? OR i.model LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($show === 'active')      { $where[] = 'i.is_active = 1'; }
elseif ($show === 'archived') { $where[] = 'i.is_active = 0'; }

$items = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT i.*, s.quantity, s.low_stock_level FROM item i JOIN stock s ON s.item_id = i.item_id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY i.item_id DESC', $params), MYSQLI_ASSOC);

$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
$pageTitle = $isAdmin ? 'Manage watches' : 'Restock';
$archivedCount = (int)(mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT COUNT(*) FROM item WHERE is_active = 0')) ?? [null])[0];
$containerClass = 'container-fluid px-4';
include('../includes/header.php');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="mb-0"><?= $isAdmin ? 'Watches' : 'Restock' ?> <small class="text-secondary fs-5">(<?= count($items) ?>)</small></h1>
  <?php if ($isAdmin && $archivedCount && $show !== 'archived'): ?><a class="small ms-auto me-2" href="<?= BASE_URL . '/item/index.php?show=archived' ?>"><i class="fa-solid fa-box-archive"></i> <?= $archivedCount ?> archived</a><?php endif; ?>
  <?php if ($isAdmin): ?><a href="<?= BASE_URL ?>/item/create.php" class="btn btn-brass"><i class="fa-solid fa-plus"></i> Add watch</a><?php endif; ?>
</div>

<form method="get" class="d-flex flex-wrap gap-2 mb-3">
  <input type="search" class="form-control" style="max-width:280px" name="q" value="<?= htmlspecialchars((string)$q) ?>" placeholder="Search brand or model" aria-label="Search watches">
  <select class="form-select" style="max-width:180px" name="show" aria-label="Which watches to show">
    <option value="active" <?= $show === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="archived" <?= $show === 'archived' ? 'selected' : '' ?>>Archived</option>
    <option value="all" <?= $show === 'all' ? 'selected' : '' ?>>All</option>
  </select>
  <button class="btn btn-primary" type="submit">Search</button>
</form>

<div class="panel p-0 table-responsive">
  <table class="table table-hover mb-0">
    <thead><tr><th scope="col"></th><th scope="col">Watch</th><th scope="col">Movement</th><?php if ($isAdmin): ?><th scope="col" class="num text-end">Cost</th><?php endif; ?><th scope="col" class="num text-end">Sell</th><th scope="col">Stock</th><th scope="col">Status</th><?php if ($isAdmin): ?><th scope="col" class="text-end">Actions</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$items): ?><tr><td colspan="<?= $isAdmin ? 8 : 6 ?>" class="text-center text-secondary py-4">No watches found.</td></tr><?php endif; ?>
    <?php foreach ($items as $it): $id = (int)$it['item_id']; $low = (int)$it['quantity'] <= (int)$it['low_stock_level']; ?>
      <tr class="<?= $it['is_active'] ? '' : 'row-inactive' ?>">
        <td><img class="admin-thumb" src="<?= htmlspecialchars((string)((is_file(ROOT_DIR . '/' . $it['img_path']) ? BASE_URL . '/' . $it['img_path'] : BASE_URL . '/assets/placeholder.svg'))) ?>" alt=""></td>
        <td><a href="<?= BASE_URL . '/product.php?id=' . $id ?>"><?= htmlspecialchars((string)(($it['brand'] . ' ' . $it['model']))) ?></a><div class="small text-secondary">#<?= $id ?></div></td>
        <td><?= ucfirst($it['movement']) ?></td>
        <?php if ($isAdmin): ?><td class="num text-end"><?= (CURRENCY . number_format($it['cost_price'], 2)) ?></td><?php endif; ?>
        <td class="num text-end"><?= (CURRENCY . number_format($it['sell_price'], 2)) ?></td>
        <td>
          <form method="post" action="<?= BASE_URL ?>/item/stock_update.php" class="d-flex gap-1 align-items-center">
<input type="hidden" name="item_id" value="<?= $id ?>">
            <input type="number" min="0" class="form-control form-control-sm" style="width:80px" name="quantity" value="<?= (int)$it['quantity'] ?>" aria-label="Stock for <?= htmlspecialchars((string)(($it['brand'] . ' ' . $it['model']))) ?>">
            <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
            <?php if ($low && $it['is_active']): ?><span class="badge bg-warning text-dark">Low</span><?php endif; ?>
          </form>
        </td>
        <td><?= $it['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Archived</span>' ?></td>
        <?php if ($isAdmin): ?><td class="text-end">
          <div class="d-flex gap-1 justify-content-end">
            <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL . '/item/edit.php?id=' . $id ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
            <form method="post" action="<?= BASE_URL ?>/item/archive.php" <?= $it['is_active'] ? 'onsubmit="return confirm(\'Archive this watch? It leaves the shop but past orders keep it, and you can restore it any time.\');"' : '' ?>>
<input type="hidden" name="item_id" value="<?= $id ?>">
              <input type="hidden" name="action" value="<?= $it['is_active'] ? 'archive' : 'restore' ?>">
              <?php if ($it['is_active']): ?>
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-box-archive"></i> Archive</button>
              <?php else: ?>
                <button class="btn btn-sm btn-outline-success" type="submit"><i class="fa-solid fa-box-open"></i> Restore</button>
              <?php endif; ?>
            </form>
          </div>
        </td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include('../includes/footer.php'); ?>
