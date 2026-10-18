<?php
// The add / edit watch form. Shared by item/create.php and item/edit.php so the two forms can't drift apart.
$gallery = $gallery ?? [];
?>
<form class="panel" method="post" action="<?= htmlspecialchars((string)$formAction) ?>" enctype="multipart/form-data" novalidate>
            <?php if ($item): ?><input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="brand">Brand</label><input class="form-control<?= (isset($_SESSION['errors']['brand']) ? ' is-invalid' : '') ?>" id="brand" name="brand" value="<?= htmlspecialchars((string)(($_SESSION['old']['brand'] ?? ($item['brand'] ?? '')))) ?>"><?= (isset($_SESSION['errors']['brand']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['brand']) . '</div>' : '') ?></div>
        <div class="col-md-8"><label class="form-label" for="model">Model</label><input class="form-control<?= (isset($_SESSION['errors']['model']) ? ' is-invalid' : '') ?>" id="model" name="model" value="<?= htmlspecialchars((string)(($_SESSION['old']['model'] ?? ($item['model'] ?? '')))) ?>"><?= (isset($_SESSION['errors']['model']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['model']) . '</div>' : '') ?></div>
        <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control<?= (isset($_SESSION['errors']['description']) ? ' is-invalid' : '') ?>" id="description" name="description" rows="3"><?= htmlspecialchars((string)(($_SESSION['old']['description'] ?? ($item['description'] ?? '')))) ?></textarea><?= (isset($_SESSION['errors']['description']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['description']) . '</div>' : '') ?></div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="movement">Movement</label>
          <select class="form-select<?= (isset($_SESSION['errors']['movement']) ? ' is-invalid' : '') ?>" id="movement" name="movement">
            <?php foreach (MOVEMENTS as $m): ?><option value="<?= $m ?>" <?= ($_SESSION['old']['movement'] ?? ($item['movement'] ?? 'quartz')) === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option><?php endforeach; ?>
          </select>
          <?= (isset($_SESSION['errors']['movement']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['movement']) . '</div>' : '') ?>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="category">For</label>
          <select class="form-select<?= (isset($_SESSION['errors']['category']) ? ' is-invalid' : '') ?>" id="category" name="category">
            <?php foreach (CATEGORIES as $c): ?><option value="<?= $c ?>" <?= ($_SESSION['old']['category'] ?? ($item['category'] ?? 'unisex')) === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option><?php endforeach; ?>
          </select>
          <?= (isset($_SESSION['errors']['category']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['category']) . '</div>' : '') ?>
        </div>
        <div class="col-6 col-md-2"><label class="form-label" for="case_size_mm">Case (mm)</label><input type="text" inputmode="decimal" class="form-control<?= (isset($_SESSION['errors']['case_size_mm']) ? ' is-invalid' : '') ?>" id="case_size_mm" name="case_size_mm" value="<?= htmlspecialchars((string)(($_SESSION['old']['case_size_mm'] ?? ($item['case_size_mm'] ?? '')))) ?>"><?= (isset($_SESSION['errors']['case_size_mm']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['case_size_mm']) . '</div>' : '') ?></div>
        <div class="col-6 col-md-4"><label class="form-label" for="strap_type">Strap</label><input class="form-control<?= (isset($_SESSION['errors']['strap_type']) ? ' is-invalid' : '') ?>" id="strap_type" name="strap_type" placeholder="Leather, steel, resin..." value="<?= htmlspecialchars((string)(($_SESSION['old']['strap_type'] ?? ($item['strap_type'] ?? '')))) ?>"><?= (isset($_SESSION['errors']['strap_type']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['strap_type']) . '</div>' : '') ?></div>
        <div class="col-6 col-md-3"><label class="form-label" for="water_resistance">Water resistance</label><input class="form-control<?= (isset($_SESSION['errors']['water_resistance']) ? ' is-invalid' : '') ?>" id="water_resistance" name="water_resistance" placeholder="50 m" value="<?= htmlspecialchars((string)(($_SESSION['old']['water_resistance'] ?? ($item['water_resistance'] ?? '')))) ?>"><?= (isset($_SESSION['errors']['water_resistance']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['water_resistance']) . '</div>' : '') ?></div>
        <div class="col-6 col-md-3"><label class="form-label" for="cost_price">Cost price</label><input type="text" inputmode="decimal" class="form-control<?= (isset($_SESSION['errors']['cost_price']) ? ' is-invalid' : '') ?>" id="cost_price" name="cost_price" value="<?= htmlspecialchars((string)(($_SESSION['old']['cost_price'] ?? ($item['cost_price'] ?? '')))) ?>"><?= (isset($_SESSION['errors']['cost_price']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['cost_price']) . '</div>' : '') ?></div>
        <div class="col-6 col-md-3"><label class="form-label" for="sell_price">Sell price</label><input type="text" inputmode="decimal" class="form-control<?= (isset($_SESSION['errors']['sell_price']) ? ' is-invalid' : '') ?>" id="sell_price" name="sell_price" value="<?= htmlspecialchars((string)(($_SESSION['old']['sell_price'] ?? ($item['sell_price'] ?? '')))) ?>"><?= (isset($_SESSION['errors']['sell_price']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['sell_price']) . '</div>' : '') ?></div>
        <div class="col-3 col-md-1"><label class="form-label" for="quantity">Stock</label><input type="text" inputmode="decimal" class="form-control<?= (isset($_SESSION['errors']['quantity']) ? ' is-invalid' : '') ?>" id="quantity" name="quantity" value="<?= htmlspecialchars((string)(($_SESSION['old']['quantity'] ?? ($item['quantity'] ?? '0')))) ?>"><?= (isset($_SESSION['errors']['quantity']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['quantity']) . '</div>' : '') ?></div>
        <div class="col-3 col-md-2"><label class="form-label" for="low_stock_level">Low at</label><input type="text" inputmode="decimal" class="form-control<?= (isset($_SESSION['errors']['low_stock_level']) ? ' is-invalid' : '') ?>" id="low_stock_level" name="low_stock_level" value="<?= htmlspecialchars((string)(($_SESSION['old']['low_stock_level'] ?? ($item['low_stock_level'] ?? '3')))) ?>"><?= (isset($_SESSION['errors']['low_stock_level']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['low_stock_level']) . '</div>' : '') ?></div>

        <div class="col-md-6">
          <label class="form-label" for="img_path">Main photo</label>
          <input type="file" class="form-control" id="img_path" name="img_path" accept="image/jpeg,image/png,image/webp">
          <div class="form-text">JPG, PNG or WEBP, up to 3 MB.<?= $item ? ' Leave empty to keep the current photo.' : '' ?></div>
          <?php if ($item && $item['img_path']): ?><img class="admin-thumb mt-2" style="width:96px;height:96px" src="<?= htmlspecialchars((string)((is_file(ROOT_DIR . '/' . $item['img_path']) ? BASE_URL . '/' . $item['img_path'] : BASE_URL . '/assets/placeholder.svg'))) ?>" alt="Current main photo"><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="gallery">More photos (optional)</label>
          <input type="file" class="form-control" id="gallery" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple>
          <?php if ($gallery): ?>
            <div class="d-flex flex-wrap gap-3 mt-2">
              <?php foreach ($gallery as $g): ?>
                <label class="small text-center">
                  <img class="admin-thumb d-block mb-1" style="width:72px;height:72px" src="<?= htmlspecialchars((string)((is_file(ROOT_DIR . '/' . $g['img_path']) ? BASE_URL . '/' . $g['img_path'] : BASE_URL . '/assets/placeholder.svg'))) ?>" alt="">
                  <input type="checkbox" name="remove_images[]" value="<?= (int)$g['image_id'] ?>"> Remove
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars((string)$submitLabel) ?></button>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/item/index.php">Cancel</a>
      </div>
    </form>
<?php unset($_SESSION['old'], $_SESSION['errors']); ?>
