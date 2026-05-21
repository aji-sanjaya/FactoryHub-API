<div class="relative" x-data="{
    dropdownOpen: false,
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
    },
    closeDropdown() {
        this.dropdownOpen = false;
    }
}" @click.away="closeDropdown()">
    @php
        $adUser = null;
        // dd(Auth::user()->name);
        if (Auth::check()) {
            $username = Auth::user()->name;
            // Try to match by value (username) or name
            $adUser = \App\Models\Idempiere\AdUser::where('name', $username)
                ->orWhere('value', $username)
                ->first();
        }
        $displayName = $adUser ? ($adUser->description ?? $adUser->name ?? Auth::user()->name) : (Auth::user()->name ?? 'Guest');
        $displayEmail = $adUser ? ($adUser->email ?? Auth::user()->email ?? '') : (Auth::user()->email ?? '');

        // Truncate display name to first 2 words only
        $nameParts = explode(' ', trim($displayName));
        $displayName = implode(' ', array_slice($nameParts, 0, 2));
    @endphp

    <!-- User Button -->
    <button
        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
        @click.prevent="toggleDropdown()"
        aria-label="User menu"
        type="button">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 2.5a3.75 3.75 0 100 7.5 3.75 3.75 0 000-7.5ZM7.75 6.25a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0ZM4 15.125A3.625 3.625 0 017.625 11.5h4.75A3.625 3.625 0 0116 15.125c0 .76-.615 1.375-1.375 1.375h-9.25A1.375 1.375 0 014 15.125Zm3.625-2.125A2.125 2.125 0 005.5 15.125h9a2.125 2.125 0 00-2.125-2.125h-4.75Z" clip-rule="evenodd" />
        </svg>
    </button>

    <!-- Dropdown Start -->
    <div x-show="dropdownOpen" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark z-50"
        style="display: none;">
        <!-- User Info -->
        <div>
            <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">{{ $displayName }}</span>
            <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $displayEmail }}</span>
        </div>

        <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-800">
            @php
                $menuItems = [
                    // [
                    //     'text' => 'Edit profile',
                    //     'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">                                                                                                                                                                                                                                                                                                                                                       </svg>',
                    //     'path' => 'profile',
                    // ],
                ];
            @endphp

            @foreach ($menuItems as $item)
                <li>
                    <a href="{{ $item['path'] }}"
                        class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                        <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                            {!! $item['icon'] !!}
                        </span>
                        {{ $item['text'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-3 space-y-1">
            <form method="POST" action="{{ route('auth.change-role') }}">
                @csrf
                <a href="#"
                    class="flex items-center w-full gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H11a4 4 0 00-4 4v2m10 0H7m10-10a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </span>
                    Change Role
                </a>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="#"
                    class="flex items-center w-full gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                    </span>
                    Sign out
                </a>
            </form>
        </div>
    </div>
    <!-- Dropdown End -->
</div>