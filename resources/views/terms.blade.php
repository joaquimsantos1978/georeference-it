<x-layouts.app>
    <x-slot name="title">Terms of Use — georeference.it</x-slot>

    <div class="max-w-3xl space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Terms of Use</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Last updated: June 2026</p>
        </div>

        <p class="text-gray-600 dark:text-gray-300">
            By using georeference.it (the website, API, or browser extension), you agree to these terms.
            If you do not agree, please do not use the platform.
        </p>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">1. Purpose</h2>
            <p class="text-gray-600 dark:text-gray-300">
                georeference.it is a crowdsourcing platform for georeferencing natural history collection
                specimens published on GBIF. Its goal is to improve the quality of biodiversity data for
                scientific and conservation use.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">2. Contributions</h2>
            <p class="text-gray-600 dark:text-gray-300">
                By submitting a georeferencing suggestion, you confirm that:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-300 ml-2">
                <li>The coordinates and associated data represent your honest best assessment of the
                    specimen's locality, following accepted georeferencing practices.</li>
                <li>You release your contribution under the
                    <a href="https://creativecommons.org/publicdomain/zero/1.0/" target="_blank" class="text-green-600 hover:underline">Creative Commons Zero (CC0) public domain dedication</a>,
                    allowing anyone to use, share, and build upon it without restriction.</li>
            </ul>
            <p class="text-gray-600 dark:text-gray-300">
                Deliberately incorrect georeferencing is not permitted and may result in account suspension.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">3. Accounts</h2>
            <p class="text-gray-600 dark:text-gray-300">
                An account is optional but required to track contributions and participate in validation.
                You are responsible for keeping your credentials secure. We reserve the right to suspend
                accounts that abuse the platform.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">4. API</h2>
            <p class="text-gray-600 dark:text-gray-300">
                The georeference.it API is provided for reasonable use by data publishers, researchers,
                and developers. Automated mass-fetching that places excessive load on our servers is
                not permitted. We reserve the right to rate-limit or block abusive clients.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">5. Browser extension</h2>
            <p class="text-gray-600 dark:text-gray-300">
                The browser extension is provided as-is. It reads GBIF occurrence pages and contacts
                our API to display georeferencing status. It does not modify GBIF data.
                The extension's source code is openly available on
                <a href="https://github.com/joaquimsantos1978/georeference-it" target="_blank" class="text-green-600 hover:underline">GitHub</a>.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">6. Map tiles</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Map tiles are provided by <a href="https://www.openstreetmap.org/copyright" target="_blank" class="text-green-600 hover:underline">OpenStreetMap contributors</a>
                under the Open Database License (ODbL). Our tile proxy is for use within this platform
                only and may not be used as a general-purpose tile service.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">7. Availability</h2>
            <p class="text-gray-600 dark:text-gray-300">
                We aim to keep the platform running reliably but make no guarantees of uptime or
                continuity. We may modify or discontinue features at any time.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">8. Contact</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Questions about these terms:
                <a href="mailto:hello@georeference.it" class="text-green-600 hover:underline">hello@georeference.it</a>
            </p>
        </section>
    </div>
</x-layouts.app>
