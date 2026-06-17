<x-layouts.app>
    <x-slot name="title">{{ __('About') }} — georeference.it</x-slot>

    <div class="max-w-3xl mx-auto space-y-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('About georeference.it') }}</h1>

        <div class="prose dark:prose-invert max-w-none space-y-4 text-gray-600 dark:text-gray-300">
            <p class="text-lg">
                {{ __('georeference.it is an open-source crowdsourcing platform for georeferencing natural history collection specimens from GBIF.') }}
            </p>
            <p>
                {{ __('Millions of biodiversity records in GBIF — particularly those from natural history collections — lack geographic coordinates. This makes them invisible to spatial analyses and conservation planning.') }}
            </p>
            <p>
                {{ __('Our platform allows volunteers to place these specimens on the map, following the Georeferencing Best Practices (Chapman & Wieczorek, 2020). A consensus-based validation system ensures quality: georeferencing suggestions accumulate votes from experienced contributors until a confidence threshold is reached.') }}
            </p>
        </div>

        <a href="{{ route('how-it-works') }}" class="inline-block text-sm text-green-600 hover:underline">How it works in detail →</a>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Open source') }}</h3>
                <p class="text-sm text-gray-500">{{ __('The platform code is freely available on GitHub. Contributions welcome.') }}</p>
                <a href="https://github.com/joaquimsantos1978/georeference-it" target="_blank" class="text-sm text-green-600 hover:underline mt-2 block">GitHub →</a>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Standards-based') }}</h3>
                <p class="text-sm text-gray-500">{{ __('All georeferencing output uses Darwin Core terms and follows the Georeferencing Quick Reference Guide.') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('API access') }}</h3>
                <p class="text-sm text-gray-500">{{ __('Publishers can download validated georeferencing data via our API to enrich their GBIF datasets.') }}</p>
                <a href="{{ route('api-docs') }}" class="text-sm text-green-600 hover:underline mt-2 block">{{ __('API docs') }} →</a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Contact</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Questions, suggestions, or collaboration proposals:</p>
            <a href="mailto:joaquimsantos@gmail.com" class="text-sm text-green-600 hover:underline mt-1 block">joaquimsantos@gmail.com</a>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-700">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Start contributing') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ __('No account needed to start. Create one to track your contributions and earn recognition.') }}</p>
            <div class="flex gap-3">
                <a href="{{ route('georef.index') }}" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-700">{{ __('Start georeferencing') }}</a>
                <a href="{{ route('register') }}" class="border border-green-600 text-green-600 px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-50 dark:hover:bg-green-900/30">{{ __('Create account') }}</a>
            </div>
        </div>
    </div>
</x-layouts.app>
