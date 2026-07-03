<x-layouts.app>
    <div class="flex flex-col items-center">
        <a href="/" class="mb-4 hover:opacity-80">
            <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
        </a>

        <div class="w-full sm:max-w-md px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
