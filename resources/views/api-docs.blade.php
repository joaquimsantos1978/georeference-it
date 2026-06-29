<x-layouts.app title="API Documentation" description="Access georeferenced GBIF occurrence data via the georeference.it open API. DarwinCore-compliant JSON and JSON-LD endpoints with filtering and pagination.">

<div class="max-w-4xl mx-auto space-y-4 mb-8">
    <div class="flex items-center gap-2">
        <h1 class="text-2xl font-bold text-gray-900">georeference.it API</h1>
        <span class="text-xs font-mono bg-gray-100 text-gray-500 px-2 py-0.5 rounded">v1</span>
        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Public · No auth required</span>
        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">60 req/min</span>
    </div>
    <p class="text-sm text-gray-500 max-w-xl">Open REST API returning <a href="https://dwc.tdwg.org/terms/#location" target="_blank" class="text-green-600 hover:underline">Darwin Core</a> occurrence data with community georeferences. No API key needed.</p>
    <div class="flex items-center gap-3 flex-wrap">
        <code class="bg-gray-100 text-gray-700 font-mono text-sm px-4 py-2 rounded-lg">{{ url('/api/v1') }}</code>
        <a href="{{ url('/api/v1/occurrences') }}" target="_blank"
           class="text-xs border border-gray-300 text-gray-600 hover:border-green-500 hover:text-green-600 px-3 py-2 rounded-lg transition">
            Try it → <span class="font-mono">/occurrences</span>
        </a>
    </div>
</div>

<div class="max-w-4xl mx-auto">
<div class="flex gap-8">

    {{-- Sticky sidebar --}}
    <nav class="hidden lg:block w-44 flex-shrink-0">
        <div class="sticky top-4 space-y-1 text-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Contents</p>
            <a href="#endpoints"   class="block text-gray-600 dark:text-gray-400 hover:text-green-600 py-0.5">Endpoints</a>
            <a href="#occurrences" class="block text-gray-500 dark:text-gray-500 hover:text-green-600 py-0.5 pl-3 text-xs">GET occurrences</a>
            <a href="#single"      class="block text-gray-500 dark:text-gray-500 hover:text-green-600 py-0.5 pl-3 text-xs">GET occurrence/:key</a>
            <a href="#datasets-ep" class="block text-gray-500 dark:text-gray-500 hover:text-green-600 py-0.5 pl-3 text-xs">GET datasets</a>
            <a href="#response"    class="block text-gray-600 dark:text-gray-400 hover:text-green-600 py-0.5 mt-2">Response format</a>
            <a href="#fields"      class="block text-gray-600 dark:text-gray-400 hover:text-green-600 py-0.5">Fields</a>
            <a href="#status"      class="block text-gray-600 dark:text-gray-400 hover:text-green-600 py-0.5">Verification status</a>
            <a href="#examples"    class="block text-gray-600 dark:text-gray-400 hover:text-green-600 py-0.5">Code examples</a>
        </div>
    </nav>

    {{-- Main content --}}
    <div class="flex-1 min-w-0 space-y-10 pb-16">

        {{-- Endpoints --}}
        <section id="endpoints">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Endpoints</h2>

            {{-- GET occurrences --}}
            <div id="occurrences" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden mb-4">
                <div class="px-5 py-3.5 flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-bold bg-green-100 text-green-700 rounded px-2 py-0.5 font-mono">GET</span>
                    <code class="text-sm font-mono text-gray-800 dark:text-gray-100">/api/v1/occurrences</code>
                    <a href="{{ url('/api/v1/occurrences') }}?per_page=5" target="_blank"
                       class="ml-auto text-xs text-green-600 hover:underline">Try →</a>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-500 mb-4">Paginated list of occurrences. All filters are optional and combinable.</p>
                    <table class="w-full text-sm mb-4">
                        <thead><tr class="text-xs text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="text-left pb-2 font-medium w-48">Parameter</th>
                            <th class="text-left pb-2 font-medium w-28">Type</th>
                            <th class="text-left pb-2 font-medium">Description</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50 text-xs">
                            <tr><td class="py-2 font-mono text-gray-700 dark:text-gray-300">country</td><td class="py-2 text-gray-400">ISO 3166-1 α-2</td><td class="py-2 text-gray-500">Filter by country code — <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">PT</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">ES</code>, …</td></tr>
                            <tr><td class="py-2 font-mono text-gray-700 dark:text-gray-300">dataset_key</td><td class="py-2 text-gray-400">UUID</td><td class="py-2 text-gray-500">Filter by GBIF dataset key</td></tr>
                            <tr><td class="py-2 font-mono text-gray-700 dark:text-gray-300">status</td><td class="py-2 text-gray-400">string</td><td class="py-2 text-gray-500"><code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">validated</code> · <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">has_suggestion</code> · <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">ungeoreferenced</code> · <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">gbif_georeferenced</code></td></tr>
                            <tr><td class="py-2 font-mono text-gray-700 dark:text-gray-300">scientific_name</td><td class="py-2 text-gray-400">string</td><td class="py-2 text-gray-500">Partial match on scientific name</td></tr>
                            <tr><td class="py-2 font-mono text-gray-700 dark:text-gray-300">per_page</td><td class="py-2 text-gray-400">integer</td><td class="py-2 text-gray-500">Records per page — default 100, max 500</td></tr>
                            <tr><td class="py-2 font-mono text-gray-700 dark:text-gray-300">page</td><td class="py-2 text-gray-400">integer</td><td class="py-2 text-gray-500">Page number — default 1</td></tr>
                            <tr><td class="py-2 font-mono text-gray-700 dark:text-gray-300">format</td><td class="py-2 text-gray-400">string</td><td class="py-2 text-gray-500">Set to <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">csv</code> to download all matching records as a UTF-8 CSV file (ignores <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">per_page</code>/<code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">page</code>), or <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">jsonld</code> for JSON-LD output</td></tr>
                        </tbody>
                    </table>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg px-4 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-300">
                        {{ url('/api/v1/occurrences') }}?country=PT&amp;status=validated&amp;per_page=50
                    </div>
                </div>
            </div>

            {{-- GET single --}}
            <div id="single" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden mb-4">
                <div class="px-5 py-3.5 flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-bold bg-green-100 text-green-700 rounded px-2 py-0.5 font-mono">GET</span>
                    <code class="text-sm font-mono text-gray-800 dark:text-gray-100">/api/v1/occurrences/{gbif_key}</code>
                    <a href="{{ url('/api/v1/occurrences') }}/3014169604" target="_blank"
                       class="ml-auto text-xs text-green-600 hover:underline">Try →</a>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-500">Returns a single occurrence by its GBIF numeric key. Accepts <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">Accept: application/ld+json</code> or <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">?format=jsonld</code>.</p>
                </div>
            </div>

            {{-- GET datasets --}}
            <div id="datasets-ep" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-3.5 flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-bold bg-green-100 text-green-700 rounded px-2 py-0.5 font-mono">GET</span>
                    <code class="text-sm font-mono text-gray-800 dark:text-gray-100">/api/v1/datasets</code>
                    <a href="{{ url('/api/v1/datasets') }}" target="_blank"
                       class="ml-auto text-xs text-green-600 hover:underline">Try →</a>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-500 mb-3">Aggregated statistics per GBIF dataset. Optional filters: <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">country</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">institution_code</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">q</code> (free text on institution/collection). Returns <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">dataset_key</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">institution_code</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">collection_code</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">total</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">georeferenced</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">validated</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">ungeoreferenced</code>.</p>
                    <p class="text-sm text-gray-500">See also: <a href="{{ route('datasets') }}" class="text-green-600 hover:underline">Datasets browser</a> with CSV download.</p>
                </div>
            </div>
        </section>

        {{-- Response format --}}
        <section id="response">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Response format</h2>
            <p class="text-sm text-gray-500 mb-4">Coordinates reflect the best available georeference: community-validated → pending suggestion → original GBIF coordinates.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">JSON (default)</p>
                    <pre style="background:#111827;color:#e5e7eb;border-radius:0.75rem;font-size:0.75rem;padding:1rem;overflow-x:auto;line-height:1.625"><code style="color:inherit;background:none">{
  "meta": {
    "total": 48213,
    "per_page": 100,
    "current_page": 1,
    "last_page": 483
  },
  "data": [
    {
      "occurrenceID": "3014169604",
      "scientificName": "Quercus robur L.",
      "verbatimLocality": "Redinha",
      "countryCode": "PT",
      "decimalLatitude": 39.8812,
      "decimalLongitude": -8.5234,
      "coordinateUncertaintyInMeters": 500,
      "georeferenceVerificationStatus":
        "verified by contributor",
      "georef_status": "validated",
      ...
    }
  ]
}</code></pre>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">JSON-LD</p>
                    <p class="text-xs text-gray-400 mb-2">Send <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">Accept: application/ld+json</code> or add <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">?format=jsonld</code></p>
                    <pre style="background:#111827;color:#e5e7eb;border-radius:0.75rem;font-size:0.75rem;padding:1rem;overflow-x:auto;line-height:1.625"><code style="color:inherit;background:none">{
  "@@context": {
    "@@vocab":
      "http://rs.tdwg.org/dwc/terms/",
    ...
  },
  "@@type": "owl:Ontology",
  "totalRecords": 48213,
  "@@graph": [
    {
      "@@type": "dwc:Occurrence",
      "@@id": "https://www.gbif.org/
         occurrence/3014169604",
      "scientificName": "Quercus robur L.",
      "georeferenceVerificationStatus":
        "verified by contributor",
      ...
    }
  ]
}</code></pre>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">CSV</p>
                    <p class="text-xs text-gray-400 mb-2">Add <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">?format=csv</code> — downloads all matching records as a file (no pagination). UTF-8 with BOM (Excel-compatible).</p>
                    <pre style="background:#111827;color:#e5e7eb;border-radius:0.75rem;font-size:0.75rem;padding:1rem;overflow-x:auto;line-height:1.625"><code style="color:inherit;background:none">occurrenceID,datasetKey,institutionCode,collectionCode,catalogNumber,scientificName,countryCode,decimalLatitude,decimalLongitude,coordinateUncertaintyInMeters,georeferencedBy,georeferenceVerificationStatus,...
3014169604,8a863029-...,MHNC,COL,12345,Quercus robur L.,PT,39.8812,-8.5234,500,Jane Smith (https://orcid.org/...),verified by contributor,...
4729103821,8a863029-...,MHNC,COL,67890,Pinus pinaster Aiton,PT,37.1523,-8.9014,1000,,requires verification,...</code></pre>
                </div>
            </div>
        </section>

        {{-- Fields --}}
        <section id="fields">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Fields reference</h2>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500 w-56">Field</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Description</th>
                            <th class="px-4 py-2.5 w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @php
                        $apiFields = [
                            ['occurrenceID', 'GBIF numeric occurrence key', true],
                            ['datasetKey', 'GBIF dataset UUID', true],
                            ['institutionCode', 'Institution that holds the specimen', true],
                            ['collectionCode', 'Collection within the institution', true],
                            ['catalogNumber', 'Catalog number in the collection', true],
                            ['basisOfRecord', 'Nature of the record (PRESERVED_SPECIMEN, etc.)', true],
                            ['scientificName', 'Full scientific name with authorship', true],
                            ['taxonRank', 'Rank of the taxon (SPECIES, GENUS, etc.)', true],
                            ['kingdom / family', 'Higher taxonomy', true],
                            ['eventDate', 'Date of collection — ISO 8601', true],
                            ['recordedBy', 'Collector name(s)', true],
                            ['country / countryCode', 'Country name and ISO 3166-1 alpha-2 code', true],
                            ['stateProvince', 'State or province', true],
                            ['county', 'County or second-level administrative area', true],
                            ['municipality', 'Municipality', true],
                            ['island / islandGroup', 'Island and island group when applicable', true],
                            ['waterBody', 'Water body name when applicable', true],
                            ['verbatimLocality', 'Original locality text from the specimen label', true],
                            ['decimalLatitude / decimalLongitude', 'Best available coordinates', true],
                            ['geodeticDatum', 'Coordinate reference system — WGS84', true],
                            ['coordinateUncertaintyInMeters', 'Radius of positional uncertainty in metres', true],
                            ['georeferencedBy', 'Who georeferenced (contributor or system)', true],
                            ['georeferencedDate', 'Date of georeference — ISO 8601', true],
                            ['georeferenceProtocol', 'Protocol used — Zermoglio et al. 2020', true],
                            ['georeferenceSources', 'Tools used (georeference.it, OpenStreetMap, etc.)', true],
                            ['georeferenceRemarks', 'Free-text notes added by the georeferencer', true],
                            ['georeferenceVerificationStatus', 'DwC verification status (see below)', true],
                            ['georef_status', 'Platform internal status — omitted from JSON-LD', false],
                            ['localityGroupID', 'Locality group identifier — omitted from JSON-LD', false],
                        ];
                        @endphp
                        @foreach($apiFields as $f)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="px-4 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $f[0] }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $f[1] }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($f[2])
                                    <span class="text-green-600 font-medium">DwC</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Verification status --}}
        <section id="status">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700"><code class="font-mono text-base">georeferenceVerificationStatus</code></h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="bg-white dark:bg-gray-800 border border-green-200 dark:border-green-800 rounded-xl p-4">
                    <code class="text-xs font-mono text-green-700 dark:text-green-400">verified by contributor</code>
                    <p class="text-xs text-gray-500 mt-1">Coordinates submitted and approved by community members.</p>
                    <span class="inline-block mt-2 text-xs font-mono bg-green-50 text-green-700 border border-green-200 px-1.5 py-0.5 rounded">validated</span>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                    <code class="text-xs font-mono text-amber-700 dark:text-amber-400">requires verification</code>
                    <p class="text-xs text-gray-500 mt-1">Coordinates exist but not yet community-validated. Applies to records with pending suggestions, conflicting georeferences, or existing GBIF coordinates awaiting confirmation.</p>
                    <span class="inline-block mt-2 text-xs font-mono bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded">has_suggestion</span>
                    <span class="inline-block mt-2 text-xs font-mono bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded">conflicted</span>
                    <span class="inline-block mt-2 text-xs font-mono bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded">gbif_georeferenced</span>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <code class="text-xs font-mono text-gray-500">requires georeference</code>
                    <p class="text-xs text-gray-500 mt-1">No coordinates available — specimen needs georeferencing.</p>
                    <span class="inline-block mt-2 text-xs font-mono bg-gray-100 text-gray-600 border border-gray-200 px-1.5 py-0.5 rounded">ungeoreferenced</span>
                </div>
            </div>
        </section>

        {{-- Code examples --}}
        <section id="examples">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Code examples</h2>
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">curl</p>
                    <pre style="background:#111827;color:#e5e7eb;border-radius:0.75rem;font-size:0.75rem;padding:1rem;overflow-x:auto;line-height:1.625"><code style="color:inherit;background:none"><span class="text-gray-500"># Validated occurrences from Portugal</span>
curl "{{ url('/api/v1/occurrences') }}?country=PT&status=validated"

<span class="text-gray-500"># As JSON-LD</span>
curl -H "Accept: application/ld+json" \
  "{{ url('/api/v1/occurrences') }}?country=PT&status=validated"

<span class="text-gray-500"># Single occurrence</span>
curl "{{ url('/api/v1/occurrences') }}/3014169604"

<span class="text-gray-500"># Dataset list</span>
curl "{{ url('/api/v1/datasets') }}?country=PT"</code></pre>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">JavaScript</p>
                    <pre style="background:#111827;color:#e5e7eb;border-radius:0.75rem;font-size:0.75rem;padding:1rem;overflow-x:auto;line-height:1.625"><code style="color:inherit;background:none">const res = await fetch(
  '{{ url('/api/v1/occurrences') }}?country=PT&status=validated&per_page=500'
);
const { meta, data } = await res.json();
data.forEach(o =>
  console.log(o.scientificName, o.decimalLatitude, o.decimalLongitude)
);</code></pre>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Python — fetch all pages</p>
                    <pre style="background:#111827;color:#e5e7eb;border-radius:0.75rem;font-size:0.75rem;padding:1rem;overflow-x:auto;line-height:1.625"><code style="color:inherit;background:none">import requests

url = "{{ url('/api/v1/occurrences') }}"
params = {"country": "PT", "status": "validated", "per_page": 500}

records, page = [], 1
while True:
    r = requests.get(url, params={**params, "page": page}).json()
    records.extend(r["data"])
    if page >= r["meta"]["last_page"]:
        break
    page += 1

print(f"{len(records)} records")</code></pre>
                </div>
            </div>
        </section>

        {{-- Data note --}}
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-5 py-4 text-sm text-amber-800 dark:text-amber-300">
            <strong>Data provenance</strong> — Records are imported from
            <a href="https://www.gbif.org" target="_blank" class="underline">GBIF</a>.
            Community georeferences follow the
            <a href="https://doi.org/10.35035/e09p-h128" target="_blank" class="underline">Georeferencing Quick Reference Guide (Zermoglio et al. 2020)</a>.
            Please cite both GBIF and georeference.it when using this data.
        </div>

    </div>{{-- /main --}}
</div>{{-- /flex --}}
</div>{{-- /max-w --}}
</x-layouts.app>
