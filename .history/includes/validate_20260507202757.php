<?php
function validateProduct(array $d): array {
    $e=[];
    if(empty(trim($d['name']??'')))            $e[]='Product name required.';
    if(empty($d['sku']))                        $e[]='SKU required.';
    if(empty($d['category']))                   $e[]='Category required.';
    if(empty($d['price'])||(float)$d['price']<=0) $e[]='Price must be > 0.';
    if(isset($d['quantity'])&&(int)$d['quantity']<0) $e[]='Quantity cannot be negative.';
    if(isset($d['min_qty'])&&(int)$d['min_qty']<1)   $e[]='Min stock must be >= 1.';
    return $e;
}
function validateStock(array $d): array {
    $e=[];
    if(empty($d['product_id'])) $e[]='Select a product.';
    if(empty($d['type']))       $e[]='Select transaction type.';
    if(empty($d['quantity'])||(int)$d['quantity']<=0) $e[]='Quantity must be > 0.';
    return $e;
}
function validateOrder(array $d): array {
    $e=[];
    if(empty(trim($d['customer']??''))) $e[]='Customer name required.';
    return $e;
}
