<?php

require __DIR__.'/PostPrice.php';

print_r(PostPrice::calculatePrice([
    'service_type' => 'sefareshi',
    'product_type' => 'matbou',
    'weight' => 100,
    'destination_type' => 'beyn_shahri',
    'origin_state' => 1,
    'origin_city' => 39780,
    'destination_state' => 31,
    'destination_city' => 31656,
    'receiver_postal_code' => true,
    'insurance_type' => 1,
    'post_devilery_time' => 0
]));
