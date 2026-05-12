<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MerchandiseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchandise = [
            [
                'name' => 'Campaign T-Shirt',
                'slug' => 'campaign-tshirt',
                'description' => 'Quality cotton t-shirt with campaign logo and slogan. Available in multiple sizes.',
                'category' => 't-shirt',
                'price' => 2500,
                'currency' => 'NGN',
                'sizes' => json_encode(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
                'colors' => json_encode(['Navy Blue', 'White', 'Gold']),
                'sku' => 'TSH-001',
                'stock_quantity' => 500,
                'in_stock' => true,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Campaign Cap',
                'slug' => 'campaign-cap',
                'description' => 'Classic baseball cap with embroidered campaign logo. One size fits all.',
                'category' => 'cap',
                'price' => 1500,
                'currency' => 'NGN',
                'sizes' => json_encode(['One Size']),
                'colors' => json_encode(['Navy', 'White']),
                'sku' => 'CAP-001',
                'stock_quantity' => 300,
                'in_stock' => true,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Campaign Hoodie',
                'slug' => 'campaign-hoodie',
                'description' => 'Comfortable fleece hoodie perfect for campaign events and gatherings.',
                'category' => 'hoodie',
                'price' => 5000,
                'currency' => 'NGN',
                'sizes' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
                'colors' => json_encode(['Navy Blue', 'Black', 'Gold']),
                'sku' => 'HDI-001',
                'stock_quantity' => 200,
                'in_stock' => true,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Campaign Polo Shirt',
                'slug' => 'campaign-polo',
                'description' => 'Elegant polo shirt for formal campaign events. Premium cotton blend.',
                'category' => 'polo',
                'price' => 3500,
                'currency' => 'NGN',
                'sizes' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
                'colors' => json_encode(['Navy', 'White', 'Gold']),
                'sku' => 'PLO-001',
                'stock_quantity' => 150,
                'in_stock' => true,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Campaign Tote Bag',
                'slug' => 'campaign-tote',
                'description' => 'Durable canvas tote bag with campaign branding. Perfect for carrying materials.',
                'category' => 'tote',
                'price' => 2000,
                'currency' => 'NGN',
                'sizes' => json_encode(['One Size']),
                'colors' => json_encode(['Navy', 'White']),
                'sku' => 'TOT-001',
                'stock_quantity' => 400,
                'in_stock' => true,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'Campaign Wristband',
                'slug' => 'campaign-wristband',
                'description' => 'Silicone wristband with campaign message. Show your support!',
                'category' => 'wristband',
                'price' => 500,
                'currency' => 'NGN',
                'sizes' => json_encode(['One Size']),
                'colors' => json_encode(['Navy', 'Gold']),
                'sku' => 'WRI-001',
                'stock_quantity' => 1000,
                'in_stock' => true,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'Campaign Sticker Pack',
                'slug' => 'campaign-sticker',
                'description' => 'Pack of high-quality waterproof stickers for supporters.',
                'category' => 'sticker',
                'price' => 800,
                'currency' => 'NGN',
                'sizes' => json_encode(['One Pack']),
                'colors' => null,
                'sku' => 'STI-001',
                'stock_quantity' => 2000,
                'in_stock' => true,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 7,
            ],
            [
                'name' => 'Campaign Badge',
                'slug' => 'campaign-badge',
                'description' => 'Pin badge for campaign supporters to wear with pride.',
                'category' => 'badge',
                'price' => 400,
                'currency' => 'NGN',
                'sizes' => json_encode(['One Size']),
                'colors' => null,
                'sku' => 'BAD-001',
                'stock_quantity' => 1500,
                'in_stock' => true,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 8,
            ],
        ];

        foreach ($merchandise as $item) {
            \App\Models\Merchandise::create($item);
        }
    }
}
