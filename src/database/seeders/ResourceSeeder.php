<?php
namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            ['name' => 'Тренажёрный зал', 'type' => 'gym',   'capacity' => 20, 'floor' => 1, 'price_per_hour' => 500, 'description' => 'Полный набор тренажёров'],
            ['name' => 'Зал йоги',        'type' => 'yoga',  'capacity' => 15, 'floor' => 2, 'price_per_hour' => 300, 'description' => 'Тихий зал для йоги и растяжки'],
            ['name' => 'Бассейн',         'type' => 'pool',  'capacity' => 10, 'floor' => 1, 'price_per_hour' => 700, 'description' => '25-метровый бассейн'],
            ['name' => 'Сквош-корт',      'type' => 'court', 'capacity' => 4,  'floor' => 3, 'price_per_hour' => 400, 'description' => 'Профессиональный корт для сквоша'],
        ];

        foreach ($resources as $r) {
            Resource::create($r);
        }
    }
}
