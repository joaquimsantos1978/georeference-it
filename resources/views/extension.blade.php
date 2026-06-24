<x-layouts.app title="Browser Extension" description="Install the georeference.it browser extension for Chrome and Firefox to georeference GBIF specimens directly while browsing occurrence pages.">

    <div class="max-w-3xl mx-auto space-y-8">

        <div class="space-y-3">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">georeference.it Browser Extension</h1>
            <p class="text-lg text-gray-600 dark:text-gray-300">
                See georeferencing status and suggested coordinates directly on any GBIF occurrence page —
                without leaving your browser.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="https://chromewebstore.google.com/detail/georeferenceit/kpeachknjnikeihigoapgognjmgkimbd"
               target="_blank"
               class="inline-flex items-center gap-2 bg-green-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 2.5a7.5 7.5 0 0 1 6.547 3.836L12 8.5a3.5 3.5 0 0 0-3.5 3.5H4.522A7.5 7.5 0 0 1 12 4.5zm0 5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zm4.938 1.164h3.04A7.5 7.5 0 0 1 12 19.5a7.47 7.47 0 0 1-5.196-2.094l3.03-5.25A3.503 3.503 0 0 0 12 13.5a3.5 3.5 0 0 0 2.134-.72l2.804-.116z"/></svg>
                Install for Chrome
            </a>
            <a href="https://addons.mozilla.org/en-US/firefox/addon/georeference-it/"
               target="_blank"
               class="inline-flex items-center gap-2 bg-green-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-.5 3.5c1.6 0 3.1.5 4.3 1.4-.4-.1-.8-.1-1.2-.1-2.8 0-5.1 2-5.5 4.7H7.5c.4-3.4 3.2-6 6-6zm.5 13c-3.3 0-6-2.7-6-6 0-.3 0-.6.1-.9.5 2.2 2.5 3.9 4.9 3.9 1.1 0 2.1-.4 2.9-1H16c-.9 2.4-3.2 4-5 4z"/></svg>
                Install for Firefox
            </a>
            <a href="https://github.com/joaquimsantos1978/georeference-it/tree/main/browser-extension"
               target="_blank"
               class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 px-2 py-2.5">
                Source code →
            </a>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="text-2xl mb-2">📍</div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Status at a glance</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Instantly see whether an occurrence is ungeoreferenced, has a pending suggestion,
                    is validated, or conflicts with GBIF's coordinates.
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="text-2xl mb-2">🗺️</div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Interactive map</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    A pannable, zoomable map shows the suggested coordinates directly in the GBIF page,
                    in a draggable and resizable panel.
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="text-2xl mb-2">🔗</div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">One-click access</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Jump straight to the georeferencing page for any occurrence — to review, correct,
                    or georeference it yourself.
                </p>
            </div>
        </div>

        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">See it in action</h2>
            <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm">
                <img src="{{ asset('images/extension-screenshot-1.png') }}"
                     alt="georeference.it extension panel on a GBIF occurrence page, showing a map with suggested coordinates and a pending suggestion badge"
                     class="w-full">
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                The extension panel appears automatically on GBIF occurrence pages, showing the suggested
                coordinates on a map and the current status — here, a pending suggestion for
                <em>Collema subnigrescens</em> from Portugal.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">How it works</h2>
            <ol class="space-y-3 text-gray-600 dark:text-gray-300">
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 rounded-full text-sm font-semibold flex items-center justify-center">1</span>
                    <span>Navigate to any occurrence page on <strong>gbif.org/occurrence/…</strong></span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 rounded-full text-sm font-semibold flex items-center justify-center">2</span>
                    <span>The extension checks the georeference.it API for that occurrence.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 rounded-full text-sm font-semibold flex items-center justify-center">3</span>
                    <span>If we have data, a panel appears with the georeferencing status, a map, and a link to act on it.</span>
                </li>
            </ol>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Privacy</h2>
            <p class="text-gray-600 dark:text-gray-300">
                The extension only sends the GBIF occurrence key (a public identifier) to our API.
                It does not collect, transmit, or store any personal information.
                Your panel position and size are saved locally in your browser only.
                Read our full <a href="{{ route('privacy') }}" class="text-green-600 hover:underline">Privacy Policy</a>.
            </p>
        </section>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-700">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Want to contribute?</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                The extension is open source. Bug reports, translations, and pull requests are welcome.
            </p>
            <a href="https://github.com/joaquimsantos1978/georeference-it" target="_blank"
               class="inline-block bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                View on GitHub →
            </a>
        </div>

    </div>
</x-layouts.app>
