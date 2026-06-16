<x-layouts.app>
    <x-slot name="title">Privacy Policy — georeference.it</x-slot>

    <div class="max-w-3xl space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Privacy Policy</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Last updated: June 2026</p>
        </div>

        <p class="text-gray-600 dark:text-gray-300">
            georeference.it ("we", "our", or "the platform") is committed to protecting your privacy.
            This policy explains what information we collect, why, and how we use it.
        </p>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">1. Website</h2>
            <p class="text-gray-600 dark:text-gray-300">
                When you use georeference.it without an account, we do not collect any personal information.
                We use a session cookie solely to maintain your browsing state (e.g. flash messages). No
                tracking, analytics, or advertising cookies are set.
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                If you create an account, we store your name, email address, and a hashed password.
                This information is used only to authenticate you and attribute your georeferencing
                contributions. We do not share it with third parties.
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                Georeferencing contributions (coordinates, uncertainty, remarks) you submit are associated
                with your account and are publicly visible as part of the platform's open data output.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">2. Browser Extension</h2>
            <p class="text-gray-600 dark:text-gray-300">
                The georeference.it browser extension runs on GBIF occurrence pages
                (<code class="text-sm bg-gray-100 dark:bg-gray-800 px-1 rounded">gbif.org/occurrence/*</code>).
                Here is exactly what it does and does not do:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-300 ml-2">
                <li>It reads the GBIF occurrence key from the page URL and sends it to
                    <code class="text-sm bg-gray-100 dark:bg-gray-800 px-1 rounded">georeference.it/api/v1/occurrences/{key}</code>
                    to check whether we have georeferencing data for that specimen.</li>
                <li>It fetches map tiles from our server
                    (<code class="text-sm bg-gray-100 dark:bg-gray-800 px-1 rounded">georeference.it/api/v1/tiles/{z}/{x}/{y}</code>),
                    which in turn proxies tiles from OpenStreetMap. No tile request is made directly from
                    your browser to OpenStreetMap.</li>
                <li>Your badge position and size preferences are stored locally in
                    <code class="text-sm bg-gray-100 dark:bg-gray-800 px-1 rounded">chrome.storage.local</code>
                    (or its Firefox equivalent). This data never leaves your browser.</li>
                <li>The extension does <strong>not</strong> collect, transmit, or store any personal
                    information. It does not track which occurrences you view, does not read any other
                    page content, and does not communicate with any service other than georeference.it.</li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">3. Map tile proxy</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Our server fetches map tiles from OpenStreetMap on your behalf. The request originates
                from our server, not from your browser. We do not log which tile coordinates are requested
                beyond what is retained in standard server access logs (typically 7 days).
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">4. Server logs</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Our web server retains standard access logs (IP address, request path, timestamp) for
                up to 30 days for security and debugging purposes. These logs are not shared with third
                parties.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">5. Data deletion</h2>
            <p class="text-gray-600 dark:text-gray-300">
                You may delete your account at any time from your profile page. Doing so removes your
                email, name, and password. Georeferencing contributions associated with your account
                are anonymised rather than deleted, as they form part of the platform's scientific output.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">6. Contact</h2>
            <p class="text-gray-600 dark:text-gray-300">
                For any privacy-related questions, contact us at
                <a href="mailto:privacy@georeference.it" class="text-green-600 hover:underline">privacy@georeference.it</a>.
            </p>
        </section>
    </div>
</x-layouts.app>
