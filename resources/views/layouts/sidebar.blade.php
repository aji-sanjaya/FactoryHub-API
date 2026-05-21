@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();

    // Get current path
    $currentPath = request()->path();
@endphp

<aside id="sidebar"
    class="fixed flex flex-col mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200"
    x-data="{
        openSubmenus: {},
        init() {
            // Auto-open Dashboard menu on page load
            this.initializeActiveMenus();
        },
        matchesPath(currentPath, targetPath) {
            if (!targetPath) {
                return false;
            }

            const normalizedCurrentPath = '/' + String(currentPath || '').replace(/^\/+/, '');
            const normalizedTargetPath = '/' + String(targetPath || '').replace(/^\/+/, '');

            return normalizedCurrentPath === normalizedTargetPath || normalizedCurrentPath.startsWith(normalizedTargetPath + '/');
        },
        initializeActiveMenus() {
            const currentPath = '{{ $currentPath }}';

            @foreach ($menuGroups as $groupIndex => $menuGroup)
                @foreach ($menuGroup['items'] as $itemIndex => $item)
                    @if (isset($item['subItems']))
                        // Check if any submenu item matches current path
                        @foreach ($item['subItems'] as $subItem)
                            if (this.matchesPath(currentPath, '{{ $subItem['path'] ?? '' }}') ||
                                this.matchesPath(window.location.pathname, '{{ $subItem['path'] ?? '' }}')) {
                                this.openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] = true;
                        } @endforeach
                    @endif
                @endforeach
            @endforeach
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            const newState = !this.openSubmenus[key];

            // Close all other submenus when opening a new one
            if (newState) {
                this.openSubmenus = {};
            }

            this.openSubmenus[key] = newState;
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return this.openSubmenus[key] || false;
        },
        isActive(path) {
            return this.matchesPath(window.location.pathname, path) || this.matchesPath('{{ $currentPath }}', path);
        }
    }" :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }" @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">
    <!-- Logo Section -->
    <div class="pt-5 pb-7 flex" :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
        'xl:justify-center' :
        'justify-start'">
        <a href="/" class="flex items-center gap-2.5">
            {{-- Expanded: icon + text --}}
            <template x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-orange-500 shadow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M13 2L4.09 12.11a1 1 0 0 0 .77 1.64H11l-1 8 8.91-10.11a1 1 0 0 0-.77-1.64H13l1-8Z"
                                fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="text-base font-bold tracking-wide text-gray-900 dark:text-white">FactoryHub</span>
                </div>
            </template>
            {{-- Collapsed: icon only --}}
            <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-500 shadow">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M13 2L4.09 12.11a1 1 0 0 0 .77 1.64H11l-1 8 8.91-10.11a1 1 0 0 0-.77-1.64H13l1-8Z"
                            fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </template>
        </a>
    </div>

    <!-- Navigation Menu -->
    <div class="flex flex-col flex-1 overflow-y-auto duration-300 ease-linear no-scrollbar">
        <nav class="mb-6">
            <div class="flex flex-col gap-4">
                @foreach ($menuGroups as $groupIndex => $menuGroup)
                    <div>
                        <!-- Menu Group Title -->
                        <h2 class="mb-4 text-xs uppercase flex leading-[20px] text-gray-400" :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                                                    'lg:justify-center' : 'justify-start'">
                            <template
                                x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                <span>{{ $menuGroup['title'] }}</span>
                            </template>
                            <template
                                x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                                        fill="currentColor" />
                                </svg>
                            </template>
                        </h2>

                        <!-- Menu Items -->
                        <ul class="flex flex-col gap-1">
                            @foreach ($menuGroup['items'] as $itemIndex => $item)
                                <li>
                                    @if (isset($item['subItems']))
                                        <!-- Menu Item with Submenu -->
                                        <button @click="toggleSubmenu({{ $groupIndex }}, {{ $itemIndex }})"
                                            class="menu-item group w-full" :class="[
                                                                                                                        isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) ?
                                                                                                                        'menu-item-active' : 'menu-item-inactive',
                                                                                                                        !$store.sidebar.isExpanded && !$store.sidebar.isHovered ?
                                                                                                                        'xl:justify-center' : 'xl:justify-start'
                                                                                                                    ]">

                                            <!-- Icon -->
                                            <span
                                                :class="isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) ?
                                                                                                                            'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                                {!! MenuHelper::getIconSvg($item['icon'] ?? 'dashboard') !!}
                                            </span>

                                            <!-- Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                {{ $item['name'] }}
                                                @if (!empty($item['new']))
                                                    <span class="absolute right-10"
                                                        :class="isActive('{{ $item['path'] ?? '' }}') ?
                                                                                                                                                            'menu-dropdown-badge menu-dropdown-badge-active' :
                                                                                                                                                            'menu-dropdown-badge menu-dropdown-badge-inactive'">
                                                        new
                                                    </span>
                                                @endif
                                            </span>

                                            <!-- Chevron Down Icon -->
                                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="ml-auto w-3 h-3 transition-transform duration-200" :class="{
                                                                                                                            'rotate-180 text-brand-500': isSubmenuOpen({{ $groupIndex }},
                                                                                                                                {{ $itemIndex }})
                                                                                                                        }"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <!-- Submenu -->
                                        <div
                                            x-show="isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)"
                                            x-collapse>
                                            <ul class="mt-2 space-y-1 ml-4">
                                                @foreach ($item['subItems'] as $subItem)
                                                    <li>
                                                        <a href="{{ $subItem['path'] ?? '#' }}" class="menu-dropdown-item"
                                                            :class="isActive('{{ $subItem['path'] ?? '' }}') ?
                                                                                                                                                                'menu-dropdown-item-active' :
                                                                                                                                                                'menu-dropdown-item-inactive'">
                                                            <span class="flex-shrink-0"
                                                                :class="isActive('{{ $subItem['path'] ?? '' }}') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                                                {!! MenuHelper::getIconSvg($subItem['icon'] ?? 'circle-dot') !!}
                                                            </span>
                                                            {{ $subItem['name'] }}
                                                            <span class="flex items-center gap-1 ml-auto">
                                                                @if (!empty($subItem['new']))
                                                                    <span
                                                                        :class="isActive('{{ $subItem['path'] ?? '' }}') ?
                                                                                                                                                                                                    'menu-dropdown-badge menu-dropdown-badge-active' :
                                                                                                                                                                                                    'menu-dropdown-badge menu-dropdown-badge-inactive'">
                                                                        new
                                                                    </span>
                                                                @endif
                                                                @if (!empty($subItem['pro']))
                                                                    <span
                                                                        :class="isActive('{{ $subItem['path'] ?? '' }}') ?
                                                                                                                                                                                                    'menu-dropdown-badge-pro menu-dropdown-badge-pro-active' :
                                                                                                                                                                                                    'menu-dropdown-badge-pro menu-dropdown-badge-pro-inactive'">
                                                                        pro
                                                                    </span>
                                                                @endif
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <!-- Simple Menu Item -->
                                        <a href="{{ $item['path'] ?? '#' }}" class="menu-item group" :class="[
                                                                                                                        isActive('{{ $item['path'] ?? '' }}') ? 'menu-item-active' :
                                                                                                                        'menu-item-inactive',
                                                                                                                        (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                                                                                                                        'xl:justify-center' :
                                                                                                                        'justify-start'
                                                                                                                    ]">

                                            <!-- Icon -->
                                            <span
                                                :class="isActive('{{ $item['path'] ?? '' }}') ? 'menu-item-icon-active' :
                                                                                                                            'menu-item-icon-inactive'">
                                                {!! MenuHelper::getIconSvg($item['icon'] ?? 'dashboard') !!}
                                            </span>

                                            <!-- Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                {{ $item['name'] }}
                                                @if (!empty($item['new']))
                                                    <span
                                                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-brand-500 text-white">
                                                        new
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </nav>

        <div class="mb-6">
            <a href="{{ route('dashboard') }}"
                class="group flex w-full items-center gap-3 rounded-xl border px-3 py-3 text-sm font-medium transition-colors"
                :class="[
                    (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start',
                    'border-brand-200 bg-brand-50 text-brand-600 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300'
                ]">
                <span class="text-brand-600 dark:text-brand-300">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.707 1.293a1 1 0 00-1.414 0l-7 7A1 1 0 003 10h1v6a1 1 0 001 1h3.5a.5.5 0 00.5-.5V13a1 1 0 011-1h0a1 1 0 011 1v3.5a.5.5 0 00.5.5H15a1 1 0 001-1v-6h1a1 1 0 00.707-1.707l-7-7z" />
                    </svg>
                </span>
                <span
                    x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                    class="truncate">
                    Back Home
                </span>
            </a>
        </div>

        <!-- Sidebar Widget -->
        <div x-data x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
            x-transition class="mt-auto">
            <x-layouts.sidebar-widget />
        </div>

    </div>
</aside>

<!-- Mobile Overlay -->
<div x-show="$store.sidebar.isMobileOpen" @click="$store.sidebar.setMobileOpen(false)"
    class="fixed z-50 h-screen w-full bg-gray-900/50"></div>