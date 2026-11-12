<?php
require 'config.php';
require 'auth.php';
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard', 'products', 'categories', 'brands', 'suppliers', 'sales'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$stats = [
    'products' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'stock' => (int)$pdo->query("SELECT COALESCE(SUM(stock),0) FROM products")->fetchColumn(),
    'low' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5")->fetchColumn(),
    'sales' => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(sold_at)=CURDATE()")->fetchColumn()
];
$recent = $pdo->query("SELECT s.*,p.name AS product_name FROM sales s JOIN products p ON p.id=s.product_id ORDER BY s.sold_at DESC LIMIT 5")->fetchAll();
$products = $pdo->query("SELECT p.*,c.name category,b.name brand FROM products p JOIN categories c ON c.id=p.category_id JOIN brands b ON b.id=p.brand_id ORDER BY p.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT c.*,COUNT(p.id) product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.name")->fetchAll();
$brands = $pdo->query("SELECT b.*,COUNT(p.id) product_count FROM brands b LEFT JOIN products p ON p.brand_id=b.id GROUP BY b.id ORDER BY b.name")->fetchAll();
$suppliers = $pdo->query("SELECT s.*,COUNT(p.id) product_count FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id GROUP BY s.id ORDER BY s.name")->fetchAll();
$sales = $pdo->query("SELECT s.*,p.name product_name FROM sales s JOIN products p ON p.id=s.product_id ORDER BY s.sold_at DESC")->fetchAll();
$edit = null;
if ($page === 'products' && isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}
$catOptions = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$brandOptions = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();
$supplierOptions = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>GadgetHub | Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <div class="app">
        <aside class="sidebar"><a class="brand" href="index.php"><span class="brand-icon">G</span><span>gadget<span class="brand-light">hub</span><small>MANAGEMENT SUITE</small></span></a>
            <div class="nav-label">WORKSPACE</div>
            <a class="nav <?= $page === 'dashboard' ? 'active' : '' ?>" href="?page=dashboard"><span>▦</span> Dashboard</a>
            <a class="nav <?= $page === 'products' ? 'active' : '' ?>" href="?page=products"><span>▣</span> Products</a>
            <div class="nav-label">CATALOG</div>
            <a class="nav <?= $page === 'categories' ? 'active' : '' ?>" href="?page=categories"><span>◫</span> Categories</a>
            <a class="nav <?= $page === 'brands' ? 'active' : '' ?>" href="?page=brands"><span>◇</span> Brands</a>
            <a class="nav <?= $page === 'suppliers' ? 'active' : '' ?>" href="?page=suppliers"><span>♧</span> Suppliers</a>
            <div class="nav-label">OPERATIONS</div>
            <a class="nav <?= $page === 'sales' ? 'active' : '' ?>" href="?page=sales"><span>↗</span> Sales</a>
            <div class="sidebar-bottom">
                <div class="profile">
                    <div class="avatar">A</div>
                    <div><b>Administrator</b><small>Store manager</small></div><span class="dots">•••</span>
                </div>
            </div>
        </aside>
        <main class="main">
            <header class="topbar">
                <div>
                    <div class="eyebrow">GADGETHUB / <?= strtoupper($page) ?></div>
                    <h1><?= ucfirst($page) ?></h1>
                </div>
                <div class="top-actions"><span class="today"><?= date('D, d M Y') ?></span>
                    <div class="avatar">A</div>
                </div>
            </header>
            <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <?php if ($page === 'dashboard'): ?>
                <section class="welcome">
                    <div><span class="welcome-tag">OVERVIEW</span>
                        <h2>Good day, Admin <span>✦</span></h2>
                        <p>Here’s what’s happening with your gadget store today.</p>
                    </div><a class="btn btn-white" href="?page=products#product-form">＋ Add new product</a>
                </section>
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-top"><span>Total products</span><span class="stat-icon blue">▣</span></div><strong><?= number_format($stats['products']) ?></strong><small>Products in your catalog</small>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top"><span>Items in stock</span><span class="stat-icon purple">▤</span></div><strong><?= number_format($stats['stock']) ?></strong><small>Units available</small>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top"><span>Low stock alerts</span><span class="stat-icon orange">⚑</span></div><strong><?= number_format($stats['low']) ?></strong><small>Products with 5 or fewer units</small>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top"><span>Today's sales</span><span class="stat-icon green">₱</span></div><strong><?= money($stats['sales']) ?></strong><small>Recorded sales today</small>
                    </div>
                </div>
                <div class="content-grid">
                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h3>Recent products</h3>
                                <p>Your latest catalog additions</p>
                            </div><a class="text-link" href="?page=products">View all →</a>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>PRODUCT</th>
                                        <th>BRAND</th>
                                        <th>PRICE</th>
                                        <th>STOCK</th>
                                    </tr>
                                </thead>
                                <tbody><?php foreach (array_slice($products, 0, 5) as $p): ?><tr>
                                            <td>
                                                <div class="product-cell"><?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" alt=""><?php else: ?><span class="product-placeholder">▣</span><?php endif; ?><div><b><?= e($p['name']) ?></b><small><?= e($p['sku']) ?></small></div>
                                                </div>
                                            </td>
                                            <td><?= e($p['brand']) ?></td>
                                            <td class="price"><?= money($p['price']) ?></td>
                                            <td><span class="stock <?= $p['stock'] <= 5 ? 'low' : '' ?>"><?= (int)$p['stock'] ?> units</span></td>
                                        </tr><?php endforeach; ?></tbody>
                            </table>
                        </div>
                    </section>
                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h3>Recent sales</h3>
                                <p>Latest transactions</p>
                            </div><a class="text-link" href="?page=sales">View all →</a>
                        </div><?php if (!$recent): ?><div class="empty">No sales recorded yet.</div><?php else: foreach ($recent as $s): ?><div class="sale-row">
                                    <div class="sale-icon">↗</div>
                                    <div class="sale-info"><b><?= e($s['product_name']) ?></b><small><?= e($s['customer_name'] ?: 'Walk-in customer') ?> · <?= date('M d, g:i A', strtotime($s['sold_at'])) ?></small></div><strong><?= money($s['total']) ?></strong>
                                </div><?php endforeach;
                                                                                                endif; ?>
                    </section>
                </div>
            <?php elseif ($page === 'products'): ?>
                <div class="section-intro">
                    <div>
                        <h2>Product catalog</h2>
                        <p>Manage your gadget inventory and product details.</p>
                    </div><a href="#product-form" class="btn btn-primary">＋ Add product</a>
                </div>
                <section class="panel form-panel" id="product-form">
                    <div class="panel-head">
                        <div>
                            <h3><?= $edit ? 'Edit product' : 'Add a product' ?></h3>
                            <p><?= $edit ? 'Update the selected product details.' : 'Enter the details to add an item to your catalog.' ?></p>
                        </div><?php if ($edit): ?><a class="text-link" href="?page=products">Cancel edit</a><?php endif; ?>
                    </div>
                    <form action="save.php" method="post" class="form-grid"><input type="hidden" name="entity" value="product"><input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
                        <label>Product name<input required name="name" value="<?= e($edit['name'] ?? '') ?>" placeholder="e.g. iPhone 16 Pro Max"></label>
                        <label>SKU / Product code<input required name="sku" value="<?= e($edit['sku'] ?? '') ?>" placeholder="e.g. APL-IP16-256"></label>
                        <label>Category<select name="category_id" required>
                                <option value="">Select category</option><?php foreach ($catOptions as $o): ?><option value="<?= $o['id'] ?>" <?= ($edit['category_id'] ?? '') == $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
                            </select></label>
                        <label>Brand<select name="brand_id" required>
                                <option value="">Select brand</option><?php foreach ($brandOptions as $o): ?><option value="<?= $o['id'] ?>" <?= ($edit['brand_id'] ?? '') == $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
                            </select></label>
                        <label>Supplier<select name="supplier_id">
                                <option value="">No supplier</option><?php foreach ($supplierOptions as $o): ?><option value="<?= $o['id'] ?>" <?= ($edit['supplier_id'] ?? '') == $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
                            </select></label>
                        <label>Price (₱)<input type="number" name="price" min="0" step="0.01" required value="<?= e($edit['price'] ?? '') ?>" placeholder="0.00"></label>
                        <label>Stock quantity<input type="number" name="stock" min="0" required value="<?= e($edit['stock'] ?? '0') ?>"></label>
                        <label>Image URL<input name="image_url" value="<?= e($edit['image_url'] ?? '') ?>" placeholder="https://..."></label>
                        <label class="full">Description<textarea name="description" rows="2" placeholder="Short product description..."><?= e($edit['description'] ?? '') ?></textarea></label>
                        <div class="form-actions full"><button class="btn btn-primary" type="submit"><?= $edit ? 'Save changes' : 'Save product' ?></button><a class="btn btn-muted" href="?page=products">Clear</a></div>
                    </form>
                </section>
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>All products <span class="count"><?= count($products) ?></span></h3>
                            <p>Search, edit, or remove products from your catalog.</p>
                        </div><input class="search" id="tableSearch" placeholder="⌕  Search products...">
                    </div>
                    <div class="table-wrap">
                        <table id="dataTable">
                            <thead>
                                <tr>
                                    <th>PRODUCT</th>
                                    <th>CATEGORY</th>
                                    <th>BRAND</th>
                                    <th>PRICE</th>
                                    <th>STOCK</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody><?php foreach ($products as $p): ?><tr>
                                        <td>
                                            <div class="product-cell"><?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" alt=""><?php else: ?><span class="product-placeholder">▣</span><?php endif; ?><div><b><?= e($p['name']) ?></b><small><?= e($p['sku']) ?></small></div>
                                            </div>
                                        </td>
                                        <td><?= e($p['category']) ?></td>
                                        <td><?= e($p['brand']) ?></td>
                                        <td class="price"><?= money($p['price']) ?></td>
                                        <td><span class="stock <?= $p['stock'] <= 5 ? 'low' : '' ?>"><?= (int)$p['stock'] ?></span></td>
                                        <td>
                                            <div class="actions"><a class="action edit" href="?page=products&edit=<?= $p['id'] ?>#product-form">Edit</a>
                                                <form action="delete.php" method="post" onsubmit="return confirm('Delete this product? This cannot be undone.')"><input type="hidden" name="entity" value="product"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="action delete" type="submit">Delete</button></form>
                                            </div>
                                        </td>
                                    </tr><?php endforeach;
                                        if (!$products): ?><tr>
                                        <td colspan="6" class="empty">No products yet. Add your first product above.</td>
                                    </tr><?php endif; ?></tbody>
                        </table>
                    </div>
                </section>
            <?php elseif (in_array($page, ['categories', 'brands', 'suppliers'])): $entity = $page;
                $entityKey = ['categories' => 'category', 'brands' => 'brand', 'suppliers' => 'supplier'][$page];
                $rows = $page === 'categories' ? $categories : ($page === 'brands' ? $brands : $suppliers); ?>
                <div class="section-intro">
                    <div>
                        <h2><?= ucfirst($page) ?> management</h2>
                        <p>Create and maintain your store's <?= $page ?>.</p>
                    </div>
                </div>
                <section class="panel form-panel">
                    <div class="panel-head">
                        <div>
                            <h3>Add <?= rtrim($page, 's') ?></h3>
                            <p>Fill in the details below.</p>
                        </div>
                    </div>
                    <form action="save.php" method="post" class="form-grid"><input type="hidden" name="entity" value="<?= $entityKey ?>">
                        <?php if ($page === 'categories'): ?><label>Name<input required name="name" placeholder="e.g. Wearables"></label><label>Description<input name="description" placeholder="Category description"></label>
                        <?php elseif ($page === 'brands'): ?><label>Brand name<input required name="name" placeholder="e.g. Huawei"></label><label>Country<input name="country" placeholder="Country of origin"></label>
                        <?php else: ?><label>Supplier name<input required name="name" placeholder="Company name"></label><label>Contact person<input name="contact_person" placeholder="Contact person"></label><label>Email<input type="email" name="email" placeholder="email@example.com"></label><label>Phone<input name="phone" placeholder="Phone number"></label><label class="full">Address<input name="address" placeholder="Business address"></label><?php endif; ?>
                        <div class="form-actions full"><button class="btn btn-primary">Save <?= rtrim($page, 's') ?></button></div>
                    </form>
                </section>
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>All <?= $page ?> <span class="count"><?= count($rows) ?></span></h3>
                        </div><input class="search" id="tableSearch" placeholder="⌕  Search...">
                    </div>
                    <div class="table-wrap">
                        <table id="dataTable">
                            <thead>
                                <tr><?php if ($page === 'categories'): ?><th>NAME</th>
                                        <th>DESCRIPTION</th>
                                        <th>PRODUCTS</th><?php elseif ($page === 'brands'): ?><th>BRAND</th>
                                        <th>COUNTRY</th>
                                        <th>PRODUCTS</th><?php else: ?><th>SUPPLIER</th>
                                        <th>CONTACT</th>
                                        <th>EMAIL</th>
                                        <th>PHONE</th>
                                        <th>PRODUCTS</th><?php endif; ?><th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody><?php foreach ($rows as $r): ?><tr><?php if ($page === 'categories'): ?><td><b><?= e($r['name']) ?></b></td>
                                            <td><?= e($r['description']) ?></td>
                                            <td><?= $r['product_count'] ?></td><?php elseif ($page === 'brands'): ?><td><b><?= e($r['name']) ?></b></td>
                                            <td><?= e($r['country']) ?></td>
                                            <td><?= $r['product_count'] ?></td><?php else: ?><td><b><?= e($r['name']) ?></b></td>
                                            <td><?= e($r['contact_person']) ?></td>
                                            <td><?= e($r['email']) ?></td>
                                            <td><?= e($r['phone']) ?></td>
                                            <td><?= $r['product_count'] ?></td><?php endif; ?><td>
                                            <form action="delete.php" method="post" onsubmit="return confirm('Delete this record?')"><input type="hidden" name="entity" value="<?= $entityKey ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="action delete">Delete</button></form>
                                        </td>
                                    </tr><?php endforeach;
                                        if (!$rows): ?><tr>
                                        <td colspan="6" class="empty">No records yet.</td>
                                    </tr><?php endif; ?></tbody>
                        </table>
                    </div>
                </section>
            <?php elseif ($page === 'sales'): ?>
                <div class="section-intro">
                    <div>
                        <h2>Sales transactions</h2>
                        <p>Record sales and keep track of store transactions.</p>
                    </div>
                </div>
                <section class="panel form-panel">
                    <div class="panel-head">
                        <div>
                            <h3>Record a sale</h3>
                            <p>Stock is automatically deducted after a successful sale.</p>
                        </div>
                    </div>
                    <form action="save.php" method="post" class="form-grid"><input type="hidden" name="entity" value="sale"><label class="full">Product<select name="product_id" required>
                                <option value="">Choose product</option><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>" <?= $p['stock'] < 1 ? 'disabled' : '' ?>><?= e($p['name']) ?> — <?= money($p['price']) ?> (stock: <?= $p['stock'] ?>)</option><?php endforeach; ?>
                            </select></label><label>Quantity<input type="number" name="quantity" min="1" value="1" required></label><label>Customer name<input name="customer_name" placeholder="Walk-in customer"></label>
                        <div class="form-actions full"><button class="btn btn-primary">＋ Record sale</button></div>
                    </form>
                </section>
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>Transaction history</h3>
                            <p><?= count($sales) ?> recorded transactions</p>
                        </div><input class="search" id="tableSearch" placeholder="⌕  Search sales...">
                    </div>
                    <div class="table-wrap">
                        <table id="dataTable">
                            <thead>
                                <tr>
                                    <th>DATE</th>
                                    <th>PRODUCT</th>
                                    <th>CUSTOMER</th>
                                    <th>QTY</th>
                                    <th>UNIT PRICE</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody><?php foreach ($sales as $s): ?><tr>
                                        <td><?= date('M d, Y h:i A', strtotime($s['sold_at'])) ?></td>
                                        <td><b><?= e($s['product_name']) ?></b></td>
                                        <td><?= e($s['customer_name'] ?: 'Walk-in customer') ?></td>
                                        <td><?= $s['quantity'] ?></td>
                                        <td><?= money($s['unit_price']) ?></td>
                                        <td class="price"><?= money($s['total']) ?></td>
                                    </tr><?php endforeach;
                                        if (!$sales): ?><tr>
                                        <td colspan="6" class="empty">No sales recorded yet.</td>
                                    </tr><?php endif; ?></tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
            <footer>© <?= date('Y') ?> GadgetHub · Gadget Management System <span>Built for smarter inventory.</span></footer>
        </main>
    </div>
    <script src="assets/app.js"></script>
</body>

</html>