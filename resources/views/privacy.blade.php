<x-layouts.app>
    <x-slot name="title">{{ __('Privacy Policy') }} — georeference.it</x-slot>

    <div class="max-w-3xl mx-auto space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('Privacy Policy') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Last updated: June 2026') }}</p>
        </div>

        <p class="text-gray-600 dark:text-gray-300">
            {{ __('georeference.it ("we", "our", or "the platform") is committed to protecting your privacy. This policy explains what information we collect, why, and how we use it.') }}
        </p>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('1. Website') }}</h2>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __('When you use georeference.it without an account, we do not collect any personal information. We use a session cookie solely to maintain your browsing state (e.g. flash messages). No tracking, analytics, or advertising cookies are set.') }}
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __('If you create an account, we store your name, email address, and a hashed password. This information is used only to authenticate you and attribute your georeferencing contributions. We do not share it with third parties.') }}
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __("Georeferencing contributions (coordinates, uncertainty, remarks) you submit are associated with your account and are publicly visible as part of the platform's open data output.") }}
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('2. Browser Extension') }}</h2>
            <p class="text-gray-600 dark:text-gray-300">
                {!! __('The georeference.it browser extension runs on GBIF occurrence pages (:code). Here is exactly what it does and does not do:', ['code' => '<code class="text-sm bg-gray-100 dark:bg-gray-800 px-1 rounded">gbif.org/occurrence/*</code>']) !!}
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-600 dark:text-gray-300 ml-2">
                <li>{!! __('It reads the GBIF occurrence key from the page URL and sends it to :code to check whether we have georeferencing data for that specimen.', ['code' => '<code class="text-sm bg-gray-100 dark:bg-gray-800 px-1 rounded">georeference.it/api/v1/occurrences/{key}</code>']) !!}</li>
                <li>{!! __('It fetches map tiles from our server (:code), which in turn proxies tiles from OpenStreetMap. No tile request is made directly from your browser to OpenStreetMap.', ['code' => '<code class="text-sm bg-gray-100 dark:bg-gray-800 px-1 rounded">georeference.it/api/v1/tiles/{z}/{x}/{y}</code>']) !!}</li>
                <li>{!! __('Your badge position and size preferences are stored locally in :code (or its Firefox equivalent). This data never leaves your browser.', ['code' => '<code class="text-sm bg-gray-100 dark:bg-gray-800 px-1 rounded">chrome.storage.local</code>']) !!}</li>
                <li>{!! __('The extension does :strong collect, transmit, or store any personal information. It does not track which occurrences you view, does not read any other page content, and does not communicate with any service other than georeference.it.', ['strong' => '<strong>'.__('not').'</strong>']) !!}</li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('3. Map tile proxy') }}</h2>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __('Our server fetches map tiles from OpenStreetMap on your behalf. The request originates from our server, not from your browser. We do not log which tile coordinates are requested beyond what is retained in standard server access logs (typically 7 days).') }}
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('4. Server logs') }}</h2>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __('Our web server retains standard access logs (IP address, request path, timestamp) for up to 30 days for security and debugging purposes. These logs are not shared with third parties.') }}
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('5. Data deletion') }}</h2>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __('You may delete your account at any time from your profile page. Doing so removes your email, name, and password. Georeferencing contributions associated with your account are anonymised rather than deleted, as they form part of the platform\'s scientific output.') }}
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('6. Contact') }}</h2>
            <p class="text-gray-600 dark:text-gray-300">
                {{ __('For any privacy-related questions, contact us at') }}
                <a href="mailto:info@georeference.it" class="text-green-600 hover:underline">info@georeference.it</a>.
            </p>
        </section>
    </div>
</x-layouts.app>
