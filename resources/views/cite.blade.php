<x-layouts.app>
<x-slot name="title">{{ __('How to cite') }} — georeference.it</x-slot>

<div class="max-w-3xl mx-auto space-y-8">

    <h1 class="text-2xl font-bold text-gray-900">{{ __('How to cite') }}</h1>

    <div class="space-y-6">

        {{-- Recommended citation --}}
        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-700">{{ __('Recommended citation') }}</h2>
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-5 py-4 font-mono text-sm text-gray-800 leading-relaxed">
                Santos, J. ({{ date('Y') }}). <em>georeference.it</em>: Crowdsourced georeferencing of natural history specimens. Available at: <a href="https://georeference.it" class="text-green-600 hover:underline">https://georeference.it</a>
            </div>
        </section>

        {{-- BibTeX --}}
        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-700">BibTeX</h2>
            <pre class="bg-gray-50 border border-gray-200 rounded-lg px-5 py-4 text-sm text-gray-800 overflow-x-auto leading-relaxed">@misc{georeference_it_{{ date('Y') }},
  author    = {Santos, Joaquim},
  title     = {georeference.it: Crowdsourced georeferencing of natural history specimens},
  year      = {{{ date('Y') }}},
  url       = {https://georeference.it}
}</pre>
        </section>

        {{-- Data citation --}}
        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-700">{{ __('Citing the data') }}</h2>
            <p class="text-sm text-gray-600">
                {{ __('If you use georeference data from this platform in a publication, please also cite the original occurrence records from') }}
                <a href="https://www.gbif.org" target="_blank" class="text-green-600 hover:underline">GBIF</a>
                {{ __('using the dataset-specific citation provided there.') }}
            </p>
        </section>

    </div>

</div>
</x-layouts.app>
