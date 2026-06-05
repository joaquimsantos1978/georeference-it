<x-layouts.georef>
    <div style="position:relative; height:100vh; width:100vw;">
        {{-- Fullscreen map --}}
        <div id="map" style="position:absolute;inset:0;z-index:0;"></div>

        {{-- Top bar overlay --}}
        <div style="position:absolute;top:0;left:0;right:0;z-index:10;" class="flex items-center justify-between px-4 py-3 bg-gradient-to-b from-black/60 to-transparent">
            <span class="text-white font-bold text-lg tracking-tight">georeference.it</span>
            <div class="flex items-center gap-3">
                <select id="country-select" class="text-sm bg-white/20 text-white border border-white/30 rounded-lg px-3 py-1.5 backdrop-blur focus:outline-none">
                    <option value="">{{ __('All countries') }}</option>
                    <option value="PT">Portugal</option>
                    <option value="ES">Spain</option>
                    <option value="GB">United Kingdom</option>
                    <option value="FR">France</option>
                    <option value="DE">Germany</option>
                    <option value="IT">Italy</option>
                    <option value="BR">Brazil</option>
                    <option value="US">United States</option>
                </select>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm text-white/80 hover:text-white">{{ __('Dashboard') }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-white/80 hover:text-white">{{ __('Logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm bg-white/20 text-white border border-white/30 rounded-lg px-3 py-1.5 backdrop-blur hover:bg-white/30">{{ __('Login') }}</a>
                    <a href="{{ route('register') }}" class="text-sm bg-green-600 text-white rounded-lg px-3 py-1.5 hover:bg-green-700">{{ __('Register') }}</a>
                @endauth
            </div>
        </div>

        {{-- Side panel --}}
        <div id="side-panel" style="position:absolute;top:0;right:0;bottom:0;z-index:10;width:380px;transition:transform 0.3s;" class="bg-white dark:bg-gray-900 shadow-2xl flex flex-col">
            {{-- Panel toggle button --}}
            <button id="panel-toggle" style="position:absolute;left:-36px;top:50%;transform:translateY(-50%);z-index:11;"
                    class="bg-white dark:bg-gray-800 shadow-lg rounded-l-lg p-2 text-gray-500 hover:text-green-600">
                <svg id="panel-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            {{-- Panel content --}}
            <div class="flex flex-col h-full overflow-hidden">
                {{-- Occurrence info --}}
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div id="occurrence-loading" class="text-center py-8 text-gray-400 text-sm">
                        {{ __('Loading occurrences...') }}
                    </div>
                    <div id="occurrence-info" class="hidden">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h2 id="taxon-name" class="font-semibold text-gray-900 dark:text-white text-base"></h2>
                                <p id="collection-info" class="text-xs text-gray-500 mt-0.5"></p>
                            </div>
                            <span id="georef-status-badge" class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"></span>
                        </div>
                        <div id="locality-fields" class="space-y-1 text-sm text-gray-600 dark:text-gray-300"></div>
                    </div>
                </div>

                {{-- Occurrences list --}}
                <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Occurrences in this group') }}</span>
                        <span id="occurrence-count" class="text-xs text-gray-400"></span>
                    </div>
                    <div id="occurrences-list" class="space-y-1 max-h-40 overflow-y-auto"></div>
                </div>

                {{-- Existing suggestions --}}
                <div id="existing-suggestions" class="p-3 border-b border-gray-200 dark:border-gray-700 hidden">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Existing suggestions') }}</span>
                    <div id="suggestions-list" class="mt-2 space-y-2"></div>
                </div>

                {{-- Map instructions --}}
                <div id="map-instructions" class="p-3 border-b border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Click on the map to place a point. Drag to adjust. The circle represents coordinate uncertainty.') }}
                    </p>
                </div>

                {{-- Georef form --}}
                <div class="p-4 flex-1 overflow-y-auto">
                    <form id="georef-form" class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Latitude') }}</label>
                            <input type="number" id="lat-input" step="0.0000001" class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="0.0000000">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Longitude') }}</label>
                            <input type="number" id="lng-input" step="0.0000001" class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="0.0000000">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Uncertainty (metres)') }}</label>
                            <input type="number" id="uncertainty-input" min="1" class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="1000">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Remarks') }}</label>
                            <textarea id="remarks-input" rows="2" class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="{{ __('Optional notes...') }}"></textarea>
                        </div>
                        @guest
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Your name (optional)') }}</label>
                            <input type="text" id="anon-name" class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="{{ __('Anonymous') }}">
                        </div>
                        @endguest
                    </form>
                </div>

                {{-- Comments --}}
                <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                    <div id="comments-section">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Discussion') }}</span>
                        <div id="comments-list" class="mt-2 space-y-2 max-h-32 overflow-y-auto"></div>
                        @auth
                        <div class="mt-2 flex gap-2">
                            <input type="text" id="comment-input" class="flex-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Add a comment...') }}">
                            <button id="comment-submit" class="text-xs bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">{{ __('Send') }}</button>
                        </div>
                        @endauth
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                    <button id="skip-btn" class="flex-1 text-sm border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 rounded-lg py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800">
                        {{ __('Skip') }}
                    </button>
                    <button id="submit-btn" class="flex-1 text-sm bg-green-600 text-white rounded-lg py-2.5 hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        {{ __('Submit') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Bottom progress bar --}}
        <div style="position:absolute;bottom:0;left:0;right:380px;z-index:10;" class="bg-black/40 backdrop-blur px-4 py-2 flex items-center gap-4">
            <span id="progress-text" class="text-white text-xs"></span>
            <div class="flex-1 bg-white/20 rounded-full h-1.5">
                <div id="progress-bar" class="bg-green-400 h-1.5 rounded-full transition-all" style="width:0%"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let map, marker, circle, currentGroup = null, panelOpen = true;

        // Init map
        map = L.map('map', { zoomControl: false }).setView([20, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        L.control.zoom({ position: 'bottomleft' }).addTo(map);

        // Panel toggle
        const panel = document.getElementById('side-panel');
        const panelIcon = document.getElementById('panel-icon');
        document.getElementById('panel-toggle').addEventListener('click', () => {
            panelOpen = !panelOpen;
            panel.style.transform = panelOpen ? '' : 'translateX(100%)';
            panelIcon.style.transform = panelOpen ? '' : 'rotate(180deg)';
        });

        // Map click to place marker
        map.on('click', function(e) {
            placeMarker(e.latlng.lat, e.latlng.lng);
        });

        function placeMarker(lat, lng) {
            const uncertainty = parseInt(document.getElementById('uncertainty-input').value) || 1000;
            if (marker) map.removeLayer(marker);
            if (circle) map.removeLayer(circle);
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            circle = L.circle([lat, lng], { radius: uncertainty, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.1, weight: 2 }).addTo(map);
            document.getElementById('lat-input').value = lat.toFixed(7);
            document.getElementById('lng-input').value = lng.toFixed(7);
            document.getElementById('submit-btn').disabled = false;
            marker.on('drag', function(e) {
                const pos = e.target.getLatLng();
                circle.setLatLng(pos);
                document.getElementById('lat-input').value = pos.lat.toFixed(7);
                document.getElementById('lng-input').value = pos.lng.toFixed(7);
            });
        }

        document.getElementById('uncertainty-input').addEventListener('input', function() {
            if (circle) circle.setRadius(parseInt(this.value) || 1000);
        });

        // Load first occurrence group
        loadNextGroup();

        function loadNextGroup() {
            const country = document.getElementById('country-select').value;
            document.getElementById('occurrence-loading').classList.remove('hidden');
            document.getElementById('occurrence-info').classList.add('hidden');
            fetch(`/georef/next?country=${country}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('occurrence-loading').classList.add('hidden');
                if (data.group) {
                    currentGroup = data.group;
                    renderGroup(data.group, data.occurrences, data.suggestions, data.comments);
                } else {
                    document.getElementById('occurrence-info').classList.remove('hidden');
                    document.getElementById('taxon-name').textContent = '{{ __("No occurrences found") }}';
                    document.getElementById('locality-fields').innerHTML = '<p class="text-gray-400 text-sm">{{ __("Try selecting a different country or area.") }}</p>';
                }
            })
            .catch(() => {
                document.getElementById('occurrence-loading').classList.add('hidden');
            });
        }

        function renderGroup(group, occurrences, suggestions, comments) {
            document.getElementById('occurrence-info').classList.remove('hidden');
            const firstOcc = occurrences[0] ?? {};
            document.getElementById('taxon-name').textContent = firstOcc.scientific_name ?? '{{ __("Unknown taxon") }}';
            document.getElementById('collection-info').textContent = [firstOcc.institution_code, firstOcc.collection_code, firstOcc.catalog_number].filter(Boolean).join(' | ');

            const fields = ['verbatim_locality','country','state_province','county','municipality','island','water_body'].filter(f => group[f]);
            document.getElementById('locality-fields').innerHTML = fields.map(f =>
                `<div class="flex gap-2"><span class="text-gray-400 w-32 shrink-0 text-xs">${f.replace(/_/g,' ')}</span><span class="text-gray-700 dark:text-gray-200 text-xs">${group[f]}</span></div>`
            ).join('');

            // Occurrences list
            document.getElementById('occurrence-count').textContent = `${occurrences.length} {{ __('occurrences') }}`;
            document.getElementById('occurrences-list').innerHTML = occurrences.map(o => `
                <label class="flex items-center gap-2 text-xs p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                    <input type="checkbox" class="occurrence-checkbox" value="${o.id}" checked>
                    <span class="flex-1 truncate text-gray-700 dark:text-gray-300">${o.catalog_number ?? o.gbif_occurrence_key}</span>
                    <a href="https://www.gbif.org/occurrence/${o.gbif_occurrence_key}" target="_blank" class="text-green-600 hover:underline shrink-0">GBIF</a>
                    ${o.media && o.media.length > 0 ? `<img src="${o.media[0].identifier}" class="h-6 w-6 rounded object-cover cursor-pointer" onclick="window.open('${o.media[0].identifier}')">` : ''}
                </label>
            `).join('');

            // Suggestions
            if (suggestions && suggestions.length > 0) {
                document.getElementById('existing-suggestions').classList.remove('hidden');
                document.getElementById('suggestions-list').innerHTML = suggestions.map(s => `
                    <div class="text-xs border border-gray-200 dark:border-gray-700 rounded-lg p-2">
                        <div class="flex justify-between">
                            <span class="font-medium">${s.decimal_latitude}, ${s.decimal_longitude}</span>
                            <span class="text-gray-400">±${s.coordinate_uncertainty_m}m</span>
                        </div>
                        <div class="flex justify-between mt-1 text-gray-400">
                            <span>${s.submitted_by}</span>
                            <div class="flex gap-2">
                                <button onclick="validateSuggestion(${s.id}, 'agree')" class="text-green-600 hover:underline">{{ __('Agree') }}</button>
                                <button onclick="validateSuggestion(${s.id}, 'disagree')" class="text-red-500 hover:underline">{{ __('Disagree') }}</button>
                            </div>
                        </div>
                        <div class="mt-1 text-gray-300">
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-full h-1 mt-1">
                                <div class="bg-green-500 h-1 rounded-full" style="width:${Math.min(100, (s.total_points / {{ \App\Models\PlatformSetting::get('validation_threshold', 60) }}) * 100)}%"></div>
                            </div>
                        </div>
                        ${s.decimal_latitude ? `<button onclick="previewSuggestion(${s.decimal_latitude}, ${s.decimal_longitude}, ${s.coordinate_uncertainty_m})" class="mt-1 text-blue-500 hover:underline">{{ __('Preview on map') }}</button>` : ''}
                    </div>
                `).join('');
            } else {
                document.getElementById('existing-suggestions').classList.add('hidden');
            }

            // Comments
            renderComments(comments ?? []);

            // If group has a georef, fly to it
            if (group.gbif_decimal_latitude && group.gbif_decimal_longitude) {
                map.flyTo([group.gbif_decimal_latitude, group.gbif_decimal_longitude], 10);
            }
        }

        function renderComments(comments) {
            document.getElementById('comments-list').innerHTML = comments.map(c => `
                <div class="text-xs">
                    <span class="font-medium text-gray-700 dark:text-gray-300">${c.user_name}</span>
                    <span class="text-gray-400 ml-1">${c.created_at}</span>
                    <p class="text-gray-600 dark:text-gray-400 mt-0.5">${c.body}</p>
                </div>
            `).join('');
        }

        function previewSuggestion(lat, lng, uncertainty) {
            placeMarker(lat, lng);
            map.flyTo([lat, lng], 12);
        }

        function validateSuggestion(suggestionId, vote) {
            fetch(`/georef/validate/${suggestionId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ vote })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) loadNextGroup();
            });
        }

        document.getElementById('submit-btn').addEventListener('click', function() {
            if (!currentGroup) return;
            const excludedIds = Array.from(document.querySelectorAll('.occurrence-checkbox:not(:checked)')).map(c => c.value);
            const payload = {
                locality_group_id: currentGroup.id,
                decimal_latitude: document.getElementById('lat-input').value,
                decimal_longitude: document.getElementById('lng-input').value,
                coordinate_uncertainty_m: document.getElementById('uncertainty-input').value,
                georeference_remarks: document.getElementById('remarks-input').value,
                anon_name: document.getElementById('anon-name')?.value ?? null,
                excluded_occurrence_ids: excludedIds,
            };
            fetch('/georef/submit', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (marker) map.removeLayer(marker);
                    if (circle) map.removeLayer(circle);
                    marker = circle = null;
                    document.getElementById('submit-btn').disabled = true;
                    loadNextGroup();
                }
            });
        });

        document.getElementById('skip-btn').addEventListener('click', loadNextGroup);

        document.getElementById('country-select').addEventListener('change', loadNextGroup);

        // Comment submit
        const commentSubmit = document.getElementById('comment-submit');
        if (commentSubmit) {
            commentSubmit.addEventListener('click', function() {
                const body = document.getElementById('comment-input').value.trim();
                if (!body || !currentGroup) return;
                fetch(`/georef/comment`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ locality_group_id: currentGroup.id, body })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('comment-input').value = '';
                        renderComments(data.comments);
                    }
                });
            });
        }
    </script>
    @endpush
</x-layouts.georef>
