<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\Lookbook;
use App\Models\LookbookItem;
use App\Models\Voucher;
use App\Models\Banner; // 👈 Jangan lupa import model Banner
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. USERS (Admin & Member)
        User::create([
            'name' => 'Admin Hurts',
            'email' => 'admin@hurtsspace.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'avatar' => 'https://ui-avatars.com/api/?name=Admin+Hurts&background=000&color=fff'
        ]);

        User::create([
            'name' => 'Zidan Buyer',
            'email' => 'member@hurtsspace.com',
            'password' => Hash::make('password'),
            'role' => 'member',
            'points' => 100,
            'phone' => '081234567890',
            'address_detail' => 'Jl. Sudirman No. 1, Jakarta Pusat',
            'avatar' => 'https://ui-avatars.com/api/?name=Zidan+Buyer&background=random'
        ]);

        // 2. BANNERS (Split Banner Home) 🔥
        Banner::create([
            'title' => 'CORE NEW SEASON',
            'image_left' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=1000&auto=format&fit=crop',
            'image_right' => 'https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?q=80&w=1000&auto=format&fit=crop',
            'link_url' => '/shop?sort=new',
            'is_active' => true,
        ]);

        // 3. VOUCHERS 🎟️
        Voucher::create([
            'code' => 'HURTSLAUNCH',
            'discount_type' => 'fixed',
            'discount_amount' => 50000,
            'stock' => 100,
            'start_date' => now(),
            'end_date' => now()->addMonths(1),
        ]);

        Voucher::create([
            'code' => 'DISKON10',
            'discount_type' => 'percent',
            'discount_amount' => 10, // 10%
            'stock' => 100,
            'start_date' => now(),
            'end_date' => now()->addMonths(1),
        ]);

        // 4. CATEGORIES
        $catTshirt = Category::create(['name' => 'T-Shirts', 'slug' => 't-shirts']);
        $catOuter = Category::create(['name' => 'Outerwear', 'slug' => 'outerwear']);
        $catPants = Category::create(['name' => 'Pants', 'slug' => 'pants']);

        // 5. PRODUCTS

        // Produk 1: Kaos
        $prod1 = Product::create([
            'category_id' => $catTshirt->id,
            'name' => 'Hurts Heavyweight Tee - Black',
            'slug' => 'hurts-heavyweight-tee-black',
            'description' => "Material: 100% Cotton 24s (Heavyweight).\nFit: Boxy Oversized.\nDetails: High density plastisol print at chest.",
            'price' => 189000,
            'weight' => 250,
            'is_new_arrival' => true,
        ]);

        foreach (['S', 'M', 'L', 'XL'] as $size) {
            ProductVariant::create(['product_id' => $prod1->id, 'size' => $size, 'stock' => 20]);
        }

        ProductImage::create([
            'product_id' => $prod1->id,
            'image_url' => 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=800&q=80',
            'is_primary' => true
        ]);

        // Produk 2: Hoodie
        $prod2 = Product::create([
            'category_id' => $catOuter->id,
            'name' => 'Hurts Signature Hoodie - Grey',
            'slug' => 'hurts-signature-hoodie-grey',
            'description' => "Material: Fleece 330gsm.\nFit: Relaxed Fit.\nDetails: Embroidered logo.",
            'price' => 450000,
            'weight' => 600,
            'is_collab' => true,
        ]);

        foreach (['M', 'L', 'XL'] as $size) {
            ProductVariant::create(['product_id' => $prod2->id, 'size' => $size, 'stock' => 15]);
        }

        ProductImage::create([
            'product_id' => $prod2->id,
            'image_url' => 'https://images.unsplash.com/photo-1556906781-9a412961d289?auto=format&fit=crop&w=800&q=80',
            'is_primary' => true
        ]);

        // 6. LOOKBOOK
        $lookbook = Lookbook::create([
            'title' => 'Urban Explorer Vol.1',
            'image_url' => 'https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?auto=format&fit=crop&w=800&q=80'
        ]);

        // Tag Hoodie di foto lookbook
        LookbookItem::create([
            'lookbook_id' => $lookbook->id,
            'product_id' => $prod2->id,
            'x_position' => 50,
            'y_position' => 40,
        ]);
    }
}
