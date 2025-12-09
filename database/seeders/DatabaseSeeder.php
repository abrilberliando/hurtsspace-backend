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
use App\Models\Banner;
use App\Models\HeroSection; // 👈 MODEL BARU: Asumsi lo punya model ini
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Pake Faker ID biar datanya 'Lokal Pride' tapi tetep aesthetic
        $faker = Faker::create('id_ID');

        // ==========================================
        // 1. USERS (Total 10: 1 Admin, 1 Member Fixed, 8 Random)
        // ==========================================

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

        for ($i = 0; $i < 8; $i++) {
            User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'role' => 'member',
                'points' => rand(0, 500),
                'phone' => $faker->phoneNumber,
                'address_detail' => $faker->address,
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($faker->name) . '&background=random'
            ]);
        }

        // ==========================================
        // 2. BANNERS (AESTHETIC MODEL PHOTOS)
        // ==========================================

        $aestheticImages = [
            'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1529139574466-a302391d9bd5?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1503341455253-b2e72333dbdb?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1617127365659-c47fa864d8bc?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1520975661595-6453be3f7070?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1506619215786-1017528184f8?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1496747611176-843222e1e57c?w=1920&q=80', // Ubah size ke 1920
            'https://images.unsplash.com/photo-1504194921103-f8b80cadd5e4?w=1920&q=80', // Ubah size ke 1920
        ];

        Banner::create([
            'title' => 'SEASON 01: GENESIS',
            'image_left' => $aestheticImages[0],
            'image_right' => $aestheticImages[1],
            'link_url' => '/shop?sort=new',
            'is_active' => true,
        ]);

        for ($i = 1; $i < 10; $i++) {
            $imgLeft = $aestheticImages[rand(0, 9)];
            $imgRight = $aestheticImages[rand(0, 9)];

            Banner::create([
                'title' => 'ARCHIVE COLLECTION VOL.' . $i,
                'image_left' => $imgLeft,
                'image_right' => $imgRight,
                'link_url' => '/shop?collection=vol-' . $i,
                'is_active' => $i < 5,
            ]);
        }

        // ==========================================
        // 3. VOUCHERS
        // ==========================================

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
            'discount_amount' => 10,
            'stock' => 100,
            'start_date' => now(),
            'end_date' => now()->addMonths(1),
        ]);

        for ($i = 0; $i < 8; $i++) {
            Voucher::create([
                'code' => 'PROMO' . strtoupper($faker->bothify('??##')),
                'discount_type' => $faker->randomElement(['fixed', 'percent']),
                'discount_amount' => rand(1, 5) * 10000,
                'stock' => rand(10, 50),
                'start_date' => now(),
                'end_date' => now()->addWeeks(rand(1, 4)),
            ]);
        }

        // ==========================================
        // 4. CATEGORIES
        // ==========================================

        $categories = [
            'T-Shirts', 'Hoodies', 'Jackets', 'Cargo Pants',
            'Denim', 'Shorts', 'Vests', 'Knitwear', 'Headwear', 'Socks'
        ];

        $catIds = [];

        foreach ($categories as $catName) {
            $cat = Category::create([
                'name' => $catName,
                'slug' => Str::slug($catName)
            ]);
            $catIds[] = $cat->id;
        }

        // ==========================================
        // 5. PRODUCTS (AUTO GENERATE 100 ITEMS)
        // ==========================================
        // Kita bikin "Bank Kata" biar namanya variatif dan random tapi tetep logis

        $adjectives = ['Heavyweight', 'Essential', 'Distressed', 'Vintage', 'Tactical', 'Boxy', 'Oversized', 'Signature', 'Acid Wash', 'Ripstop', 'Utility', 'Core'];
        $productTypes = ['T-Shirt', 'Hoodie', 'Cargo Pants', 'Denim Jacket', 'Varsity Jacket', 'Sweatpants', 'Knit Sweater', 'Puffer Vest', 'Shorts', 'Work Jacket'];
        $colors = ['Jet Black', 'Ash Grey', 'Olive Drab', 'Navy', 'Charcoal', 'Off-White', 'Earth Brown', 'Washed Indigo', 'Sand', 'Matte Black'];

        for ($i = 0; $i < 100; $i++) {
            $randomCatId = $catIds[array_rand($catIds)];

            // Mix and Match Nama
            $adj = $adjectives[array_rand($adjectives)];
            $type = $productTypes[array_rand($productTypes)];
            $color = $colors[array_rand($colors)];

            $name = "{$adj} {$type} - {$color}";

            // Biar slug gak nabrak kalau ada nama sama persis, tambahin random string dikit di slug
            $slug = Str::slug($name) . '-' . Str::random(5);

            $material = $faker->randomElement(['Cotton Combat 20s', 'Heavyweight Fleece 375gsm', 'Japanese Denim 14oz', 'Nylon Crinkle', 'French Terry']);
            $fit = $faker->randomElement(['Boxy Fit', 'Oversized', 'Relaxed Fit', 'Cropped', 'Regular Fit']);

            // Harga random tapi masuk akal (antara 150rb - 850rb)
            $price = rand(15, 85) * 10000;

            $product = Product::create([
                'category_id' => $randomCatId,
                'name' => $name,
                'slug' => $slug,
                'description' => "Engineered for durability and style.\n\nMaterial: {$material}.\nFit: {$fit}.\n\nCare Instructions: Machine wash cold, hang dry. Do not bleach.",
                'price' => $price,
                'weight' => rand(300, 900),
                'is_new_arrival' => $i < 10, // 10 produk pertama jadi New Arrival
                'is_collab' => ($i % 20 == 0), // Tiap kelipatan 20 jadi produk collab
            ]);

            // Variants (Sizes)
            foreach (['S', 'M', 'L', 'XL'] as $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $size,
                    'stock' => rand(0, 50) // Ada yg 0 biar keliatan sold out dikit
                ]);
            }

            // 5 Foto Per Produk
            for ($k = 0; $k < 5; $k++) {
                $isPrimary = ($k === 0);

                // FIX: Tambahkan sort_order (k+1)
                $sortOrder = $k + 1;

                // Seed unik biar gambarnya gak kembar semua se-website
                $seed = "hurts" . $product->id . $k . Str::random(3);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => "https://picsum.photos/seed/{$seed}/800/800",
                    'is_primary' => $isPrimary,
                    'sort_order' => $sortOrder // PENTING: Assign urutan 1 sampai 5
                ]);
            }
        }

        // ==========================================
        // 6. LOOKBOOK
        // ==========================================

        $allProductIds = Product::pluck('id')->toArray();

        for ($i = 1; $i <= 8; $i++) {
            $lookbookImg = $aestheticImages[rand(0, 9)];

            $lookbook = Lookbook::create([
                'title' => 'Editorial Campaign Vol.' . $i,
                'image_url' => $lookbookImg
            ]);

            // Random tag 1-3 produk di setiap lookbook
            $totalTags = rand(1, 3);
            for($t=0; $t < $totalTags; $t++) {
                $randomProdId = $allProductIds[array_rand($allProductIds)];

                LookbookItem::create([
                    'lookbook_id' => $lookbook->id,
                    'product_id' => $randomProdId,
                    'x_position' => rand(20, 80),
                    'y_position' => rand(20, 80),
                ]);
            }
        }

        // ==========================================
        // 7. HERO SECTION (BARU)
        // ==========================================

        // Ambil 5 gambar besar yang udah di-define di atas buat Hero Slider
        $heroImages = array_slice($aestheticImages, 0, 5);

        // Data Hero Section cuma 1 row
        HeroSection::create([
            'title' => 'CORE COLLECTION V.03',
            'subtitle' => 'PREMIUM STREETWEAR',
            'description' => 'Introducing the third iteration of our Core Collection. Minimalist design, maximized comfort, engineered for the street.',
            'button_text' => 'SHOP NEW DROPS',
            'button_link' => '/shop?new=true',
            // Gunakan JSON untuk menyimpan array gambar (penting untuk Eloquent/Migration)
            'background_images' => json_encode($heroImages),
        ]);

    }
}
