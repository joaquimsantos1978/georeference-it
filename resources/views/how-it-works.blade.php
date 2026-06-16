<x-layouts.app>
    <x-slot name="title">How it works — georeference.it</x-slot>

    <div class="max-w-3xl space-y-12">

        <div class="space-y-3">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">How georeference.it works</h1>
            <p class="text-lg text-gray-600 dark:text-gray-300">
                Millions of natural history specimens in GBIF have no coordinates — only a written
                description of where they were collected. georeference.it is a platform for volunteers
                to fix that, one locality at a time.
            </p>
        </div>

        {{-- The problem --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">The problem</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Natural history collections hold hundreds of millions of specimens collected over
                centuries. Most were recorded long before GPS existed. A specimen label might read
                <em>"Serra da Estrela, Portugal"</em> or <em>"near the old mill, Sierra Nevada"</em> —
                rich locality information, but no decimal coordinates that a computer can use.
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                Without coordinates, these records are invisible to species distribution models,
                protected area assessments, climate change analyses, and most modern biodiversity
                informatics workflows. The records exist in GBIF but are excluded from the majority
                of research uses.
            </p>
        </section>

        {{-- What georeferencing is --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">What georeferencing is</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Georeferencing is the process of interpreting a textual locality description and
                assigning it decimal coordinates and a coordinate uncertainty radius — the smallest
                circle that could reasonably contain the actual collection location.
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                A well-georeferenced record includes:
            </p>
            <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-300 ml-2">
                <li><strong>decimalLatitude / decimalLongitude</strong> — the best estimate of the location</li>
                <li><strong>coordinateUncertaintyInMeters</strong> — the radius of the uncertainty circle</li>
                <li><strong>geodeticDatum</strong> — the coordinate reference system (WGS84)</li>
                <li><strong>georeferenceProtocol</strong> — the method used</li>
                <li><strong>georeferencedBy / georeferencedDate</strong> — provenance</li>
            </ul>
            <p class="text-gray-600 dark:text-gray-300">
                We follow the
                <a href="https://doi.org/10.35035/e09p-h128" target="_blank" class="text-green-600 hover:underline">Georeferencing Best Practices</a>
                (Chapman &amp; Wieczorek, 2020) and the
                <a href="https://doi.org/10.35035/gdwq-3v93" target="_blank" class="text-green-600 hover:underline">Georeferencing Quick Reference Guide</a>.
            </p>
        </section>

        {{-- Locality groups --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Locality groups</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Many GBIF records share the same written locality — different specimens of different
                species all collected at "Parque Nacional da Peneda-Gerês, Minho, Portugal", for example.
                Georeferencing one of them effectively georeferences all of them.
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                georeference.it groups records by their combination of locality fields
                (country, state/province, county, municipality, island, verbatim locality).
                Each group is georeferenced once, and the result applies to all records in the group.
                This makes the work much more efficient: a single georeferencing effort can improve
                hundreds or thousands of GBIF records simultaneously.
            </p>
        </section>

        {{-- How suggestions work --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Suggestions and validation</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Anyone can submit a georeferencing suggestion for a locality group. Suggestions are
                reviewed by other contributors. To avoid relying on a single person's judgement, a
                consensus mechanism is used:
            </p>
            <ol class="space-y-3 text-gray-600 dark:text-gray-300">
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 rounded-full text-sm font-semibold flex items-center justify-center">1</span>
                    <span>A contributor places a point on the map and sets the uncertainty radius. They may add remarks explaining their reasoning.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 rounded-full text-sm font-semibold flex items-center justify-center">2</span>
                    <span>Other contributors review the suggestion. They can agree with it (adding a validation vote), submit a competing suggestion if they disagree, or leave a comment.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 rounded-full text-sm font-semibold flex items-center justify-center">3</span>
                    <span>Once a suggestion accumulates enough weighted validation votes, it is marked <strong>validated</strong> and becomes the platform's official georeferencing for that locality group.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 rounded-full text-sm font-semibold flex items-center justify-center">4</span>
                    <span>Data publishers (natural history collections) can retrieve validated georeferences via the API and submit them back to GBIF, improving the public dataset.</span>
                </li>
            </ol>
        </section>

        {{-- Consistency checking --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Consistency checking</h2>
            <p class="text-gray-600 dark:text-gray-300">
                For localities where GBIF already has coordinates (georeferenced by the data publisher),
                georeference.it runs an automatic consistency check. It clusters all georeferenced
                occurrences within a locality group and checks whether they agree spatially.
            </p>
            <p class="text-gray-600 dark:text-gray-300">
                If records in the same named locality are spread across widely separated locations,
                something is likely wrong — a coordinate error, a transcription mistake, or a genuine
                ambiguity in the locality name. These groups are flagged as <strong>inconsistent</strong>
                and presented to contributors for review, with one competing suggestion per cluster
                so the community can identify which coordinates are correct.
            </p>
        </section>

        {{-- Quality levels --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Status labels</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach([
                    ['Ungeoreferenced',   '#dc2626', '#fef2f2', '#fca5a5', 'No coordinates exist for this locality group in GBIF or georeference.it.'],
                    ['Suggestion pending','#d97706', '#fffbeb', '#fcd34d', 'A georeferencing suggestion has been submitted and is awaiting validation.'],
                    ['Conflicted',        '#7c3aed', '#f5f3ff', '#c4b5fd', 'Two or more competing suggestions exist. The community needs to resolve the disagreement.'],
                    ['Validated',         '#166534', '#f0fdf4', '#86efac', 'A suggestion has accumulated enough validation votes and is the platform\'s accepted georeferencing.'],
                    ['GBIF georef',       '#1d4ed8', '#eff6ff', '#93c5fd', 'GBIF already has coordinates for this group from the data publisher. May still need review.'],
                    ['Reviewed',          '#166534', '#f0fdf4', '#86efac', 'GBIF\'s coordinates have been reviewed and confirmed correct by the community.'],
                ] as [$label, $color, $bg, $border, $desc])
                <div class="flex gap-3 items-start p-3 rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <span class="flex-shrink-0 mt-0.5 px-2 py-0.5 rounded-full text-xs font-semibold border"
                          style="color:{{ $color }};background:{{ $bg }};border-color:{{ $border }}">{{ $label }}</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </section>

        {{-- CTA --}}
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-700">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Ready to contribute?</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                No account needed to start georeferencing. It takes about two minutes per locality.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('georef.index') }}" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-700">Start georeferencing</a>
                <a href="{{ route('about') }}" class="border border-green-600 text-green-600 px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-50 dark:hover:bg-green-900/30">About the project</a>
            </div>
        </div>

    </div>
</x-layouts.app>
