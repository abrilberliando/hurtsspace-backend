<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\Lookbook;
use App\Models\LookbookItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Bikin Akun Admin & Member
        User::create([
            'name' => 'Admin Hurts',
            'email' => 'admin@hurtsspace.com',
            'password' => Hash::make('password'), // Passwordnya 'password'
            'role' => 'admin',
            'avatar' => 'https://ui-avatars.com/api/?name=Admin+Hurts&background=000&color=fff'
        ]);

        User::create([
            'name' => 'Zidan Buyer',
            'email' => 'member@hurtsspace.com',
            'password' => Hash::make('password'),
            'role' => 'member',
            'points' => 50, // Bonus point awal
            'avatar' => 'https://ui-avatars.com/api/?name=Zidan+Buyer&background=random'
        ]);

        // 2. Bikin Kategori
        $catTshirt = Category::create([
            'name' => 'T-Shirts',
            'slug' => 't-shirts',
            'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=500&q=60'
        ]);

        $catOuter = Category::create([
            'name' => 'Outerwear',
            'slug' => 'outerwear',
            'image' => 'https://images.unsplash.com/photo-1556906781-9a412961d289?auto=format&fit=crop&w=500&q=60'
        ]);

        // 3. Bikin Produk 1: Kaos Oversize (Best Seller)
        $prod1 = Product::create([
            'category_id' => $catTshirt->id,
            'name' => 'Hurts Basic Oversized Tee - Black',
            'slug' => 'hurts-basic-oversized-black',
            'description' => 'Material: Heavyweight Cotton 24s. Fit: Oversized Boxy Cut. Sablon: Plastisol High Density.',
            'price' => 189000,
            'weight' => 250, // 250 gram
            'is_new_arrival' => true,
        ]);

        // Varian Size Produk 1
        $sizes = ['S', 'M', 'L', 'XL'];
        foreach ($sizes as $size) {
            ProductVariant::create([
                'product_id' => $prod1->id,
                'size' => $size,
                'stock' => rand(5, 20) // Stok acak 5-20 pcs
            ]);
        }

        // Gambar Produk 1
        ProductImage::create([
            'product_id' => $prod1->id,
            'image_url' => 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=500&q=60',
            'is_primary' => true
        ]);

        // 4. Bikin Produk 2: Hoodie Collab (Limited)
        $prod2 = Product::create([
            'category_id' => $catOuter->id,
            'name' => 'Hurts x Local Heroes Hoodie',
            'slug' => 'hurts-local-heroes-hoodie',
            'description' => 'Limited Edition Collaboration. Fleece 330gsm. Glow in the dark ink.',
            'price' => 450000,
            'weight' => 600, // 600 gram
            'is_collab' => true,
        ]);

        // Varian Size Produk 2
        foreach (['M', 'L'] as $size) {
            ProductVariant::create([
                'product_id' => $prod2->id,
                'size' => $size,
                'stock' => 5 // Stok dikit biar exclusive
            ]);
        }

        ProductImage::create([
            'product_id' => $prod2->id,
            'image_url' => 'https://images.unsplash.com/photo-1556906781-9a412961d289?auto=format&fit=crop&w=500&q=60',
            'is_primary' => true
        ]);

        // 5. Bikin Lookbook Interactive
        $lookbook = Lookbook::create([
            'title' => 'Urban Midnight Vol.1',
            'image_url' => 'https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?auto=format&fit=crop&w=800&q=80'
        ]);

        // Tag Produk di Foto Lookbook (Hotspot)
        LookbookItem::create([
            'lookbook_id' => $lookbook->id,
            'product_id' => $prod2->id, // Hoodie
            'x_position' => 50, // Posisi tengah horizontal
            'y_position' => 40, // Posisi agak atas vertikal
        ]);
    }
}
