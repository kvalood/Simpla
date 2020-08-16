<?php

/**
 * Simpla CMS
 *
 * @copyright	2017 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once('api/Simpla.php');
$simpla = new Simpla();

header("Content-type: text/xml; charset=UTF-8");
print(pack('CCC', 0xef, 0xbb, 0xbf));


print "<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL;
print "<!DOCTYPE yml_catalog SYSTEM 'shops.dtd'>" . PHP_EOL;
print "<yml_catalog date='".date('Y-m-d H:i')."'>" . PHP_EOL;
print "<shop>" . PHP_EOL;
print "<name>".$simpla->settings->site_name."</name>" . PHP_EOL;
print "<company>".$simpla->settings->company_name."</company>" . PHP_EOL;
print "<url>".$simpla->config->root_url."</url>" . PHP_EOL;

// Валюты
$currencies = $simpla->money->get_currencies(array('enabled'=>1));
$main_currency = reset($currencies);
print "<currencies>" . PHP_EOL;
foreach ($currencies as $c) {
    if ($c->enabled) {
        print "<currency id='".$c->code."' rate='".$c->rate_to/$c->rate_from*$main_currency->rate_from/$main_currency->rate_to."'/>" . PHP_EOL;
    }
}
print "</currencies>" . PHP_EOL;


// Категории
$simpla->db->query("SELECT id, parent_id, name FROM __categories WHERE visible=1");
$categories = $simpla->db->results();
print "<categories>" .PHP_EOL;
foreach ($categories as $c) {
    print "<category id='$c->id'";
    if ($c->parent_id>0) {
        print " parentId='$c->parent_id'";
    }
    print ">".htmlspecialchars($c->name)."</category>" .PHP_EOL;
}
print "</categories>" .PHP_EOL;

// Товары
$simpla->db->query("SET SQL_BIG_SELECTS=1");
// Товары
$simpla->db->query("SELECT v.price, 
                            v.compare_price, 
                            v.id as variant_id, 
                            p.name as product_name, 
                            v.name as variant_name, 
                            v.position as variant_position, 
                            p.id as product_id, 
                            p.url, 
                            p.annotation, 
                            pc.category_id
					FROM __variants v 
					LEFT JOIN __products p ON v.product_id=p.id
					LEFT JOIN __products_categories pc ON p.id = pc.product_id AND pc.position=(SELECT MIN(position) FROM __products_categories WHERE product_id=p.id LIMIT 1)
					WHERE p.visible AND (v.stock >0 OR v.stock is NULL) 
					GROUP BY v.id 
					ORDER BY p.id, v.position");

$products = [];
foreach ($simpla->db->results() as $p) {
    $products[$p->product_id] = $p;
}

$products_ids = array_keys($products);

$query = $simpla->db->placehold("SELECT i.id,i.filename,i.position, i.product_id
                                FROM __images as i
                                WHERE i.product_id in (?@) AND i.position=(SELECT min(position) FROM __images WHERE i.product_id = product_id LIMIT 1)
                                ORDER BY i.position
                                ", $products_ids);
$simpla->db->query($query);
$images = $simpla->db->results();
foreach ($images as $image) {
    $products[$image->product_id]->image = $image->filename;
}

print "<offers>" . PHP_EOL;

$currency_code = reset($currencies)->code;

$prev_product_id = null;
foreach ($products as $p) {
    $variant_url = '';
    if ($prev_product_id === $p->product_id) {
        $variant_url = '?variant='.$p->variant_id;
    }
    $prev_product_id = $p->product_id;

    $price = round($simpla->money->convert($p->price, $main_currency->id, false), 2);
    $oldprice = round($simpla->money->convert($p->compare_price, $main_currency->id, false),2);

    print PHP_EOL;
    print "<offer id='$p->variant_id' available='true'>" . PHP_EOL;
    print "<url>".$simpla->config->root_url.'/products/'.$p->url.$variant_url."</url>" . PHP_EOL;
    print "<price>$price</price>" . PHP_EOL;
    if ($p->compare_price > 0 && $p->compare_price > $p->price) {
        print "<oldprice>$oldprice</oldprice>" . PHP_EOL;
    }
    print "<currencyId>".$currency_code."</currencyId>" . PHP_EOL;
    print "<categoryId>".$p->category_id."</categoryId>" . PHP_EOL;
    if ($p->image) {
        print "<picture>".$simpla->image->resize_image($p->image, 200, 200)."</picture>" . PHP_EOL;
    }

    print "<name>".htmlspecialchars($p->product_name).($p->variant_name?' '.htmlspecialchars($p->variant_name):'')."</name>" . PHP_EOL;
    print "<description>".htmlspecialchars(strip_tags($p->annotation))."</description>" . PHP_EOL;
    print "</offer>" . PHP_EOL;
}
print "</offers>" . PHP_EOL;
print "</shop>" . PHP_EOL;
print "</yml_catalog>";

