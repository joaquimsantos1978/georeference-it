<x-layouts.app title="Georeferencing Guide" description="A practical 2-minute guide to georeferencing natural history specimens following GBIF best practices. Learn how to find coordinates, estimate uncertainty, and document your sources.">

    <div class="max-w-3xl mx-auto space-y-8">

        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Georeferencing Guide</h1>
            <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">A practical summary based on <a href="https://doi.org/10.35035/e09p-h128" target="_blank" class="text-green-600 hover:underline">Georeferencing Best Practices</a> (Chapman &amp; Wieczorek, 2020) — read in ~2 minutes.</p>
        </div>

        {{-- What is georeferencing --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 space-y-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">What is georeferencing?</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Georeferencing is the process of interpreting a textual locality description — such as <em>"5 km NW of Ratanakiri, Cambodia"</em> — into a geographic coordinate pair (latitude/longitude) plus a <strong>coordinate uncertainty radius</strong> that captures the imprecision of the original description.
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                The result is not just a point on a map, but a <strong>point-radius</strong>: the true location lies somewhere within a circle centered on the given coordinates.
            </p>
        </section>

        {{-- Step by step --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Step-by-step</h2>

            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 flex items-center justify-center font-semibold text-xs">1</span>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100">Read the full label</p>
                        <p>Use all available fields — locality, state/province, country, collector, date, habitat. They provide context to disambiguate place names.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 flex items-center justify-center font-semibold text-xs">2</span>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100">Find the named place</p>
                        <p>Use a gazetteer (Google Maps, GeoNames, Getty Thesaurus of Geographic Names) to locate the feature described. Prefer historical gazetteers when the collection is old — place names change.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 flex items-center justify-center font-semibold text-xs">3</span>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100">Place the coordinate</p>
                        <p>For a named feature, use its center. For directional descriptions (<em>"5 km NW of [town]"</em>), place the point 5 km NW of the town's center — the uncertainty radius then accounts for the town's own extent plus the imprecision of the stated distance. For administrative areas with no finer detail, use the centroid.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 flex items-center justify-center font-semibold text-xs">4</span>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100">Estimate the uncertainty</p>
                        <p>The uncertainty radius must encompass <em>every possible</em> true location. It combines: the extent of the named feature, the precision of any distance/direction given, and GPS/map accuracy. When in doubt, go larger — underestimating uncertainty is worse than overestimating it.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 flex items-center justify-center font-semibold text-xs">5</span>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100">Document your sources and remarks</p>
                        <p>Note which gazetteer or tool you used and any assumptions you made. This allows future reviewers to verify or improve the georeference.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Uncertainty guidance --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 space-y-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Uncertainty quick reference</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="text-xs text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-2 pr-4">Locality description</th>
                            <th class="py-2">Typical uncertainty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr><td class="py-2 pr-4">GPS coordinates on label</td><td class="py-2">10–100 m</td></tr>
                        <tr><td class="py-2 pr-4">Named village or small town</td><td class="py-2">1–5 km</td></tr>
                        <tr><td class="py-2 pr-4">"5 km NW of [town]"</td><td class="py-2">Radius of town + ~500 m (imprecision of the stated distance)</td></tr>
                        <tr><td class="py-2 pr-4">Named river, lake or mountain</td><td class="py-2">Radius of smallest circle enclosing the feature</td></tr>
                        <tr><td class="py-2 pr-4">County / district only</td><td class="py-2">Radius of the administrative area</td></tr>
                        <tr><td class="py-2 pr-4">Country only</td><td class="py-2">Radius of the country</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- When NOT to georeference --}}
        <section class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-6 border border-amber-200 dark:border-amber-700 space-y-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">When to skip</h2>
            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1 list-disc list-inside">
                <li>The locality is too vague to narrow down below country level.</li>
                <li>Multiple equally plausible interpretations exist and you cannot resolve the ambiguity.</li>
                <li>The place name is unresolvable in any gazetteer, even with contextual clues.</li>
            </ul>
            <p class="text-sm text-gray-600 dark:text-gray-300">In these cases it is better to leave it ungeoreferenced than to assign misleading coordinates.</p>
        </section>

        {{-- Further reading --}}
        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Further reading</h2>
            <ul class="text-sm space-y-2">
                <li>
                    <a href="https://doi.org/10.35035/e09p-h128" target="_blank" class="text-green-600 hover:underline font-medium">Georeferencing Best Practices</a>
                    <span class="text-gray-500"> — Chapman &amp; Wieczorek (2020). The authoritative reference. GBIF Secretariat.</span>
                </li>
                <li>
                    <a href="https://doi.org/10.35035/gdwq-3v93" target="_blank" class="text-green-600 hover:underline font-medium">Georeferencing Quick Reference Guide</a>
                    <span class="text-gray-500"> — Zermoglio et al. (2020). Step-by-step worked examples.</span>
                </li>
                <li>
                    <a href="https://georeferencing.org/georefcalculator/gc.html" target="_blank" class="text-green-600 hover:underline font-medium">Georeferencing Calculator</a>
                    <span class="text-gray-500"> — Online tool for computing point-radius georeferences from locality descriptions.</span>
                </li>
            </ul>
        </section>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-700">
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Ready to put this into practice?</p>
            <a href="{{ route('georef.index') }}" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-700">Start georeferencing →</a>
        </div>

    </div>
</x-layouts.app>
