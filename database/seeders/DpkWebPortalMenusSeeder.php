<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\MenuHelper;

class DpkWebPortalMenusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing menus
        DB::table('dpk_web_portal_menus')->delete();

        $menuGroups = MenuHelper::getMenuGroups();

        $sortOrder = 1;

        foreach ($menuGroups as $group) {
            foreach ($group['items'] as $item) {
                // Insert parent menu
                $parentId = DB::table('dpk_web_portal_menus')->insertGetId([
                    'parent_id' => null,
                    'name' => $item['name'],
                    'icon' => $item['icon'] ?? null,
                    'path' => $item['path'] ?? null,
                    'pro' => $item['pro'] ?? false,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insert sub-items if they exist
                if (isset($item['subItems']) && is_array($item['subItems'])) {
                    $subSortOrder = 1;
                    foreach ($item['subItems'] as $subItem) {
                        DB::table('dpk_web_portal_menus')->insert([
                            'parent_id' => $parentId,
                            'name' => $subItem['name'],
                            'icon' => $subItem['icon'] ?? null,
                            'path' => $subItem['path'] ?? null,
                            'pro' => $subItem['pro'] ?? false,
                            'sort_order' => $subSortOrder++,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
