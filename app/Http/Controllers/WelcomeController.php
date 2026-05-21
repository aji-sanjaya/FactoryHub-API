<?php

namespace App\Http\Controllers;

use App\Helpers\MenuHelper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    public function index(): View
    {
        session()->forget('selected_root_menu_id');

        $headMenus = collect(MenuHelper::getWelcomeRootItems(100, 999))
            ->map(function (array $menu) {
                $menuCount = $this->countMenuDescendants($menu);
                $primaryPath = MenuHelper::getFirstLevel3PathForRoot($menu['id'], 100, 999);
                $description = filled(trim((string) ($menu['description'] ?? '')))
                    ? $menu['description']
                    : null;

                return [
                    'id' => $menu['id'],
                    'name' => $menu['name'],
                    'icon' => $menu['icon'] ?? 'dashboard',
                    'path' => $primaryPath,
                    'description' => $description,
                    'sub_items_count' => $menuCount,
                    'is_clickable' => filled($primaryPath),
                ];
            })
            ->values();

        $displayName = session('user_data.name')
            ?? session('user_data.value')
            ?? optional(Auth::user())->name
            ?? 'User';

        return view('pages.welcome.index', [
            'title' => 'Welcome',
            'displayName' => $displayName,
            'headMenus' => $headMenus,
        ]);
    }

    public function openRootMenu(int $rootMenuId): RedirectResponse
    {
        $targetPath = MenuHelper::getFirstLevel3PathForRoot($rootMenuId, 100, 999);

        if (blank($targetPath)) {
            return redirect()->route('dashboard');
        }

        session(['selected_root_menu_id' => $rootMenuId]);

        return redirect()->to($targetPath);
    }

    private function countMenuDescendants(array $menu): int
    {
        $subItems = $menu['subItems'] ?? [];

        if (empty($subItems)) {
            return 0;
        }

        return collect($subItems)->sum(function (array $subItem) {
            return 1 + $this->countMenuDescendants($subItem);
        });
    }
}
