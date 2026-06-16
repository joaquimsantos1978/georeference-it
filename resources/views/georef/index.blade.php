<x-layouts.georef>
    <div id="georef-wrap" style="position:relative; height:100%; width:100%; display:flex; flex-direction:row;">

        {{-- LEFT PANEL: focus area + locality + occurrences --}}
        <div id="left-panel" style="width:260px; flex-shrink:0; z-index:10; display:flex; flex-direction:column; height:100%; overflow:hidden; border-right:1px solid #e5e7eb;"
            class="bg-white dark:bg-gray-900">

            {{-- Focus area --}}
            <div style="flex-shrink:0; border-bottom:1px solid #e5e7eb; padding:8px 12px;">
                <label style="display:block;font-size:10px;font-weight:500;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">{{ __('Focus area') }}</label>
                <div style="display:flex;align-items:center;gap:6px;">
                    <input type="text" id="focus-input" placeholder="{{ __('e.g. Redinha, Serra da Estrela...') }}"
                        class="flex-1 text-xs border border-gray-200 dark:border-gray-700 rounded px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
                    <button id="focus-clear" title="{{ __('Clear focus') }}" style="display:none;font-size:14px;background:none;border:none;cursor:pointer;color:#9ca3af;line-height:1;">×</button>
                    <span id="focus-hint" style="font-size:10px;color:#9ca3af;white-space:nowrap;display:none;"></span>
                </div>
                {{-- hidden country select kept for auto-detect --}}
                <select id="country-select" style="display:none;" class="text-xs border border-gray-200 dark:border-gray-700 rounded px-2 py-1.5 bg-white dark:bg-gray-800">
    <option value="">{{ __('All countries') }}</option>
    <option value="AF">Afghanistan</option>
    <option value="AL">Albania</option>
    <option value="DZ">Algeria</option>
    <option value="AD">Andorra</option>
    <option value="AO">Angola</option>
    <option value="AG">Antigua and Barbuda</option>
    <option value="AR">Argentina</option>
    <option value="AM">Armenia</option>
    <option value="AU">Australia</option>
    <option value="AT">Austria</option>
    <option value="AZ">Azerbaijan</option>
    <option value="BS">Bahamas</option>
    <option value="BH">Bahrain</option>
    <option value="BD">Bangladesh</option>
    <option value="BB">Barbados</option>
    <option value="BY">Belarus</option>
    <option value="BE">Belgium</option>
    <option value="BZ">Belize</option>
    <option value="BJ">Benin</option>
    <option value="BT">Bhutan</option>
    <option value="BO">Bolivia</option>
    <option value="BA">Bosnia and Herzegovina</option>
    <option value="BW">Botswana</option>
    <option value="BR">Brazil</option>
    <option value="BN">Brunei</option>
    <option value="BG">Bulgaria</option>
    <option value="BF">Burkina Faso</option>
    <option value="BI">Burundi</option>
    <option value="CV">Cape Verde</option>
    <option value="KH">Cambodia</option>
    <option value="CM">Cameroon</option>
    <option value="CA">Canada</option>
    <option value="CF">Central African Republic</option>
    <option value="TD">Chad</option>
    <option value="CL">Chile</option>
    <option value="CN">China</option>
    <option value="CO">Colombia</option>
    <option value="KM">Comoros</option>
    <option value="CG">Congo</option>
    <option value="CD">Congo (DR)</option>
    <option value="CR">Costa Rica</option>
    <option value="HR">Croatia</option>
    <option value="CU">Cuba</option>
    <option value="CY">Cyprus</option>
    <option value="CZ">Czech Republic</option>
    <option value="DK">Denmark</option>
    <option value="DJ">Djibouti</option>
    <option value="DM">Dominica</option>
    <option value="DO">Dominican Republic</option>
    <option value="EC">Ecuador</option>
    <option value="EG">Egypt</option>
    <option value="SV">El Salvador</option>
    <option value="GQ">Equatorial Guinea</option>
    <option value="ER">Eritrea</option>
    <option value="EE">Estonia</option>
    <option value="SZ">Eswatini</option>
    <option value="ET">Ethiopia</option>
    <option value="FJ">Fiji</option>
    <option value="FI">Finland</option>
    <option value="FR">France</option>
    <option value="GA">Gabon</option>
    <option value="GM">Gambia</option>
    <option value="GE">Georgia</option>
    <option value="DE">Germany</option>
    <option value="GH">Ghana</option>
    <option value="GR">Greece</option>
    <option value="GD">Grenada</option>
    <option value="GT">Guatemala</option>
    <option value="GN">Guinea</option>
    <option value="GW">Guinea-Bissau</option>
    <option value="GY">Guyana</option>
    <option value="HT">Haiti</option>
    <option value="HN">Honduras</option>
    <option value="HU">Hungary</option>
    <option value="IS">Iceland</option>
    <option value="IN">India</option>
    <option value="ID">Indonesia</option>
    <option value="IR">Iran</option>
    <option value="IQ">Iraq</option>
    <option value="IE">Ireland</option>
    <option value="IL">Israel</option>
    <option value="IT">Italy</option>
    <option value="JM">Jamaica</option>
    <option value="JP">Japan</option>
    <option value="JO">Jordan</option>
    <option value="KZ">Kazakhstan</option>
    <option value="KE">Kenya</option>
    <option value="KI">Kiribati</option>
    <option value="KW">Kuwait</option>
    <option value="KG">Kyrgyzstan</option>
    <option value="LA">Laos</option>
    <option value="LV">Latvia</option>
    <option value="LB">Lebanon</option>
    <option value="LS">Lesotho</option>
    <option value="LR">Liberia</option>
    <option value="LY">Libya</option>
    <option value="LI">Liechtenstein</option>
    <option value="LT">Lithuania</option>
    <option value="LU">Luxembourg</option>
    <option value="MG">Madagascar</option>
    <option value="MW">Malawi</option>
    <option value="MY">Malaysia</option>
    <option value="MV">Maldives</option>
    <option value="ML">Mali</option>
    <option value="MT">Malta</option>
    <option value="MH">Marshall Islands</option>
    <option value="MR">Mauritania</option>
    <option value="MU">Mauritius</option>
    <option value="MX">Mexico</option>
    <option value="FM">Micronesia</option>
    <option value="MD">Moldova</option>
    <option value="MC">Monaco</option>
    <option value="MN">Mongolia</option>
    <option value="ME">Montenegro</option>
    <option value="MA">Morocco</option>
    <option value="MZ">Mozambique</option>
    <option value="MM">Myanmar</option>
    <option value="NA">Namibia</option>
    <option value="NR">Nauru</option>
    <option value="NP">Nepal</option>
    <option value="NL">Netherlands</option>
    <option value="NZ">New Zealand</option>
    <option value="NI">Nicaragua</option>
    <option value="NE">Niger</option>
    <option value="NG">Nigeria</option>
    <option value="NO">Norway</option>
    <option value="OM">Oman</option>
    <option value="PK">Pakistan</option>
    <option value="PW">Palau</option>
    <option value="PA">Panama</option>
    <option value="PG">Papua New Guinea</option>
    <option value="PY">Paraguay</option>
    <option value="PE">Peru</option>
    <option value="PH">Philippines</option>
    <option value="PL">Poland</option>
    <option value="PT">Portugal</option>
    <option value="QA">Qatar</option>
    <option value="RO">Romania</option>
    <option value="RU">Russia</option>
    <option value="RW">Rwanda</option>
    <option value="KN">Saint Kitts and Nevis</option>
    <option value="LC">Saint Lucia</option>
    <option value="VC">Saint Vincent and the Grenadines</option>
    <option value="WS">Samoa</option>
    <option value="SM">San Marino</option>
    <option value="ST">Sao Tome and Principe</option>
    <option value="SA">Saudi Arabia</option>
    <option value="SN">Senegal</option>
    <option value="RS">Serbia</option>
    <option value="SC">Seychelles</option>
    <option value="SL">Sierra Leone</option>
    <option value="SG">Singapore</option>
    <option value="SK">Slovakia</option>
    <option value="SI">Slovenia</option>
    <option value="SB">Solomon Islands</option>
    <option value="SO">Somalia</option>
    <option value="ZA">South Africa</option>
    <option value="SS">South Sudan</option>
    <option value="ES">Spain</option>
    <option value="LK">Sri Lanka</option>
    <option value="SD">Sudan</option>
    <option value="SR">Suriname</option>
    <option value="SE">Sweden</option>
    <option value="CH">Switzerland</option>
    <option value="SY">Syria</option>
    <option value="TW">Taiwan</option>
    <option value="TJ">Tajikistan</option>
    <option value="TZ">Tanzania</option>
    <option value="TH">Thailand</option>
    <option value="TL">Timor-Leste</option>
    <option value="TG">Togo</option>
    <option value="TO">Tonga</option>
    <option value="TT">Trinidad and Tobago</option>
    <option value="TN">Tunisia</option>
    <option value="TR">Turkey</option>
    <option value="TM">Turkmenistan</option>
    <option value="TV">Tuvalu</option>
    <option value="UG">Uganda</option>
    <option value="UA">Ukraine</option>
    <option value="AE">United Arab Emirates</option>
    <option value="GB">United Kingdom</option>
    <option value="US">United States</option>
    <option value="UY">Uruguay</option>
    <option value="UZ">Uzbekistan</option>
    <option value="VU">Vanuatu</option>
    <option value="VE">Venezuela</option>
    <option value="VN">Vietnam</option>
    <option value="YE">Yemen</option>
    <option value="ZM">Zambia</option>
    <option value="ZW">Zimbabwe</option>
                </select>
                <span id="country-sync-status" style="font-size:10px;color:#9ca3af;display:none;"></span>
            </div>

            {{-- Locality + Nominatim --}}
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <div id="occurrence-loading" class="text-center py-4 text-gray-400 text-xs">{{ __('Loading occurrences...') }}</div>
                <div id="occurrence-info" class="hidden">
                    <div style="display:flex;align-items:flex-start;gap:4px;margin-bottom:6px;">
                        <div id="locality-fields" class="space-y-0.5 flex-1"></div>
                        <button id="share-btn" title="{{ __('Copy link to this locality') }}"
                            style="flex-shrink:0;padding:3px 7px;border:1px solid #e5e7eb;border-radius:4px;background:white;cursor:pointer;color:#16a34a;font-size:11px;margin-top:1px;"
                            onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='white'">🔗</button>
                    </div>
                    <div class="flex gap-1 mt-1">
                        <input type="text" id="nominatim-input" class="flex-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Search place to position on map...') }}">
                        <button id="nominatim-btn" class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1.5 rounded-lg hover:bg-gray-200 shrink-0">🔍</button>
                    </div>
                    <div id="nominatim-results" class="mt-1 space-y-1 max-h-32 overflow-y-auto"></div>
                </div>
            </div>

            {{-- Occurrences list (takes remaining space) --}}
            <div class="p-3" style="flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-shrink:0;margin-bottom:4px;">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Occurrences') }}</span>
                    <span id="occurrence-count" class="text-xs text-gray-400"></span>
                </div>
                <p class="text-xs text-gray-400 italic mb-1" style="flex-shrink:0;">{{ __('Check specimens to include in this georeference:') }}</p>
                <div id="occ-select-controls" class="hidden mb-1" style="flex-shrink:0;">
                    <div class="flex flex-wrap gap-1">
                        <button onclick="occSelectAll(true)"  class="text-xs px-2 py-0.5 rounded-full border border-gray-300 hover:bg-gray-100 text-gray-600">{{ __('All') }}</button>
                        <button onclick="occSelectAll(false)" class="text-xs px-2 py-0.5 rounded-full border border-gray-300 hover:bg-gray-100 text-gray-600">{{ __('None') }}</button>
                        <button onclick="occSelectByStatus(true)"  class="text-xs px-2 py-0.5 rounded-full border border-gray-300 hover:bg-gray-100 text-gray-600">{{ __('Georef') }}</button>
                        <button onclick="occSelectByStatus(false)" class="text-xs px-2 py-0.5 rounded-full border border-gray-300 hover:bg-gray-100 text-gray-600">{{ __('Ungeoref') }}</button>
                    </div>
                </div>
                <div id="occurrences-list" class="space-y-0.5 overflow-y-auto" style="flex:1;min-height:0;"></div>
            </div>
        </div>

        {{-- MAP --}}
        <div id="map" style="flex:1; position:relative; z-index:0;"></div>

        {{-- Floating history button (positioned over the map) --}}
        <div style="position:absolute;top:12px;left:272px;z-index:20;">
            <div style="position:relative;display:inline-flex;align-items:center;background:white;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                <button id="hist-prev" disabled title="{{ __('Previous') }}"
                    style="padding:6px 10px;background:none;border:none;border-right:1px solid #e5e7eb;cursor:pointer;font-size:14px;color:#d1d5db;border-radius:8px 0 0 8px;line-height:1;">←</button>
                <button id="hist-float-btn" title="{{ __('Session history') }}"
                    style="padding:6px 8px;background:none;border:none;border-right:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;gap:4px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;color:#6b7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span id="hist-counter" style="color:#9ca3af;font-size:11px;min-width:28px;text-align:center;">0/0</span>
                </button>
                <button id="hist-next" disabled title="{{ __('Next') }}"
                    style="padding:6px 10px;background:none;border:none;cursor:pointer;font-size:14px;color:#d1d5db;border-radius:0 8px 8px 0;line-height:1;">→</button>
                {{-- History dropdown --}}
                <div id="hist-list" style="display:none;position:absolute;top:calc(100% + 4px);left:0;min-width:300px;max-width:380px;max-height:300px;overflow-y:auto;background:white;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.15);z-index:30;"></div>
            </div>
        </div>

        {{-- Draggable image viewer --}}
        <div id="img-viewer" style="display:none; position:absolute; top:60px; left:284px; z-index:25; width:360px; height:320px; min-width:200px; min-height:150px;"
            class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-300 dark:border-gray-600 flex flex-col overflow-hidden">
            <div id="img-viewer-bar" class="flex items-center justify-between px-3 py-1.5 bg-gray-100 dark:bg-gray-800 cursor-move select-none shrink-0 border-b border-gray-200 dark:border-gray-700">
                <span id="img-viewer-title" class="text-xs text-gray-500 truncate flex-1 mr-2"></span>
                <div class="flex items-center gap-2 shrink-0">
                    <a id="img-viewer-link" href="#" target="_blank" class="text-xs text-green-600 hover:underline">{{ __('Full size') }}</a>
                    <button onclick="closeImgViewer()" class="text-gray-400 hover:text-gray-600 text-sm leading-none ml-1">✕</button>
                </div>
            </div>
            <div class="flex items-center gap-1 px-2 py-1 bg-gray-50 dark:bg-gray-800 shrink-0 border-b border-gray-100 dark:border-gray-700">
                <button onclick="zoomImg(-0.25)" class="text-xs bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-2 py-0.5 hover:bg-gray-50">−</button>
                <button onclick="zoomImg(0.25)" class="text-xs bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-2 py-0.5 hover:bg-gray-50">+</button>
                <button onclick="resetImgZoom()" class="text-xs bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-2 py-0.5 hover:bg-gray-50">1:1</button>
                <span id="img-zoom-label" class="text-xs text-gray-400 ml-1">100%</span>
                <span class="text-xs text-gray-300 ml-auto">{{ __('scroll to zoom · drag to pan') }}</span>
            </div>
            <div id="img-pan-area" class="flex-1 overflow-hidden relative cursor-grab" style="background:#f3f4f6;">
                <img id="img-viewer-img" src="" alt="" style="position:absolute; transform-origin:0 0; cursor:grab; user-select:none;" draggable="false">
            </div>
            <div id="img-resize-handle" style="position:absolute; bottom:0; right:0; width:16px; height:16px; cursor:se-resize; z-index:10;">
                <svg viewBox="0 0 16 16" style="width:16px;height:16px;opacity:0.4;">
                    <line x1="4" y1="16" x2="16" y2="4" stroke="#6b7280" stroke-width="1.5"/>
                    <line x1="8" y1="16" x2="16" y2="8" stroke="#6b7280" stroke-width="1.5"/>
                    <line x1="12" y1="16" x2="16" y2="12" stroke="#6b7280" stroke-width="1.5"/>
                </svg>
            </div>
        </div>

        {{-- RIGHT PANEL: suggestions + georef form + discussion + buttons --}}
        <div id="right-panel" style="width:320px; flex-shrink:0; z-index:10; display:flex; flex-direction:column; height:100%; overflow:hidden; border-left:1px solid #e5e7eb; position:relative;"
            class="bg-white dark:bg-gray-900 shadow-2xl">

            {{-- Loading overlay --}}
            <div id="panel-overlay" style="display:none;position:absolute;inset:0;background:rgba(255,255,255,0.75);z-index:50;align-items:center;justify-content:center;backdrop-filter:blur(1px);">
                <svg style="width:32px;height:32px;animation:spin 0.8s linear infinite;color:#16a34a;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path style="opacity:0.85" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <style>@keyframes spin{to{transform:rotate(360deg)}}</style>

            {{-- Existing suggestions (always shown) --}}
            <div id="existing-suggestions" style="flex-shrink:0;border-bottom:1px solid #e5e7eb;display:flex;flex-direction:column;max-height:40%;min-height:0;">
                <div class="px-3 pt-3 pb-1" style="flex-shrink:0;">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Existing suggestions') }}</span>
                </div>
                <div id="suggestions-list" class="px-3 pb-2 space-y-2 overflow-y-auto" style="flex:1;min-height:0;">
                    <p id="suggestions-empty" style="font-size:11px;color:#9ca3af;font-style:italic;padding:4px 0">{{ __('No suggestions yet for this group.') }}</p>
                </div>
            </div>

            {{-- Georef form (takes remaining space) --}}
            <div class="p-4 overflow-y-auto" style="flex:1;min-height:0;">
                <p class="text-xs text-gray-400 mb-4 mt-1">{{ __('Click on the map to place a point. Drag to adjust.') }}</p>
                <form id="georef-form" class="space-y-2">
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Latitude') }}</label>
                            <input type="number" id="lat-input" step="0.0000001" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="0.0000000">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Longitude') }}</label>
                            <input type="number" id="lng-input" step="0.0000001" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="0.0000000">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Uncertainty') }} <span id="uncertainty-display" class="text-green-600 font-semibold"></span></label>
                        <input type="number" id="uncertainty-input" min="1" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="1000">
                        <input type="range" id="uncertainty-slider" min="100" max="500000" step="1000" value="1000" class="w-full mt-1 accent-green-600">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Remarks') }}</label>
                        <textarea id="remarks-input" rows="2" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Optional notes...') }}"></textarea>
                    </div>
                    @guest
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Your name (optional)') }}</label>
                        <input type="text" id="anon-name" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Anonymous') }}">
                    </div>
                    @endguest
                </form>
            </div>

            {{-- Discussion --}}
            <div class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Discussion') }}</span>
                <div id="comments-list" class="mt-1 space-y-1 max-h-20 overflow-y-auto"></div>
                @auth
                <div class="mt-2 flex gap-1">
                    <input type="text" id="comment-input" class="flex-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Add a comment...') }}">
                    <button id="comment-submit" class="text-xs bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-200">{{ __('Send') }}</button>
                </div>
                @else
                <p class="mt-2 text-xs text-gray-400 italic">
                    {{ __('Only authenticated users can join the discussion.') }}
                    <a href="{{ route('login') }}" class="text-green-600 hover:underline">{{ __('Login') }}</a>
                    {{ __('or') }}
                    <a href="{{ route('register') }}" class="text-green-600 hover:underline">{{ __('register') }}</a>.
                </p>
                @endauth
            </div>

            {{-- Action buttons --}}
            <div class="p-3 border-t border-gray-200 dark:border-gray-700 flex gap-2 shrink-0">
                <button id="skip-btn" class="flex-1 text-sm border border-gray-200 dark:border-gray-700 text-gray-600 rounded-lg py-2 hover:bg-gray-50">{{ __('Skip') }}</button>
                <button id="submit-btn" class="flex-1 text-sm bg-green-600 text-white rounded-lg py-2 hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>{{ __('Submit') }}</button>
            </div>
        </div>

        {{-- Hidden stubs for dead JS references --}}
        <input type="text" id="area-search" style="display:none">
        <button id="area-search-btn" style="display:none"></button>
        <span id="area-hint" style="display:none"></span>

        {{-- Mobile bottom bar --}}
        <div id="mobile-tabs" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:50;background:white;border-top:1px solid #e5e7eb;height:52px;"
            class="dark:bg-gray-900 dark:border-gray-700">
            <div style="display:flex;align-items:center;height:100%;padding:0 8px;gap:8px;">

                {{-- Scrolling locality text --}}
                <div style="flex:1;min-width:0;overflow:hidden;height:100%;display:flex;align-items:center;">
                    <div id="mob-locality-track" style="white-space:nowrap;font-size:11px;color:#6b7280;overflow:hidden;width:100%;">
                        <span id="mob-locality-text" style="display:inline-block;">—</span>
                    </div>
                </div>

                {{-- Location + Georef toggle buttons, right-aligned with separator --}}
                <div style="flex-shrink:0;display:flex;align-items:stretch;border-left:1px solid #e5e7eb;margin-left:4px;" class="dark:border-gray-700">
                    <button id="mob-btn-info" onclick="mobileToggle('info')"
                        style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;border:none;background:none;font-size:9px;font-weight:600;color:#6b7280;cursor:pointer;padding:4px 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Location
                    </button>
                    <div style="width:1px;background:#e5e7eb;margin:8px 0;" class="dark:bg-gray-700"></div>
                    <button id="mob-btn-suggest" onclick="mobileToggle('suggest')"
                        style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;border:none;background:none;font-size:9px;font-weight:600;color:#6b7280;cursor:pointer;padding:4px 10px;position:relative;">
                        <span style="position:relative;display:inline-block;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span id="mob-sugg-badge" style="display:none;position:absolute;top:-5px;right:-6px;background:#ef4444;color:white;font-size:8px;font-weight:700;line-height:1;padding:2px 4px;border-radius:999px;min-width:14px;text-align:center;"></span>
                        </span>
                        Georef
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    @keyframes mob-marquee {
        0%   { transform: translateX(0); }
        10%  { transform: translateX(0); }
        90%  { transform: translateX(var(--mob-scroll-dist, 0px)); }
        100% { transform: translateX(var(--mob-scroll-dist, 0px)); }
    }
    .mob-marquee-anim {
        animation: mob-marquee 8s ease-in-out infinite alternate;
    }

    @media (max-width: 768px) {
        #mobile-tabs { display:flex !important; }

        #georef-wrap { flex-direction: column !important; padding-bottom: 52px; }

        #left-panel {
            position: fixed !important;
            bottom: 52px !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            height: 65vh !important;
            max-height: 65vh !important;
            border-right: none !important;
            border-top: 2px solid #e5e7eb !important;
            border-radius: 16px 16px 0 0 !important;
            z-index: 40 !important;
            transform: translateY(100%) !important;
            transition: transform 0.3s cubic-bezier(.4,0,.2,1) !important;
            box-shadow: 0 -4px 24px rgba(0,0,0,0.12) !important;
        }
        #left-panel.mob-open { transform: translateY(0) !important; }
        #left-panel::before {
            content: '';
            display: block;
            width: 36px; height: 4px;
            background: #d1d5db;
            border-radius: 2px;
            margin: 8px auto 4px;
            flex-shrink: 0;
        }

        #right-panel {
            position: fixed !important;
            bottom: 52px !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            height: 75vh !important;
            max-height: 75vh !important;
            border-left: none !important;
            border-top: 2px solid #e5e7eb !important;
            border-radius: 16px 16px 0 0 !important;
            z-index: 40 !important;
            transform: translateY(100%) !important;
            transition: transform 0.3s cubic-bezier(.4,0,.2,1) !important;
            box-shadow: 0 -4px 24px rgba(0,0,0,0.12) !important;
        }
        #right-panel.mob-open { transform: translateY(0) !important; }
        #right-panel::before {
            content: '';
            display: block;
            width: 36px; height: 4px;
            background: #d1d5db;
            border-radius: 2px;
            margin: 8px auto 4px;
            flex-shrink: 0;
        }

        #map {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 52px !important;
            width: 100% !important;
            height: auto !important;
            z-index: 1 !important;
        }

        /* History button — reposition for mobile */
        #georef-wrap > div[style*="left:272px"] {
            left: 8px !important;
            top: 8px !important;
        }
    }
    </style>

    @push('scripts')
    <script>
    const APP_URL   = document.querySelector('meta[name="app-url"]').getAttribute('content');
    const CSRF      = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const THRESHOLD = {{ \App\Models\PlatformSetting::get('validation_threshold', 60) }};
    const IS_AUTH   = {{ auth()->check() ? 'true' : 'false' }};
    const TXT = {
        agree:        "{{ __('Agree') }}",
        disagree:     "{{ __('Disagree') }}",
        loginToVal:   "{{ __('Login to validate') }}",
        previewMap:   "{{ __('Preview on map') }}",
        searching:    "{{ __('Searching...') }}",
        noResults:    "{{ __('No results found.') }}",
        searchFailed: "{{ __('Search failed.') }}",
        noOcc:        "{{ __('No occurrences found. Try a different country.') }}",
        occurrences:  "{{ __('occurrences') }}",
    };

    // Session history — restored from localStorage on every page load
var sessionHistory = JSON.parse(localStorage.getItem('georef_history') || '[]');
var historyIndex   = parseInt(localStorage.getItem('georef_index') ?? '-1');
if (isNaN(historyIndex) || historyIndex >= sessionHistory.length) historyIndex = sessionHistory.length - 1;

    // ── Map ───────────────────────────────────────────────────────────────────
    let map, marker, circle, radiusHandle, currentGroup = null;
    map = L.map('map', { zoomControl: false }).setView([39.5, -8.0], 6);
    const osm           = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors', maxZoom: 19 });
    const esriSat       = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    const esriLabels    = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, pane: 'overlayPane' });
    const esriSatLabels = L.layerGroup([esriSat, esriLabels]);
    const esriStreet    = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    const esriTopo      = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    osm.addTo(map);
    L.control.layers({ 'OpenStreetMap': osm, 'ESRI Satellite': esriSat, 'ESRI Satellite + Labels': esriSatLabels, 'ESRI Street Map': esriStreet, 'ESRI Topo': esriTopo }, {}, { position: 'bottomleft' }).addTo(map);
    L.control.zoom({ position: 'bottomleft' }).addTo(map);

    // Measure button as a Leaflet control (bottomleft, above layers)
    const MeasureControl = L.Control.extend({
        options: { position: 'bottomleft' },
        onAdd() {
            const btn = L.DomUtil.create('button', '');
            btn.id = 'measure-btn';
            btn.title = '{{ __("Measure distance") }}';
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="10" rx="1"/><line x1="6" y1="7" x2="6" y2="12"/><line x1="10" y1="7" x2="10" y2="11"/><line x1="14" y1="7" x2="14" y2="12"/><line x1="18" y1="7" x2="18" y2="11"/></svg>';
            btn.style.cssText = 'display:flex;align-items:center;padding:5px 7px;background:white;border:2px solid rgba(0,0,0,0.2);border-radius:4px;cursor:pointer;color:#374151;font-family:inherit;';
            L.DomEvent.on(btn, 'click', L.DomEvent.stopPropagation);
            L.DomEvent.on(btn, 'click', toggleMeasure);
            return btn;
        }
    });
    new MeasureControl().addTo(map);
    map.on('click', e => { if (!measureMode) placeMarker(e.latlng.lat, e.latlng.lng); });

    // ── Radius handle helpers ─────────────────────────────────────────────────
    function getRadiusHandleLatLng(centerLatLng, radiusM) {
        // Place handle due East of centre
        const earthR = 6378137;
        const dLng = (radiusM / (earthR * Math.cos(centerLatLng.lat * Math.PI / 180))) * (180 / Math.PI);
        return L.latLng(centerLatLng.lat, centerLatLng.lng + dLng);
    }

    function updateRadiusHandle() {
        if (!circle || !radiusHandle) return;
        radiusHandle.setLatLng(getRadiusHandleLatLng(circle.getLatLng(), circle.getRadius()));
    }

    function setUncertainty(v) {
        v = Math.max(1, Math.round(v));
        document.getElementById('uncertainty-input').value = v;
        document.getElementById('uncertainty-slider').value = Math.min(v, 500000);
        document.getElementById('uncertainty-display').textContent = v.toLocaleString() + 'm';
        if (circle) { circle.setRadius(v); updateRadiusHandle(); }
    }

    function placeMarker(lat, lng) {
        const unc = parseInt(document.getElementById('uncertainty-input').value) || 1000;
        if (marker) { map.removeLayer(marker); marker = null; }
        if (circle) { map.removeLayer(circle); circle = null; }
        if (radiusHandle) { map.removeLayer(radiusHandle); radiusHandle = null; }

        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        circle = L.circle([lat, lng], { radius: unc, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.15, weight: 2 }).addTo(map);

        // Radius drag handle — white circle on the East edge of the circle
        const handleIcon = L.divIcon({
            className: '',
            html: '<div style="width:14px;height:14px;border-radius:50%;background:white;border:2px solid #16a34a;box-shadow:0 1px 4px rgba(0,0,0,0.3);cursor:ew-resize;"></div>',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
        });
        radiusHandle = L.marker(getRadiusHandleLatLng(L.latLng(lat, lng), unc), {
            icon: handleIcon,
            draggable: true,
            zIndexOffset: 1000,
        }).addTo(map);

        radiusHandle.on('drag', e => {
            const center = circle.getLatLng();
            const handle = e.target.getLatLng();
            const newRadius = Math.round(center.distanceTo(handle));
            circle.setRadius(newRadius);
            document.getElementById('uncertainty-input').value = newRadius;
            document.getElementById('uncertainty-slider').value = Math.min(newRadius, 500000);
            document.getElementById('uncertainty-display').textContent = newRadius.toLocaleString() + 'm';
        });
        radiusHandle.on('dragend', () => updateRadiusHandle()); // snap handle to East

        document.getElementById('lat-input').value = lat.toFixed(7);
        document.getElementById('lng-input').value = lng.toFixed(7);
        document.getElementById('uncertainty-display').textContent = unc.toLocaleString() + 'm';
        document.getElementById('uncertainty-slider').value = Math.min(unc, 500000);
        document.getElementById('submit-btn').disabled = false;

        marker.on('drag', e => {
            const p = e.target.getLatLng();
            circle.setLatLng(p);
            updateRadiusHandle();
            document.getElementById('lat-input').value = p.lat.toFixed(7);
            document.getElementById('lng-input').value = p.lng.toFixed(7);
        });
    }

    function syncMarkerFromInputs() {
        var lat = parseFloat(document.getElementById('lat-input').value);
        var lng = parseFloat(document.getElementById('lng-input').value);
        if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            placeMarker(lat, lng);
            map.flyTo([lat, lng], map.getZoom() < 8 ? 8 : map.getZoom());
        }
    }
    ['lat-input','lng-input'].forEach(function(id) {
        var el = document.getElementById(id);
        el.addEventListener('blur', syncMarkerFromInputs);
        el.addEventListener('keydown', function(e) { if (e.key === 'Enter') syncMarkerFromInputs(); });
    });

    document.getElementById('uncertainty-input').addEventListener('input', function() {
        setUncertainty(parseInt(this.value) || 1000);
    });
    document.getElementById('uncertainty-slider').addEventListener('input', function() {
        setUncertainty(parseInt(this.value));
    });

    // ── Distance measurement tool ─────────────────────────────────────────────
    let measureMode = false, measurePoints = [], measureLines = [], measureMarkers = [], measureLabel = null;

    function formatDist(m) {
        return m >= 1000 ? (m/1000).toFixed(2) + ' km' : Math.round(m) + ' m';
    }

    function updateMeasureLabel() {
        if (!measurePoints.length) return;
        let total = 0;
        for (let i = 1; i < measurePoints.length; i++) total += measurePoints[i-1].distanceTo(measurePoints[i]);
        const last = measurePoints[measurePoints.length - 1];
        if (measureLabel) map.removeLayer(measureLabel);
        measureLabel = L.tooltip({ permanent: true, direction: 'top', className: 'measure-tooltip' })
            .setLatLng(last)
            .setContent('<b>' + formatDist(total) + '</b>')
            .addTo(map);
    }

    function clearMeasure() {
        measurePoints = [];
        measureLines.forEach(l => map.removeLayer(l)); measureLines = [];
        measureMarkers.forEach(m => map.removeLayer(m)); measureMarkers = [];
        if (measureLabel) { map.removeLayer(measureLabel); measureLabel = null; }
    }

    function toggleMeasure() {
        measureMode = !measureMode;
        const btn = document.getElementById('measure-btn');
        if (measureMode) {
            btn.style.background = '#16a34a';
            btn.style.color = 'white';
            map.getContainer().style.cursor = 'crosshair';
            clearMeasure();
        } else {
            btn.style.background = 'white';
            btn.style.color = '#374151';
            map.getContainer().style.cursor = '';
            clearMeasure();
        }
    }

    map.on('click', e => {
        if (!measureMode) return;
        const latlng = e.latlng;
        // Small dot at each click
        const dot = L.circleMarker(latlng, { radius: 4, color: '#f59e0b', fillColor: '#f59e0b', fillOpacity: 1, weight: 2 }).addTo(map);
        measureMarkers.push(dot);
        if (measurePoints.length > 0) {
            const line = L.polyline([measurePoints[measurePoints.length-1], latlng], { color: '#f59e0b', weight: 2, dashArray: '6' }).addTo(map);
            measureLines.push(line);
        }
        measurePoints.push(latlng);
        updateMeasureLabel();
    });

    // ── Image viewer ──────────────────────────────────────────────────────────
    let imgScale = 1, imgX = 0, imgY = 0, isPanning = false, panStartX, panStartY;
    const imgViewer = document.getElementById('img-viewer');
    const imgEl     = document.getElementById('img-viewer-img');
    const panArea   = document.getElementById('img-pan-area');
    const zoomLabel = document.getElementById('img-zoom-label');

    function applyImgTransform() { imgEl.style.transform = 'translate('+imgX+'px,'+imgY+'px) scale('+imgScale+')'; zoomLabel.textContent = Math.round(imgScale*100)+'%'; }
    function resetImgZoom() { imgScale=1; imgX=0; imgY=0; applyImgTransform(); }
    function zoomImg(d) { imgScale=Math.max(0.2,Math.min(8,imgScale+d)); applyImgTransform(); }

    panArea.addEventListener('mousedown', e => { if(e.button!==0)return; isPanning=true; panStartX=e.clientX-imgX; panStartY=e.clientY-imgY; panArea.style.cursor='grabbing'; e.preventDefault(); });
    window.addEventListener('mousemove', e => { if(!isPanning)return; imgX=e.clientX-panStartX; imgY=e.clientY-panStartY; applyImgTransform(); });
    window.addEventListener('mouseup', () => { isPanning=false; panArea.style.cursor='grab'; });
    panArea.addEventListener('wheel', e => { e.preventDefault(); zoomImg(e.deltaY<0?0.15:-0.15); }, { passive: false });

    async function resolveImageUrl(url) {
        if (url && (url.includes('/manifest') || url.includes('manifest.json'))) {
            try {
                const m = await (await fetch(url, { headers: { 'Accept': 'application/json' } })).json();
                const imgRes = m.sequences?.[0]?.canvases?.[0]?.images?.[0]?.resource;
                if (imgRes) { const sid = imgRes.service?.['@id']||imgRes.service?.id; if(sid) return sid+'/full/max/0/default.jpg'; if(imgRes['@id']||imgRes.id) return imgRes['@id']||imgRes.id; }
                const item = m.items?.[0]?.items?.[0]?.items?.[0]?.body; if(item?.id) return item.id;
            } catch(e) {}
            return null;
        }
        return url;
    }
    async function openImgViewer(rawUrl, title, link) {
        document.getElementById('img-viewer-title').textContent = title||'';
        document.getElementById('img-viewer-link').href = link||rawUrl;
        imgEl.src=''; resetImgZoom(); imgViewer.style.display='flex';
        const resolved = await resolveImageUrl(rawUrl);
        if (resolved) { imgEl.src=resolved; } else { window.open(link||rawUrl,'_blank'); imgViewer.style.display='none'; }
    }
    function closeImgViewer() { imgViewer.style.display='none'; imgEl.src=''; }

    (function() {
        const bar=document.getElementById('img-viewer-bar'); let drag=false,dx,dy;
        bar.addEventListener('mousedown', e=>{ drag=true; const r=imgViewer.getBoundingClientRect(); dx=e.clientX-r.left; dy=e.clientY-r.top; e.preventDefault(); });
        window.addEventListener('mousemove', e=>{ if(!drag)return; imgViewer.style.left=(e.clientX-dx)+'px'; imgViewer.style.top=(e.clientY-dy)+'px'; });
        window.addEventListener('mouseup', ()=>{ drag=false; });
    })();
    (function() {
        const h=document.getElementById('img-resize-handle'); let res=false,sx,sy,sw,sh;
        h.addEventListener('mousedown', e=>{ res=true; sx=e.clientX; sy=e.clientY; sw=imgViewer.offsetWidth; sh=imgViewer.offsetHeight; e.preventDefault(); });
        window.addEventListener('mousemove', e=>{ if(!res)return; imgViewer.style.width=Math.max(200,sw+e.clientX-sx)+'px'; imgViewer.style.height=Math.max(150,sh+e.clientY-sy)+'px'; });
        window.addEventListener('mouseup', ()=>{ res=false; });
    })();

    // ── Nominatim ─────────────────────────────────────────────────────────────
function buildLocalityString(g) {
    return [g.verbatim_locality, g.municipality, g.county].filter(Boolean).join(', ');
}
    async function searchNominatim(query) {
        if (!query) return;
        document.getElementById('nominatim-results').innerHTML = '<p style="font-size:11px;color:#9ca3af;padding:4px">'+TXT.searching+'</p>';
        try {
            const results = await (await fetch('https://nominatim.openstreetmap.org/search?q='+encodeURIComponent(query)+'&format=json&polygon_geojson=1&limit=5', { headers: {'Accept-Language':'en'} })).json();
            if (!results.length) { document.getElementById('nominatim-results').innerHTML='<p style="font-size:11px;color:#9ca3af;padding:4px">'+TXT.noResults+'</p>'; return; }
            window._nominatimResults=results;
            document.getElementById('nominatim-results').innerHTML=results.map((r,i)=>
                '<button onclick="applyNominatimResult('+i+')" style="display:block;width:100%;text-align:left;font-size:11px;padding:5px;border-radius:4px;border:1px solid #e5e7eb;margin-bottom:2px;background:white;cursor:pointer" onmouseover="this.style.background=\'#f0fdf4\'" onmouseout="this.style.background=\'white\'">'+
                '<span style="font-weight:500;display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">'+r.display_name+'</span>'+
                '<span style="color:#9ca3af">'+r.type+' · '+parseFloat(r.lat).toFixed(4)+', '+parseFloat(r.lon).toFixed(4)+'</span></button>'
            ).join('');
        } catch(e) { document.getElementById('nominatim-results').innerHTML='<p style="font-size:11px;color:#ef4444;padding:4px">'+TXT.searchFailed+'</p>'; }
    }
    function applyNominatimResult(index) {
        const r=window._nominatimResults[index], lat=parseFloat(r.lat), lon=parseFloat(r.lon);
        if (r.geojson && (r.geojson.type==='Polygon'||r.geojson.type==='MultiPolygon')) {
            if (window._nominatimPolygon) map.removeLayer(window._nominatimPolygon);
            window._nominatimPolygon=L.geoJSON(r.geojson,{style:{color:'#16a34a',weight:2,fillOpacity:0.05}}).addTo(map);
            const bounds=window._nominatimPolygon.getBounds(), center=bounds.getCenter();
            const verts=[]; function cv(c){if(Array.isArray(c[0]))c.forEach(x=>cv(x));else verts.push(c);}
            if(r.geojson.type==='Polygon') r.geojson.coordinates.forEach(ring=>cv(ring));
            else r.geojson.coordinates.forEach(poly=>poly.forEach(ring=>cv(ring)));
            const R=6371000; let mx=0;
            verts.forEach(([vLon,vLat])=>{
                const a=Math.sin(((vLat-center.lat)*Math.PI/180)/2)**2+Math.cos(center.lat*Math.PI/180)*Math.cos(vLat*Math.PI/180)*Math.sin(((vLon-center.lng)*Math.PI/180)/2)**2;
                const d=R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a)); if(d>mx)mx=d;
            });
            const unc=Math.round(mx);
            document.getElementById('uncertainty-input').value=unc;
            document.getElementById('uncertainty-slider').max=Math.max(500000,Math.round(unc*1.5));
            document.getElementById('uncertainty-slider').value=unc;
            document.getElementById('uncertainty-display').textContent=unc.toLocaleString()+'m';
            placeMarker(center.lat,center.lng); map.fitBounds(bounds,{padding:[20,20]});
        } else { placeMarker(lat,lon); map.flyTo([lat,lon],12); }
        document.getElementById('nominatim-results').innerHTML='';
    }
    document.getElementById('nominatim-btn').addEventListener('click', ()=>searchNominatim(document.getElementById('nominatim-input').value.trim()));
    document.getElementById('nominatim-input').addEventListener('keydown', e=>{ if(e.key==='Enter') searchNominatim(e.target.value.trim()); });

    // ── Load next group ───────────────────────────────────────────────────────
    function clearSuggestionLayers() {
        if (window._suggestionLayers) window._suggestionLayers.forEach(l=>map.removeLayer(l));
        window._suggestionLayers=[];
    }
function showOverlay() {
    var o = document.getElementById('panel-overlay');
    if (o) { o.style.display = 'flex'; }
}
function hideOverlay() {
    var o = document.getElementById('panel-overlay');
    if (o) { o.style.display = 'none'; }
}

function clearPanel() {
    showOverlay();
    if(marker){map.removeLayer(marker);marker=null;} if(circle){map.removeLayer(circle);circle=null;} if(radiusHandle){map.removeLayer(radiusHandle);radiusHandle=null;}
    if(window._nominatimPolygon){map.removeLayer(window._nominatimPolygon);window._nominatimPolygon=null;}
    clearSuggestionLayers(); closeImgViewer();
    document.getElementById('submit-btn').disabled=true;
    document.getElementById('lat-input').value=''; document.getElementById('lng-input').value='';
    document.getElementById('uncertainty-display').textContent=''; document.getElementById('remarks-input').value='';
    document.getElementById('occurrence-loading').classList.remove('hidden');
    document.getElementById('occurrence-info').classList.add('hidden');
    // Clear stale content so overlay renders over blank, not old data
    document.getElementById('locality-fields').innerHTML='';
    document.getElementById('occurrences-list').innerHTML='';
    document.getElementById('suggestions-list').innerHTML='<p id="suggestions-empty" style="font-size:11px;color:#9ca3af;font-style:italic;padding:4px 0">{{ __("No suggestions yet for this group.") }}</p>';
    document.getElementById('comments-list').innerHTML='';
    document.getElementById('nominatim-results').innerHTML='';
}

function loadNextGroup() {
    clearPanel();
    var parts = [];
    if (window._georefFocus) parts.push('focus=' + encodeURIComponent(window._georefFocus));
    if (window._georefCountry) parts.push('country=' + encodeURIComponent(window._georefCountry));
    if (currentGroup) parts.push('exclude=' + currentGroup.id);
    fetch(APP_URL+'/georef/next?' + parts.join('&'), {headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(r=>r.json())
    .then(data=>{
        hideOverlay();
        document.getElementById('occurrence-loading').classList.add('hidden');
        if(data.group){
            addToHistory(data.group);
            currentGroup=data.group;
            renderGroup(data.group,data.occurrences,data.suggestions,data.comments);
            updateUrl(data.group.id);
        } else {
            document.getElementById('occurrence-info').classList.remove('hidden');
            let msg;
            if (data.focus_no_results) {
                msg = '<p style="color:#ef4444;font-size:11px">No localities found for "' + window._georefFocus + '". Try a different spelling.</p>';
            } else {
                msg = '<p style="color:#9ca3af;font-size:11px">'+TXT.noOcc+'</p>';
            }
            document.getElementById('locality-fields').innerHTML=msg;
        }
    })
    .catch(()=>{ hideOverlay(); document.getElementById('occurrence-loading').classList.add('hidden'); });
}

function loadGroup(groupId) {
    clearPanel();
    fetch(APP_URL+'/georef/group/'+groupId, {headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(r=>r.json())
    .then(data=>{
        hideOverlay();
        document.getElementById('occurrence-loading').classList.add('hidden');
        if(data.group){
            currentGroup=data.group;
            renderGroup(data.group,data.occurrences,data.suggestions,data.comments);
            updateUrl(data.group.id);
        }
    })
    .catch(()=>{ hideOverlay(); document.getElementById('occurrence-loading').classList.add('hidden'); });
}

function addToHistory(group) {
    sessionHistory = sessionHistory.filter(function(g){ return g.id !== group.id; });
    sessionHistory.push({ id: group.id, label: buildGroupLabel(group) });
    historyIndex = sessionHistory.length - 1;
    localStorage.setItem('georef_history', JSON.stringify(sessionHistory));
    localStorage.setItem('georef_index', historyIndex);
    updateHistoryNav();
}

function buildGroupLabel(group) {
    return [group.verbatim_locality, group.municipality, group.county, group.state_province, group.country_code]
        .filter(Boolean).join(', ');
}

function updateUrl(groupId) {
    var url = window.location.pathname + '?group=' + groupId;
    history.pushState({ groupId: groupId }, '', url);
}

function updateHistoryNav() {
    var prevBtn = document.getElementById('hist-prev');
    var nextBtn = document.getElementById('hist-next');
    var counter = document.getElementById('hist-counter');
    if(prevBtn) { prevBtn.disabled = historyIndex <= 0; prevBtn.style.color = historyIndex <= 0 ? '#d1d5db' : '#374151'; }
    if(nextBtn) { nextBtn.disabled = historyIndex >= sessionHistory.length - 1; nextBtn.style.color = historyIndex >= sessionHistory.length - 1 ? '#d1d5db' : '#374151'; }
    if(counter) counter.textContent = sessionHistory.length > 0 ? (historyIndex + 1) + '/' + sessionHistory.length : '0/0';
}

    // ── Render group ──────────────────────────────────────────────────────────
    let _currentOccurrences = [];

    function occSelectAll(checked) {
        document.querySelectorAll('.occurrence-checkbox').forEach(cb => cb.checked = checked);
    }
    function occSelectByStatus(georeffed) {
        const georefStatuses = ['gbif_georeferenced','gbif_reviewed','validated'];
        document.querySelectorAll('.occurrence-checkbox').forEach(cb => {
            const o = _currentOccurrences.find(o => String(o.id) === cb.value);
            if (!o) return;
            cb.checked = georeffed ? georefStatuses.includes(o.georef_status) : !georefStatuses.includes(o.georef_status);
        });
    }

    function renderGroup(group, occurrences, suggestions, comments) {
        document.getElementById('occurrence-info').classList.remove('hidden');
        _currentOccurrences = occurrences;
        const ctrl = document.getElementById('occ-select-controls');
        if (occurrences.length > 1) ctrl.classList.remove('hidden'); else ctrl.classList.add('hidden');
        const fieldDefs = [
            {key:'verbatim_locality', label:'Locality'},
            {key:'municipality',      label:'Municipality'},
            {key:'county',            label:'County'},
            {key:'state_province',    label:'State / Province'},
            {key:'island',            label:'Island'},
            {key:'island_group',      label:'Island group'},
            {key:'water_body',        label:'Water body'},
            {key:'country_code',      label:'Country'},
        ];
        document.getElementById('locality-fields').innerHTML=fieldDefs
            .filter(d=>group[d.key])
            .map(d=>
                '<div style="margin-bottom:5px">'+
                '<div style="color:#9ca3af;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;font-weight:500">'+d.label+'</div>'+
                '<div style="font-size:12px;font-weight:500;line-height:1.3;word-break:break-word">'+group[d.key]+'</div>'+
                '</div>'
            ).join('');
        document.getElementById('nominatim-input').value=buildLocalityString(group);
        document.getElementById('nominatim-results').innerHTML='';

        document.getElementById('occurrence-count').textContent=occurrences.length+' '+TXT.occurrences;

        const statusBadge = {
            'gbif_georeferenced': ['#6b7280','georeferenced'],
            'gbif_reviewed':      ['#16a34a','georeferenced ✓'],
            'validated':          ['#16a34a','validated ✓'],
            'has_suggestion':     ['#f59e0b','has suggestion'],
            'conflicted':         ['#ef4444','conflicted'],
            'ungeoreferenced':    ['#d1d5db','not georef'],
        };

        const clusterColors = ['#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];

        const occClusterColor = {};
        suggestions.forEach(function(s, i) {
            if (s.cluster_occurrence_ids && s.cluster_occurrence_ids.length > 0) {
                const color = clusterColors[i % clusterColors.length];
                s.cluster_occurrence_ids.forEach(function(oid) {
                    // Only assign if not already set (first suggestion wins per occurrence)
                    if (!occClusterColor[oid]) occClusterColor[oid] = color;
                });
            }
        });
        // Fallback: match occurrences to suggestions by GBIF coordinates
        if (suggestions.length > 1) {
            occurrences.forEach(function(o) {
                if (o.gbif_decimal_latitude && o.gbif_decimal_longitude) {
                    suggestions.forEach(function(s, i) {
                        if (Math.abs(o.gbif_decimal_latitude - s.decimal_latitude) < 0.01 &&
                            Math.abs(o.gbif_decimal_longitude - s.decimal_longitude) < 0.01) {
                            occClusterColor[o.id] = clusterColors[i % clusterColors.length];
                        }
                    });
                }
            });
        }

        document.getElementById('occurrences-list').innerHTML=occurrences.map(function(o){
            const label=[o.recorded_by,o.event_date].filter(Boolean).join(' · ')||o.gbif_occurrence_key;
            const taxon=o.scientific_name||'', meta=[o.institution_code,o.collection_code].filter(Boolean).join(' · ');
            const occId='occ-'+o.id, media=(o.media&&o.media.length>0)?o.media[0]:null;
            const clusterColor = occClusterColor[o.id];
            const clusterDot = clusterColor
                ? '<span title="Belongs to suggestion cluster" style="flex-shrink:0;display:inline-block;width:7px;height:7px;border-radius:50%;background:'+clusterColor+'"></span>'
                : '';
            const [badgeColor, badgeLabel] = statusBadge[o.georef_status] || ['#d1d5db','—'];
            const hasCoords = o.gbif_decimal_latitude && o.gbif_decimal_longitude;
            const coordHint = hasCoords
                ? '<span style="color:#9ca3af;font-size:10px">'+parseFloat(o.gbif_decimal_latitude).toFixed(4)+', '+parseFloat(o.gbif_decimal_longitude).toFixed(4)+'</span>'
                : '';
            const badge='<span style="flex-shrink:0;font-size:9px;font-weight:600;padding:1px 4px;border-radius:3px;background:'+badgeColor+'20;color:'+badgeColor+';border:1px solid '+badgeColor+'40;white-space:nowrap">'+badgeLabel+'</span>';
            var imgBtn='';
            if(media){
                var isDirectImg = media.identifier && !media.identifier.includes('manifest') &&
                    (/\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i.test(media.identifier) || (media.format && media.format.startsWith('image/')));
                var btnStyle='flex-shrink:0;width:28px;height:28px;border-radius:4px;overflow:hidden;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;background:#f9fafb;';
                var btnContent = isDirectImg
                    ? '<img src="'+media.identifier+'" style="width:28px;height:28px;object-fit:cover" loading="lazy" onerror="this.style.display=\'none\';this.parentElement.innerHTML=\'📷\';this.parentElement.style.fontSize=\'14px\'">'
                    : '<span style="font-size:14px" title="{{ __("View specimen image") }}">📷</span>';
                imgBtn='<button class="img-btn" style="'+btnStyle+'" data-src="'+media.identifier+'" data-title="'+(media.title||'').replace(/"/g,'&quot;')+'" data-link="'+media.identifier+'">'+btnContent+'</button>';
            }
            return '<div class="occ-row" id="'+occId+'" style="font-size:11px;border-radius:4px;border:1px solid transparent;padding:2px 0">'+
                '<div style="display:flex;align-items:flex-start;gap:6px;padding:4px 6px">'+
                '<input type="checkbox" class="occurrence-checkbox" value="'+o.id+'" checked style="flex-shrink:0;margin-top:2px">'+
                '<div style="flex:1;min-width:0">'+
(taxon?'<div style="font-style:italic;word-break:break-word;line-height:1.2">'+taxon+'</div>':'')+
'<div style="color:#9ca3af;word-break:break-word">'+label+'</div>'+
(meta?'<div style="color:#9ca3af">'+meta+'</div>':'')+
(hasCoords?'<div style="display:flex;align-items:center;gap:4px;margin-top:1px">'+clusterDot+coordHint+'</div>':'')+
                '</div>'+badge+
                '<a href="https://www.gbif.org/occurrence/'+o.gbif_occurrence_key+'" target="_blank" style="color:#16a34a;flex-shrink:0;text-decoration:none;font-size:10px;white-space:nowrap">GBIF ↗</a>'+
                imgBtn+'</div></div>';
        }).join('');

        document.querySelectorAll('.img-btn').forEach(function(btn){
            btn.addEventListener('click',function(e){e.stopPropagation();openImgViewer(this.dataset.src,this.dataset.title,this.dataset.link);});
        });

        clearSuggestionLayers();
        if (suggestions&&suggestions.length>0) {
            const colors=clusterColors;
            var competing = suggestions.length > 1 && suggestions.some(function(s){ return s.cluster_occurrence_ids && s.cluster_occurrence_ids.length > 0; });
            var sugHtml='';
            if (competing) {
                sugHtml += '<p style="font-size:10px;color:#6b7280;margin-bottom:6px;font-style:italic">Agreeing with one suggestion automatically disagrees with the others.</p>';
            }
            suggestions.forEach(function(s,i){
                var color=colors[i%colors.length];
                var c=L.circle([s.decimal_latitude,s.decimal_longitude],{radius:s.coordinate_uncertainty_m||1000,color:color,fillColor:color,fillOpacity:0.1,weight:2,dashArray:'6'}).addTo(map);
                var m=L.circleMarker([s.decimal_latitude,s.decimal_longitude],{radius:6,color:color,fillColor:color,fillOpacity:0.8,weight:2}).bindTooltip(s.submitted_by+' · ±'+s.coordinate_uncertainty_m+'m · '+s.total_points+'pts',{permanent:false}).addTo(map);
                window._suggestionLayers.push(c,m);
                var pct=Math.min(100,(s.total_points/THRESHOLD)*100);
                var dot='<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:'+color+';flex-shrink:0;margin-top:2px"></span>';
                var pillBase = 'font-size:11px;padding:2px 10px;border-radius:999px;border:1px solid;cursor:pointer;font-weight:500;';
                var valButtons = IS_AUTH
                    ? (s.is_own
                        ? '<span style="font-size:10px;color:#9ca3af;font-style:italic">{{ __("Your submission") }}</span>'+
                          '<button onclick="deleteSuggestion('+s.id+')" style="font-size:10px;padding:2px 8px;border-radius:999px;border:1px solid #ef4444;color:#ef4444;background:#fff1f2;cursor:pointer;">{{ __("Delete") }}</button>'
                        : '<button onclick="validateSuggestion('+s.id+',\'agree\','+competing+')" style="'+pillBase+'color:#16a34a;border-color:#16a34a;background:#f0fdf4;">'+TXT.agree+'</button>'+
                          '<button onclick="validateSuggestion('+s.id+',\'disagree\','+competing+')" style="'+pillBase+'color:#ef4444;border-color:#ef4444;background:#fff1f2;">'+TXT.disagree+'</button>')
                    : '<span style="color:#9ca3af;font-style:italic;font-size:10px">'+TXT.loginToVal+'</span>';
                sugHtml+='<div style="font-size:11px;border:1px solid #e5e7eb;border-radius:6px;padding:8px;margin-bottom:4px">'+
                    '<div style="display:flex;align-items:flex-start;gap:4px">'+dot+
                    '<div style="flex:1">'+
                    '<div style="display:flex;justify-content:space-between"><span style="font-weight:500">'+parseFloat(s.decimal_latitude).toFixed(5)+', '+parseFloat(s.decimal_longitude).toFixed(5)+'</span><span style="color:#9ca3af">±'+s.coordinate_uncertainty_m+'m</span></div>'+
                    '<div style="display:flex;justify-content:space-between;margin-top:4px;color:#9ca3af"><span>'+s.submitted_by+'</span><div style="display:flex;gap:8px">'+valButtons+'</div></div>'+
                    '<div style="background:#f3f4f6;border-radius:4px;height:4px;margin-top:6px"><div style="background:'+color+';height:4px;border-radius:4px;width:'+pct+'%"></div></div>'+
                    '<button onclick="previewSuggestion('+s.decimal_latitude+','+s.decimal_longitude+','+s.coordinate_uncertainty_m+')" style="color:#3b82f6;background:none;border:none;cursor:pointer;font-size:10px;margin-top:4px;padding:0">'+TXT.previewMap+'</button>'+
                    '</div></div></div>';
            });
            document.getElementById('suggestions-list').innerHTML=sugHtml;
        } else {
            document.getElementById('suggestions-list').innerHTML='<p style="font-size:11px;color:#9ca3af;font-style:italic;padding:4px 0">{{ __("No suggestions yet for this group.") }}</p>';
        }

        renderComments(comments||[]);
        updateMobileBar(group, (suggestions||[]).length);

if (window._suggestionLayers && window._suggestionLayers.length > 0) {
    var bounds = L.featureGroup(window._suggestionLayers).getBounds().pad(0.5);
    if (!map.getBounds().intersects(bounds)) {
        map.fitBounds(bounds);
    }
}
    }

    function renderComments(comments) {
        document.getElementById('comments-list').innerHTML=comments.map(function(c){
            return '<div style="font-size:11px;border-bottom:1px solid #f3f4f6;padding-bottom:4px"><span style="font-weight:500">'+c.user_name+'</span><span style="color:#9ca3af;margin-left:4px">'+c.created_at+'</span><p style="color:#6b7280;margin-top:2px">'+c.body+'</p></div>';
        }).join('');
    }
    function previewSuggestion(lat,lng,unc) {
        if(marker){map.removeLayer(marker);marker=null;} if(circle){map.removeLayer(circle);circle=null;} if(radiusHandle){map.removeLayer(radiusHandle);radiusHandle=null;}
        circle=L.circle([lat,lng],{radius:unc||1000,color:'#3b82f6',fillColor:'#3b82f6',fillOpacity:0.1,weight:2,dashArray:'6'}).addTo(map);
        map.flyTo([lat,lng],12);
    }
    function validateSuggestion(id, vote, hasCompeting) {
        const url = (vote === 'agree' && hasCompeting)
            ? APP_URL+'/georef/agree-with/'+id
            : APP_URL+'/georef/validate/'+id;
        const body = vote === 'agree' && hasCompeting ? '{}' : JSON.stringify({vote});

        fetch(url, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'}, body})
            .then(r=>r.json())
            .then(d => {
                if (!d.success) return;
                if (vote === 'agree' || !hasCompeting) {
                    loadNextGroup();
                } else {
                    if (currentGroup) loadGroup(currentGroup.id);
                }
            });
    }
    document.getElementById('submit-btn').addEventListener('click',function(){
        if(!currentGroup)return;
        var btn=this;
        btn.disabled=true;
        btn.innerHTML='<svg class="animate-spin inline w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>{{ __("Submitting...") }}';
        var excl=Array.from(document.querySelectorAll('.occurrence-checkbox:not(:checked)')).map(function(c){return c.value;});
        fetch(APP_URL+'/georef/submit',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},
            body:JSON.stringify({locality_group_id:currentGroup.id,decimal_latitude:document.getElementById('lat-input').value,decimal_longitude:document.getElementById('lng-input').value,coordinate_uncertainty_m:document.getElementById('uncertainty-input').value,georeference_remarks:document.getElementById('remarks-input').value,anon_name:document.getElementById('anon-name')?document.getElementById('anon-name').value:null,excluded_occurrence_ids:excl})})
        .then(r=>r.json()).then(d=>{
            btn.innerHTML='{{ __("Submit") }}';
            if(d.success)loadNextGroup();
            else btn.disabled=false;
        })
        .catch(function(){ btn.innerHTML='{{ __("Submit") }}'; btn.disabled=false; });
    });
document.getElementById('skip-btn').addEventListener('click', loadNextGroup);
document.getElementById('hist-prev').addEventListener('click', function(e) {
    e.stopPropagation();
    if(historyIndex > 0) { historyIndex--; localStorage.setItem('georef_index', historyIndex); loadGroup(sessionHistory[historyIndex].id); updateHistoryNav(); }
});
document.getElementById('hist-next').addEventListener('click', function(e) {
    e.stopPropagation();
    if(historyIndex < sessionHistory.length - 1) { historyIndex++; localStorage.setItem('georef_index', historyIndex); loadGroup(sessionHistory[historyIndex].id); updateHistoryNav(); }
});

window.addEventListener('popstate', function(e) {
    if(e.state && e.state.groupId) loadGroup(e.state.groupId);
});

updateHistoryNav();
var urlParams = new URLSearchParams(window.location.search);
var urlGroupId = urlParams.get('group');
var urlGbifKey = urlParams.get('gbif');
if(urlGbifKey) {
    loadByGbifKey(urlGbifKey);
} else if(urlGroupId) {
    var existingIdx = sessionHistory.findIndex(function(g){ return g.id === parseInt(urlGroupId); });
    if(existingIdx !== -1) { historyIndex = existingIdx; updateHistoryNav(); }
    loadGroup(parseInt(urlGroupId));
} else if(sessionHistory.length > 0 && historyIndex >= 0 && historyIndex < sessionHistory.length) {
    loadGroup(sessionHistory[historyIndex].id);
} else {
    function applyLocationAndLoad(countryCode) {
        if (countryCode) {
            window._georefCountry = countryCode;
            var sel = document.getElementById('country-select');
            if (sel) {
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].value === countryCode) { sel.value = countryCode; break; }
                }
            }
        }
        loadNextGroup();
    }

    // Cache country in localStorage for 24h to avoid calling ip-api on every load
    var cachedLoc = null;
    try {
        var raw = localStorage.getItem('georef_location');
        if (raw) {
            var parsed = JSON.parse(raw);
            if (parsed.ts && (Date.now() - parsed.ts) < 86400000) cachedLoc = parsed;
        }
    } catch(e) {}

    if (cachedLoc) {
        applyLocationAndLoad(cachedLoc.country_code);
    } else {
        var _bootTimer = setTimeout(function() { applyLocationAndLoad(null); }, 1500);
        fetch(APP_URL + '/georef/detect-location', { headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'} })
            .then(r => r.json())
            .then(function(loc) {
                clearTimeout(_bootTimer);
                if (loc && loc.country_code) {
                    try { localStorage.setItem('georef_location', JSON.stringify({country_code: loc.country_code, ts: Date.now()})); } catch(e) {}
                }
                applyLocationAndLoad(loc && loc.country_code ? loc.country_code : null);
            })
            .catch(function() { clearTimeout(_bootTimer); applyLocationAndLoad(null); });
    }
}

function loadByGbifKey(key) {
    // Strip full GBIF URLs to just the numeric key
    var match = key.match(/(\d{6,})/);
    if (match) key = match[1];
    clearPanel();
    fetch(APP_URL+'/georef/occurrence/'+encodeURIComponent(key), {headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(r=>r.json())
    .then(data=>{
        if(data.error) {
            document.getElementById('gbif-error').textContent = data.error;
            document.getElementById('gbif-error').style.display = 'block';
            return;
        }
        document.getElementById('gbif-error').style.display = 'none';
        addToHistory(data.group); currentGroup=data.group;
        renderGroup(data.group, data.occurrences, data.suggestions, data.comments);
        updateUrl(data.group.id);
    })
    .catch(()=>{ document.getElementById('gbif-error').textContent = 'Failed to load occurrence.'; document.getElementById('gbif-error').style.display='block'; });
}


// ── Focus input ───────────────────────────────────────────────────────────
    window._georefFocus   = '{{ request("focus", "") }}';
    window._georefCountry = '';

    var focusInput = document.getElementById('focus-input');
    var focusClear = document.getElementById('focus-clear');
    var focusHint  = document.getElementById('focus-hint');

    if (window._georefFocus) {
        focusInput.value = window._georefFocus;
        focusClear.style.display = 'block';
    }

    function applyFocus() {
        window._georefFocus = focusInput.value.trim();
        focusClear.style.display = window._georefFocus ? 'block' : 'none';
        focusHint.style.display = 'none';
        loadNextGroup();
    }

    focusInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') applyFocus(); });
    focusInput.addEventListener('blur', function() { if (focusInput.value.trim() !== window._georefFocus) applyFocus(); });
    focusClear.addEventListener('click', function() {
        focusInput.value = '';
        window._georefFocus = '';
        focusClear.style.display = 'none';
        focusHint.style.display = 'none';
        loadNextGroup();
    });


        var commentSubmit=document.getElementById('comment-submit');
    if(commentSubmit){
        commentSubmit.addEventListener('click',function(){
            var body=document.getElementById('comment-input').value.trim();
            if(!body||!currentGroup)return;
            fetch(APP_URL+'/georef/comment',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({locality_group_id:currentGroup.id,body:body})})
            .then(r=>r.json()).then(d=>{if(d.success){document.getElementById('comment-input').value='';renderComments(d.comments);}});
        });
    }

    // ── Layout ────────────────────────────────────────────────────────────────
    function applyLayout() {
        var wrap       = document.getElementById('georef-wrap');
        var leftPanel  = document.getElementById('left-panel');
        var rightPanel = document.getElementById('right-panel');
        var mapDiv     = document.getElementById('map');
        if (window.innerWidth < 768) {
            wrap.style.flexDirection = 'column';
            if (leftPanel)  { leftPanel.style.width='100%';  leftPanel.style.height='auto'; leftPanel.style.maxHeight='30%'; leftPanel.style.borderRight='none'; leftPanel.style.borderBottom='1px solid #e5e7eb'; }
            if (rightPanel) { rightPanel.style.width='100%'; rightPanel.style.height='45%'; rightPanel.style.borderLeft='none'; rightPanel.style.borderTop='1px solid #e5e7eb'; }
            mapDiv.style.height='25%'; mapDiv.style.flex='none';
        } else {
            wrap.style.flexDirection = 'row';
            if (leftPanel)  { leftPanel.style.width='260px'; leftPanel.style.height='100%'; leftPanel.style.maxHeight=''; leftPanel.style.borderRight='1px solid #e5e7eb'; leftPanel.style.borderBottom=''; }
            if (rightPanel) { rightPanel.style.width='320px'; rightPanel.style.height='100%'; rightPanel.style.borderLeft='1px solid #e5e7eb'; rightPanel.style.borderTop=''; }
            mapDiv.style.height=''; mapDiv.style.flex='1';
        }
        map.invalidateSize();
    }
    applyLayout();
    window.addEventListener('resize', applyLayout);

document.getElementById('hist-float-btn').addEventListener('click', function(e) {
    e.stopPropagation();
    var list = document.getElementById('hist-list');
    if(list.style.display === 'none' || list.style.display === '') {
        list.style.display = 'block';
        list.innerHTML = sessionHistory.slice().reverse().map(function(g, i) {
            var realIndex = sessionHistory.length - 1 - i;
            var active = realIndex === historyIndex;
            return '<div onclick="historyGoto('+realIndex+')" style="padding:6px 10px;font-size:11px;cursor:pointer;border-bottom:1px solid #f3f4f6;background:'+(active?'#f0fdf4':'white')+'" onmouseover="this.style.background=\'#f0fdf4\'" onmouseout="this.style.background=\''+(active?'#f0fdf4':'white')+'\'">'+g.label+'</div>';
        }).join('') || '<div style="padding:8px 10px;font-size:11px;color:#9ca3af">{{ __("No history yet") }}</div>';
    } else {
        list.style.display = 'none';
    }
});

function historyGoto(index) {
    historyIndex = index;
    localStorage.setItem('georef_index', index);
    loadGroup(sessionHistory[index].id);
    document.getElementById('hist-list').style.display = 'none';
    updateHistoryNav();
}
document.addEventListener('click', function() {
    var list = document.getElementById('hist-list');
    if(list) list.style.display = 'none';
});

// ── Mobile panel toggles ──────────────────────────────────────────────────────
function mobileToggle(panel) {
    var left  = document.getElementById('left-panel');
    var right = document.getElementById('right-panel');
    var btnInfo = document.getElementById('mob-btn-info');
    var btnSug  = document.getElementById('mob-btn-suggest');
    var active  = '#16a34a', inactive = '#6b7280';

    if (panel === 'info') {
        var opening = !left.classList.contains('mob-open');
        left.classList.toggle('mob-open');
        right.classList.remove('mob-open');
        btnInfo.style.color = opening ? active : inactive;
        btnSug.style.color  = inactive;
        if (opening) map.invalidateSize();
    } else {
        var opening = !right.classList.contains('mob-open');
        right.classList.toggle('mob-open');
        left.classList.remove('mob-open');
        btnSug.style.color  = opening ? active : inactive;
        btnInfo.style.color = inactive;
        if (opening) map.invalidateSize();
    }
}

function updateMobileBar(group, suggestionCount) {
    if (window.innerWidth > 768) return;
    // Build locality summary string
    var parts = [];
    if (group.verbatim_locality) parts.push(group.verbatim_locality);
    else {
        if (group.municipality)   parts.push(group.municipality);
        else if (group.county)    parts.push(group.county);
        if (group.state_province) parts.push(group.state_province);
    }
    if (group.country_code) parts.push(group.country_code);
    var text = parts.join(', ') || '—';

    var el    = document.getElementById('mob-locality-text');
    var track = document.getElementById('mob-locality-track');
    el.textContent = text;
    el.classList.remove('mob-marquee-anim');

    // Measure overflow and animate if needed
    requestAnimationFrame(function() {
        var overflow = el.scrollWidth - track.clientWidth;
        if (overflow > 10) {
            el.style.setProperty('--mob-scroll-dist', '-' + overflow + 'px');
            el.classList.add('mob-marquee-anim');
        }
    });

    // Suggestion badge
    var badge = document.getElementById('mob-sugg-badge');
    if (suggestionCount > 0) {
        badge.style.display = 'inline-block';
        badge.textContent   = suggestionCount > 9 ? '9+' : suggestionCount;
    } else {
        badge.style.display = 'none';
    }
}

function deleteSuggestion(id) {
    if (!confirm('{{ __("Delete your georeferencing suggestion? This cannot be undone.") }}')) return;
    fetch(APP_URL+'/georef/suggestion/'+id, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
    }).then(r => r.json()).then(d => {
        if (d.success && currentGroup) loadGroup(currentGroup.id);
    });
}

document.getElementById('share-btn').addEventListener('click', function() {
    if(!currentGroup) return;
    var url = APP_URL + '/georef?group=' + currentGroup.id;
    navigator.clipboard.writeText(url).then(function() {
        var btn = document.getElementById('share-btn');
        var orig = btn.innerHTML;
        btn.innerHTML = '✓';
        setTimeout(function(){ btn.innerHTML = orig; }, 2000);
    }).catch(function() {
        prompt('Copy this link:', APP_URL + '/georef?group=' + currentGroup.id);
    });
});
    </script>
    @endpush
</x-layouts.georef>
