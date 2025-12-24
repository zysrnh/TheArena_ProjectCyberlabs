<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AboutContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        
        DB::table('about_contents')->insert([
            [
                'section_key' => 'hero',
                'title' => 'The Arena History',
                'subtitle' => 'Tentang',
                'description_1' => null,
                'description_2' => null,
                'description_3' => null,
                'image_url' => null,
                'is_active' => true,
                'order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'arena',
                'title' => 'The Arena',
                'subtitle' => null,
                'description_1' => 'Sejak tahun 2022, The Arena telah menjadi rumah bagi berbagai komunitas basket di Kota Bandung dan sekitarnya. Visi kami adalah menjadi pusat pengembangan olahraga basket terbaik di Indonesia yang mendorong prestasi, komunitas, dan budaya olahraga secara berkelanjutan.',
                'description_2' => 'Misi kami adalah menyediakan fasilitas lapangan basket berkualitas tinggi yang dapat diakses oleh berbagai kalangan—dari pemain amatir hingga profesional—serta menyelenggarakan event dan turnamen basket secara rutin. More Than a Court, Its a Community.',
                'description_3' => null,
                'image_url' => 'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=1200',
                'is_active' => true,
                'order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'komunitas',
                'title' => 'Komunitas',
                'subtitle' => null,
                'description_1' => 'Komunitas The Arena Basketball merupakan ruang terbuka bagi para pecinta basket untuk berkumpul, bermain, dan berbagi semangat olahraga dalam suasana yang positif, inklusif, dan sportif.',
                'description_2' => 'Kami percaya bahwa basket bukan hanya tentang permainan, tetapi juga tentang kebersamaan, gaya hidup aktif, dan nilai sportivitas.',
                'description_3' => null,
                'image_url' => 'https://images.unsplash.com/photo-1608245449230-4ac19066d2d0?w=1200',
                'is_active' => true,
                'order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}