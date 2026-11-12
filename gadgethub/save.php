<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect();
$entity = $_POST['entity'] ?? '';
try {
 if ($entity === 'product') {
  $id=(int)($_POST['id']??0);
  $data=[trim($_POST['sku']??''),trim($_POST['name']??''),(int)($_POST['category_id']??0),(int)($_POST['brand_id']??0),($_POST['supplier_id']??'')!==''?(int)$_POST['supplier_id']:null,trim($_POST['description']??''),(float)($_POST['price']??0),(int)($_POST['stock']??0),trim($_POST['image_url']??'')];
  if (!$data[0] || !$data[1] || $data[2]<1 || $data[3]<1 || $data[6]<0 || $data[7]<0) throw new Exception('Please complete the required product fields with valid values.');
  if ($id) { $data[]=$id; $st=$pdo->prepare("UPDATE products SET sku=?,name=?,category_id=?,brand_id=?,supplier_id=?,description=?,price=?,stock=?,image_url=? WHERE id=?"); $st->execute($data); flash('Product updated successfully.'); }
  else { $st=$pdo->prepare("INSERT INTO products (sku,name,category_id,brand_id,supplier_id,description,price,stock,image_url) VALUES (?,?,?,?,?,?,?,?,?)"); $st->execute($data); flash('Product added successfully.'); }
  redirect('index.php?page=products');
 }
 if ($entity==='category') { $st=$pdo->prepare("INSERT INTO categories(name,description) VALUES(?,?)"); $st->execute([trim($_POST['name']??''),trim($_POST['description']??'')]); flash('Category added.'); redirect('index.php?page=categories'); }
 if ($entity==='brand') { $st=$pdo->prepare("INSERT INTO brands(name,country) VALUES(?,?)"); $st->execute([trim($_POST['name']??''),trim($_POST['country']??'')]); flash('Brand added.'); redirect('index.php?page=brands'); }
 if ($entity==='supplier') { $st=$pdo->prepare("INSERT INTO suppliers(name,contact_person,email,phone,address) VALUES(?,?,?,?,?)"); $st->execute([trim($_POST['name']??''),trim($_POST['contact_person']??''),trim($_POST['email']??''),trim($_POST['phone']??''),trim($_POST['address']??'')]); flash('Supplier added.'); redirect('index.php?page=suppliers'); }
 if ($entity==='sale') {
  $pid=(int)($_POST['product_id']??0); $qty=(int)($_POST['quantity']??0);
  if($pid<1||$qty<1) throw new Exception('Select a product and enter a valid quantity.');
  $pdo->beginTransaction();
  $st=$pdo->prepare("SELECT price,stock FROM products WHERE id=? FOR UPDATE"); $st->execute([$pid]); $p=$st->fetch();
  if(!$p || $p['stock']<$qty) throw new Exception('Not enough stock available for this sale.');
  $total=$p['price']*$qty;
  $st=$pdo->prepare("INSERT INTO sales(product_id,quantity,unit_price,total,customer_name) VALUES(?,?,?,?,?)");
  $st->execute([$pid,$qty,$p['price'],$total,trim($_POST['customer_name']??'')]);
  $st=$pdo->prepare("UPDATE products SET stock=stock-? WHERE id=?"); $st->execute([$qty,$pid]);
  $pdo->commit(); flash('Sale recorded and inventory updated.'); redirect('index.php?page=sales');
 }
 throw new Exception('Unknown action.');
} catch(Throwable $e) {
 if($pdo->inTransaction()) $pdo->rollBack();
 flash($e instanceof PDOException ? 'Unable to save. Check for duplicate values or linked records.' : $e->getMessage(),'error');
 redirect('index.php?page='.(['product'=>'products','category'=>'categories','brand'=>'brands','supplier'=>'suppliers','sale'=>'sales'][$entity]??'dashboard'));
}
