<x-layouts.georef>
    <div id="georef-wrap" style="position:relative; height:100%; width:100%; display:flex; flex-direction:row;">

        {{-- LEFT PANEL: focus area + locality + occurrences --}}
        <div id="left-panel" style="width:260px; flex-shrink:0; z-index:10; display:flex; flex-direction:column; height:100%; overflow:hidden; border-right:1px solid #e5e7eb;"
            class="bg-white dark:bg-gray-900">

            {{-- Focus area --}}
            <div style="flex-shrink:0; border-bottom:1px solid #e5e7eb; padding:8px 12px; position:relative;">
                <div style="margin-bottom:4px;">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Focus area') }}</span>
                    <p style="font-size:10px;color:#9ca3af;margin:1px 0 4px">{{ __('Filter localities to georeference by area') }}</p>
                </div>
                <div style="display:flex;align-items:center;gap:6px;position:relative;">
                    <input type="text" id="focus-input" placeholder="{{ __('e.g. Redinha, Serra da Estrela...') }}"
                        autocomplete="off"
                        class="flex-1 text-xs panel-input border border-gray-200 rounded px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-500">
                    <button id="focus-clear" title="{{ __('Clear focus') }}" style="display:none;font-size:14px;background:none;border:none;cursor:pointer;color:#9ca3af;line-height:1;">×</button>
                    <span id="focus-hint" style="font-size:10px;color:#9ca3af;white-space:nowrap;display:none;"></span>
                </div>
                <div id="focus-dropdown" class="bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" style="display:none;position:absolute;left:0;right:0;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 6px 6px;z-index:50;box-shadow:0 4px 12px rgba(0,0,0,0.08);max-height:200px;overflow-y:auto;"></div>
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
                    <div style="margin-bottom:5px;">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Location to georeference') }}</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:4px;margin-bottom:6px;">
                        <div id="locality-fields" class="space-y-0.5 flex-1"></div>
                        <button id="share-btn" title="{{ __('Copy link to this locality') }}"
                            style="flex-shrink:0;padding:3px 7px;border:1px solid #e5e7eb;border-radius:4px;background:white;cursor:pointer;color:#16a34a;font-size:11px;margin-top:1px;"
                            onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='white'">🔗</button>
                    </div>
                    <div style="font-size:10px;color:#9ca3af;margin-bottom:3px;">{{ __('Find coordinates on map:') }}</div>
                    <div class="flex gap-1">
                        <input type="text" id="nominatim-input" class="flex-1 text-xs panel-input border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Search place name...') }}">
                        <button id="nominatim-btn" class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1.5 rounded-lg hover:bg-gray-200 shrink-0">🔍</button>
                    </div>
                    <div id="nominatim-results" class="mt-1 space-y-1 max-h-32 overflow-y-auto"></div>
                </div>
            </div>

            {{-- Occurrences list (takes remaining space) --}}
            <div id="occ-section" class="p-3" style="flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden;">
                <div style="flex-shrink:0;margin-bottom:4px;">
                    <span id="occ-panel-label" class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Occurrences without coordinates') }}</span>
                    <div id="occurrence-count" class="text-xs text-gray-400 mt-0.5"></div>
                </div>
                <p id="occ-panel-hint" class="text-xs text-gray-400 italic mb-1" style="flex-shrink:0;">{{ __('Uncheck to exclude from this georeference:') }}</p>
                <div id="occ-select-controls" class="hidden mb-1" style="flex-shrink:0;">
                    <div class="flex flex-wrap gap-1 items-center">
                        <button onclick="occSelectAll(true)"  class="text-xs px-2 py-0.5 rounded-full border border-gray-300 hover:bg-gray-100 text-gray-600">{{ __('All') }}</button>
                        <button onclick="occSelectAll(false)" class="text-xs px-2 py-0.5 rounded-full border border-gray-300 hover:bg-gray-100 text-gray-600">{{ __('None') }}</button>
                        <select id="inst-filter" onchange="occSelectByInstitution(this.value)" class="text-xs border border-gray-200 rounded px-1.5 py-0.5 bg-white text-gray-600 max-w-full" style="max-width:130px;">
                            <option value="">{{ __('All institutions') }}</option>
                        </select>
                    </div>
                </div>
                <div id="occurrences-list" class="space-y-0.5 overflow-y-auto" style="flex:1;min-height:0;"></div>
                <button id="load-more-occ-btn" onclick="loadMoreUngeoref()" style="display:none;width:100%;margin-top:6px;font-size:11px;padding:5px;border-radius:6px;border:1px solid #e5e7eb;color:#6b7280;background:white;cursor:pointer;flex-shrink:0;">{{ __('Load more') }}</button>
            </div>
        </div>

        {{-- SPECIMENS PANEL (mobile only shell — occ-section is moved here by JS on mobile) --}}
        <div id="specimens-panel" class="bg-white dark:bg-gray-900" style="display:none;"></div>

        {{-- Occurrence popup (draggable window, like image viewer) --}}
        <div id="occ-popup" style="display:none;position:absolute;top:60px;right:340px;z-index:30;width:320px;height:380px;min-width:220px;min-height:180px;"
            class="bg-white dark:bg-gray-900 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
            <div id="occ-popup-bar" class="flex items-center justify-between px-3 py-1.5 bg-gray-100 dark:bg-gray-800 cursor-move select-none shrink-0 border-b border-gray-200 dark:border-gray-700">
                <span class="text-xs text-gray-500 truncate flex-1 mr-2">{{ __('Georeferenced occurrences in cluster') }}</span>
                <button onclick="document.getElementById('occ-popup').style.display='none'" class="text-gray-400 hover:text-gray-600 text-sm leading-none ml-1">✕</button>
            </div>
            <div id="occ-popup-list" class="overflow-y-auto flex-1 px-3 py-2" style="min-height:0;font-size:11px;"></div>
            <div class="px-3 py-2 border-t border-gray-100 shrink-0">
                <button id="occ-popup-loadmore" onclick="fetchOccPopupPage(false)" style="display:none;width:100%;font-size:11px;padding:5px;border-radius:6px;border:1px solid #e5e7eb;color:#6b7280;background:white;cursor:pointer;">{{ __('Load more') }}</button>
            </div>
            <div id="occ-popup-resize" style="position:absolute;bottom:0;right:0;width:16px;height:16px;cursor:se-resize;z-index:10;">
                <svg viewBox="0 0 16 16" style="width:16px;height:16px;opacity:0.4;"><line x1="4" y1="16" x2="16" y2="4" stroke="#6b7280" stroke-width="1.5"/><line x1="8" y1="16" x2="16" y2="8" stroke="#6b7280" stroke-width="1.5"/><line x1="12" y1="16" x2="16" y2="12" stroke="#6b7280" stroke-width="1.5"/></svg>
            </div>
        </div>

        {{-- MAP --}}
        <div id="map" style="flex:1; position:relative; z-index:0;"></div>

        {{-- Map loading overlay --}}
        <div id="map-loading" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:15;background:rgba(255,255,255,0.88);backdrop-filter:blur(2px);align-items:center;justify-content:center;flex-direction:column;gap:16px;pointer-events:none;">
            <img src="{{ asset('images/loading-search.gif') }}" alt="" width="200" style="display:block;">
        </div>
        <div style="display:none">{{-- SVG fallback below, kept for reference --}}<svg viewBox="0 0 180 160" width="200" height="178" xmlns="http://www.w3.org/2000/svg" style="overflow:visible;display:none;">
                <style>
                    #ov-head  { animation: ov-shake 0.35s ease-in-out infinite alternate; transform-origin: 90px 52px; }
                    #ov-arml  { animation: ov-arml  0.6s ease-in-out infinite alternate; transform-origin: 78px 78px; }
                    #ov-armr  { animation: ov-armr  0.6s ease-in-out infinite alternate; transform-origin: 102px 78px; }
                    #ov-sw1   { animation: ov-sw 1s  0s   ease-in-out infinite; }
                    #ov-sw2   { animation: ov-sw 1s  .33s ease-in-out infinite; }
                    #ov-sw3   { animation: ov-sw 1s  .66s ease-in-out infinite; }
                    #ov-fp1   { animation: ov-fly 1.8s 0s   ease-in-out infinite; transform-origin: 30px 80px; }
                    #ov-fp2   { animation: ov-fly 2.1s 0.3s ease-in-out infinite; transform-origin: 150px 70px; }
                    #ov-fp3   { animation: ov-fly 1.6s 0.7s ease-in-out infinite; transform-origin: 55px 60px; }
                    #ov-fp4   { animation: ov-fly 2.3s 0.1s ease-in-out infinite; transform-origin: 130px 85px; }
                    #ov-d1    { animation: ov-dot 1.2s 0s   ease-in-out infinite; }
                    #ov-d2    { animation: ov-dot 1.2s .4s  ease-in-out infinite; }
                    #ov-d3    { animation: ov-dot 1.2s .8s  ease-in-out infinite; }
                    @keyframes ov-shake { from{transform:rotate(-8deg)} to{transform:rotate(8deg)} }
                    @keyframes ov-arml  { from{transform:rotate(-25deg)} to{transform:rotate(-5deg)} }
                    @keyframes ov-armr  { from{transform:rotate(5deg)}  to{transform:rotate(25deg)} }
                    @keyframes ov-sw    { 0%,100%{opacity:0;transform:translateY(0) scale(.6)} 40%{opacity:1} 80%{opacity:0;transform:translateY(-12px) scale(1)} }
                    @keyframes ov-fly   { 0%,100%{transform:rotate(0deg) translateY(0)} 50%{transform:rotate(12deg) translateY(-8px)} }
                    @keyframes ov-dot   { 0%,100%{opacity:.15} 50%{opacity:1} }
                </style>

                {{-- pile of papers (bottom) --}}
                {{-- back stack left --}}
                <rect x="8"  y="118" width="44" height="30" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(-12,30,133)"/>
                <rect x="12" y="116" width="44" height="30" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(-6,34,131)"/>
                <rect x="14" y="114" width="44" height="30" rx="2" fill="white" stroke="#1f2937" stroke-width="2"/>
                {{-- back stack right --}}
                <rect x="124" y="118" width="44" height="30" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(12,146,133)"/>
                <rect x="122" y="116" width="44" height="30" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(6,144,131)"/>
                <rect x="120" y="114" width="44" height="30" rx="2" fill="white" stroke="#1f2937" stroke-width="2"/>
                {{-- center pile --}}
                <rect x="62" y="120" width="56" height="28" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(-4,90,134)"/>
                <rect x="60" y="118" width="60" height="28" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(3,90,132)"/>
                <rect x="58" y="115" width="64" height="32" rx="2" fill="white" stroke="#1f2937" stroke-width="2"/>
                {{-- lines on front papers --}}
                <line x1="65" y1="122" x2="115" y2="122" stroke="#d1d5db" stroke-width="1.5"/>
                <line x1="65" y1="128" x2="110" y2="128" stroke="#d1d5db" stroke-width="1.5"/>
                <line x1="65" y1="134" x2="115" y2="134" stroke="#d1d5db" stroke-width="1.5"/>
                <line x1="65" y1="140" x2="108" y2="140" stroke="#d1d5db" stroke-width="1.5"/>

                {{-- flying papers --}}
                <g id="ov-fp1"><rect x="14" y="66" width="36" height="28" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(-20,32,80)"/><line x1="18" y1="75" x2="44" y2="71" stroke="#d1d5db" stroke-width="1.2"/><line x1="18" y1="81" x2="42" y2="77" stroke="#d1d5db" stroke-width="1.2"/><line x1="18" y1="87" x2="44" y2="83" stroke="#d1d5db" stroke-width="1.2"/></g>
                <g id="ov-fp2"><rect x="126" y="56" width="36" height="28" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(18,144,70)"/><line x1="130" y1="66" x2="156" y2="63" stroke="#d1d5db" stroke-width="1.2"/><line x1="130" y1="72" x2="154" y2="69" stroke="#d1d5db" stroke-width="1.2"/><line x1="130" y1="78" x2="156" y2="75" stroke="#d1d5db" stroke-width="1.2"/></g>
                <g id="ov-fp3"><rect x="46" y="44" width="30" height="24" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(-8,61,56)"/><line x1="50" y1="52" x2="72" y2="51" stroke="#d1d5db" stroke-width="1.2"/><line x1="50" y1="58" x2="70" y2="57" stroke="#d1d5db" stroke-width="1.2"/></g>
                <g id="ov-fp4"><rect x="104" y="48" width="30" height="24" rx="2" fill="white" stroke="#1f2937" stroke-width="2" transform="rotate(10,119,60)"/><line x1="108" y1="56" x2="130" y2="58" stroke="#d1d5db" stroke-width="1.2"/><line x1="108" y1="62" x2="128" y2="64" stroke="#d1d5db" stroke-width="1.2"/></g>

                {{-- torso (buried in papers) --}}
                <rect x="78" y="88" width="24" height="32" rx="6" fill="white" stroke="#1f2937" stroke-width="2.5"/>

                {{-- left arm raised --}}
                <g id="ov-arml">
                    <line x1="78" y1="94" x2="54" y2="72" stroke="#1f2937" stroke-width="2.5" stroke-linecap="round"/>
                    {{-- hand --}}
                    <line x1="54" y1="72" x2="48" y2="65" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                    <line x1="54" y1="72" x2="51" y2="63" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                    <line x1="54" y1="72" x2="56" y2="63" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                    <line x1="54" y1="72" x2="61" y2="65" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                </g>

                {{-- right arm raised --}}
                <g id="ov-armr">
                    <line x1="102" y1="94" x2="126" y2="72" stroke="#1f2937" stroke-width="2.5" stroke-linecap="round"/>
                    {{-- hand --}}
                    <line x1="126" y1="72" x2="132" y2="65" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                    <line x1="126" y1="72" x2="129" y2="63" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                    <line x1="126" y1="72" x2="124" y2="63" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                    <line x1="126" y1="72" x2="119" y2="65" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                </g>

                {{-- head --}}
                <g id="ov-head">
                    <circle cx="90" cy="64" r="16" fill="white" stroke="#1f2937" stroke-width="2.5"/>
                    {{-- wide panic eyes --}}
                    <circle cx="83" cy="62" r="4.5" fill="white" stroke="#1f2937" stroke-width="2"/>
                    <circle cx="97" cy="62" r="4.5" fill="white" stroke="#1f2937" stroke-width="2"/>
                    <circle cx="84" cy="63" r="2" fill="#1f2937"/>
                    <circle cx="98" cy="63" r="2" fill="#1f2937"/>
                    {{-- panic mouth --}}
                    <path d="M83 72 Q90 68 97 72" fill="none" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                    {{-- eyebrows worried --}}
                    <line x1="79" y1="56" x2="87" y2="58" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                    <line x1="93" y1="58" x2="101" y2="56" stroke="#1f2937" stroke-width="2" stroke-linecap="round"/>
                </g>

                {{-- sweat drops --}}
                <path id="ov-sw1" d="M107 44 Q109 40 111 44 Q111 48 107 44Z" fill="none" stroke="#1f2937" stroke-width="1.5"/>
                <path id="ov-sw2" d="M115 52 Q117 48 119 52 Q119 56 115 52Z" fill="none" stroke="#1f2937" stroke-width="1.5"/>
                <path id="ov-sw3" d="M104 36 Q106 32 108 36 Q108 40 104 36Z" fill="none" stroke="#1f2937" stroke-width="1.5"/>

                {{-- dots --}}
                <circle id="ov-d1" cx="82" cy="153" r="4" fill="#1f2937"/>
                <circle id="ov-d2" cx="90" cy="153" r="4" fill="#1f2937"/>
                <circle id="ov-d3" cx="98" cy="153" r="4" fill="#1f2937"/>
            </svg></div>

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
                <div id="dismiss-system-row" style="display:none" class="px-3 pb-2">
                    <button id="dismiss-system-btn" class="w-full text-xs border border-amber-300 text-amber-700 bg-amber-50 hover:bg-amber-100 dark:border-amber-600 dark:text-amber-400 dark:bg-amber-900/20 rounded-lg py-1.5 font-medium">
                        {{ __('No conflict — dismiss system suggestions') }}
                    </button>
                </div>
            </div>

            {{-- Similar groups --}}
            <div id="similar-groups-wrap" style="display:none;flex-shrink:0;border-bottom:1px solid #e5e7eb;">
                <div class="px-3 pt-2 pb-1" style="flex-shrink:0;">
                    <span class="text-xs font-medium text-orange-500 uppercase tracking-wide">{{ __('Similar localities') }}</span>
                </div>
                <div id="similar-groups-list" class="px-3 pb-2 space-y-2 overflow-y-auto" style="max-height:180px;"></div>
            </div>

            {{-- Georef form (takes remaining space) --}}
            <div class="p-4 overflow-y-auto" style="flex:1;min-height:0;">
                <div style="margin-bottom:10px;">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Your georeference') }}</span>
                </div>
                {{-- Mode toggle: only shown when there are suggestions --}}
                <div id="mode-toggle-wrap" style="display:none;margin-bottom:10px;">
                    <button id="mode-toggle-btn" onclick="togglePointMode()" style="width:100%;font-size:11px;font-weight:600;padding:7px;border-radius:6px;border:1.5px solid #4C9C2E;color:#4C9C2E;background:#f0fdf4;cursor:pointer;text-align:center;transition:background 0.2s,color 0.2s;">
                        + {{ __('Submit a different point') }}
                    </button>
                </div>
                <style>
                @keyframes btn-flash {
                    0%,100% { background:#f0fdf4; color:#4C9C2E; }
                    40%     { background:#4C9C2E; color:#fff; }
                }
                .btn-flash { animation: btn-flash 0.5s ease 2; }
                .dark-text  { color: #111827; }
                .sugg-card  { border: 1px solid #e5e7eb; background: #ffffff; }
                .sugg-divider { border-top: 1px solid #f3f4f6; }
                .sugg-bar-bg  { background: #f3f4f6; }
                .vote-agree-btn    { color:#16a34a;border:1px solid #16a34a;background:#f0fdf4; }
                .vote-disagree-btn { color:#ef4444;border:1px solid #ef4444;background:#fff1f2; }
                .delete-sug-btn    { color:#ef4444;border:1px solid #ef4444;background:#fff1f2; }
                .use-similar-btn   { color:#4C9C2E;border:1.5px solid #4C9C2E;background:#fff; }
                .panel-input { color: #111827; background: #ffffff; }
                @media (prefers-color-scheme: dark) {
                    .dark-text  { color: #f9fafb; }
                    .sugg-card  { border-color: #374151; background: #1f2937; }
                    .sugg-divider { border-top-color: #374151; }
                    .sugg-bar-bg  { background: #374151; }
                    .vote-agree-btn    { background: #052e16; }
                    .vote-disagree-btn { background: #2d1515; }
                    .delete-sug-btn    { background: #2d1515; }
                    .use-similar-btn   { background: #1f2937; }
                    .panel-input { color: #f9fafb; background: #1f2937; border-color: #374151; }
                    #focus-dropdown { background: #1f2937; border-color: #374151; color: #f9fafb; }
                }
                </style>
                <p id="map-click-hint" class="text-xs text-gray-400 mb-3 mt-0" style="display:none;">{{ __('Click on the map to place a point. Drag to adjust.') }}</p>
                <form id="georef-form" class="space-y-2">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Latitude') }}</label>
                            <input type="number" id="lat-input" step="0.0000001" class="w-full text-xs panel-input border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="0.0000000">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Longitude') }}</label>
                            <input type="number" id="lng-input" step="0.0000001" class="w-full text-xs panel-input border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="0.0000000">
                        </div>
                        <button type="button" id="reset-point-btn" onclick="resetPoint()" title="{{ __('Clear point') }}" style="flex-shrink:0;padding:5px 8px;border-radius:6px;border:1px solid #e5e7eb;cursor:pointer;font-size:12px;line-height:1;" class="hover:text-red-400 hover:border-red-300 bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-400">✕</button>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Uncertainty') }} <span id="uncertainty-display" class="text-green-600 font-semibold"></span></label>
                        <input type="number" id="uncertainty-input" min="1" class="w-full text-xs panel-input border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="1000">
                        <input type="range" id="uncertainty-slider" min="100" max="500000" step="1000" value="1000" class="w-full mt-1 accent-green-600">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Remarks') }}</label>
                        <textarea id="remarks-input" rows="2" class="w-full text-xs panel-input border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Optional notes...') }}"></textarea>
                    </div>
                    @guest
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Your name (optional)') }}</label>
                        <input type="text" id="anon-name" class="w-full text-xs panel-input border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Anonymous') }}">
                    </div>
                    @endguest
                </form>
            </div>

            {{-- Discussion --}}
            <div id="discussion-section" class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Discussion') }}</span>
                <div id="comments-list" class="mt-1 space-y-1 max-h-20 overflow-y-auto"></div>
                @auth
                <div class="mt-2 flex gap-1">
                    <input type="text" id="comment-input" class="flex-1 text-xs panel-input border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Add a comment...') }}">
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
            <p id="submit-hint" style="display:none;font-size:10px;color:#9ca3af;text-align:center;margin-top:4px">{{ __('Check "Correct georef. occurrences" on at least one card to enable submit.') }}</p>
        </div>

        {{-- Toast: vote mode warning --}}
    <div id="vote-mode-toast" style="position:absolute;bottom:80px;left:50%;transform:translateX(-50%);z-index:500;background:rgba(17,24,39,0.88);color:#fff;font-size:12px;padding:8px 14px;border-radius:8px;pointer-events:none;opacity:0;transition:opacity 0.3s;white-space:nowrap;max-width:90vw;text-align:center;">
        {{ __("There are suggestions to review. To place a new point, click") }} <strong>+ {{ __("Submit a different point") }}</strong>.
    </div>

    {{-- Hidden stubs for dead JS references --}}
        <input type="text" id="area-search" style="display:none">
        <button id="area-search-btn" style="display:none"></button>
        <span id="area-hint" style="display:none"></span>
    </div>{{-- end #georef-wrap --}}

    {{-- Help button injected into map by JS --}}
    <button id="tut-btn" onclick="tutStart();event.stopPropagation();" title="{{ __('How to use') }}" style="display:none;position:absolute;top:10px;right:10px;z-index:999;font-size:11px;font-weight:600;color:#16a34a;background:rgba(255,255,255,0.92);border:1px solid #bbf7d0;border-radius:999px;padding:3px 10px;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.12);line-height:1.6;">? Help</button>

    {{-- Tutorial overlay --}}
    <div id="tut-overlay" style="display:none;position:fixed;inset:0;z-index:1000;">
        {{-- spotlight element --}}
        <div id="tut-spot" style="position:fixed;z-index:1001;border-radius:6px;transition:all .35s cubic-bezier(.4,0,.2,1);pointer-events:none;box-shadow:0 0 0 9999px rgba(0,0,0,0.58);"></div>
        {{-- tooltip card --}}
        <div id="tut-card" style="position:fixed;z-index:1002;width:280px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.22);padding:20px;transition:all .3s cubic-bezier(.4,0,.2,1);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <span id="tut-step-label" style="font-size:10px;font-weight:600;color:#16a34a;text-transform:uppercase;letter-spacing:.08em;"></span>
                <button onclick="tutEnd()" style="border:none;background:none;font-size:18px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;">×</button>
            </div>
            <h3 id="tut-title" style="font-size:15px;font-weight:700;color:#111827;margin:0 0 8px;"></h3>
            <p id="tut-text" style="font-size:13px;color:#6b7280;margin:0 0 16px;line-height:1.55;"></p>
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <button id="tut-prev" onclick="tutStep(-1)" style="font-size:12px;border:1px solid #e5e7eb;background:white;color:#374151;padding:6px 14px;border-radius:7px;cursor:pointer;">← Back</button>
                <div id="tut-dots" style="display:flex;gap:5px;"></div>
                <button id="tut-next" onclick="tutStep(1)" style="font-size:12px;background:#16a34a;color:white;border:none;padding:6px 14px;border-radius:7px;cursor:pointer;font-weight:600;">Next →</button>
            </div>
            <button onclick="tutEnd()" style="display:block;width:100%;margin-top:10px;font-size:11px;color:#9ca3af;background:none;border:none;cursor:pointer;text-align:center;padding:4px 0;">Skip tutorial</button>
            <button onclick="tutEnd();videoOpen()" style="display:block;width:100%;margin-top:4px;font-size:11px;color:#16a34a;background:none;border:none;cursor:pointer;text-align:center;padding:4px 0;">▶ Watch video tutorial</button>
        </div>
    </div>

    {{-- Video modal --}}
    <div id="video-modal" onclick="if(event.target===this)videoClose()" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,0.75);align-items:center;justify-content:center;padding:16px;">
        <div style="position:relative;width:80%;max-width:1200px;">
            <button onclick="videoClose()" style="position:absolute;top:-36px;right:0;background:none;border:none;color:white;font-size:24px;cursor:pointer;line-height:1;">×</button>
            <div style="padding:56.25% 0 0 0;position:relative;border-radius:10px;overflow:hidden;">
                <iframe id="video-iframe" src="" frameborder="0"
                    allow="autoplay; fullscreen; picture-in-picture"
                    style="position:absolute;top:0;left:0;width:100%;height:100%;"
                    title="georeference.it tutorial">
                </iframe>
            </div>
        </div>
    </div>

    {{-- Mobile bottom bar — outside georef-wrap --}}
    {{-- Thin locality bar shown over map on mobile (right half only, to avoid overlapping history buttons) --}}
    <div id="mob-locality-bar" style="display:none;position:fixed;top:48px;left:50%;right:0;z-index:39;background:rgba(255,255,255,0.93);border-bottom:1px solid #e5e7eb;border-left:1px solid #e5e7eb;border-radius:0 0 0 6px;padding:3px 10px;backdrop-filter:blur(4px);">
        <span id="mob-locality-text" style="font-size:10px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;"></span>
        <span id="mob-locality-spinner" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);">
            <svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;color:#9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                <path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </span>
    </div>

    <div id="mobile-tabs" style="position:fixed;bottom:0;left:0;right:0;z-index:201;background:white;border-top:1px solid #e5e7eb;height:52px;"
        class="dark:bg-gray-900 dark:border-gray-700">
        <div style="display:flex;align-items:stretch;height:100%;">

            {{-- Left group: Location | Specimens | Georef --}}
            <div id="mob-action-bar" style="display:none;align-items:stretch;flex:1;" class="dark:border-gray-700">
                <button id="mob-btn-info" onclick="mobileToggle('info')"
                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;border:none;background:none;font-size:9px;font-weight:600;color:#6b7280;cursor:pointer;flex:1;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Location
                </button>
                <div style="width:1px;background:#e5e7eb;flex-shrink:0;" class="dark:bg-gray-700"></div>
                <button id="mob-btn-specimens" onclick="mobileToggle('specimens')"
                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;border:none;background:none;font-size:9px;font-weight:600;color:#6b7280;cursor:pointer;flex:1;position:relative;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span id="mob-occ-badge" style="display:none;position:absolute;top:4px;right:calc(50% - 18px);background:#6b7280;color:white;font-size:8px;font-weight:700;line-height:1;padding:2px 4px;border-radius:999px;min-width:14px;text-align:center;"></span>
                    Specimens
                </button>
                <div style="width:1px;background:#e5e7eb;flex-shrink:0;" class="dark:bg-gray-700"></div>
                <button id="mob-btn-suggest" onclick="mobileToggle('suggest')"
                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;border:none;background:none;font-size:9px;font-weight:600;color:#6b7280;cursor:pointer;flex:1;position:relative;">
                    <span style="position:relative;display:inline-block;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span id="mob-sugg-badge" style="display:none;position:absolute;top:-5px;right:-6px;background:#ef4444;color:white;font-size:8px;font-weight:700;line-height:1;padding:2px 4px;border-radius:999px;min-width:14px;text-align:center;"></span>
                    </span>
                    Georef
                </button>
            </div>

            {{-- Right group: Skip | Submit --}}
            <div id="mob-right-bar" style="display:none;align-items:stretch;flex-shrink:0;border-left:1px solid #e5e7eb;" class="dark:border-gray-700">
                <button id="mob-skip-btn" onclick="mobSkip()"
                    style="display:flex;align-items:center;justify-content:center;border:none;background:none;font-size:11px;font-weight:500;color:#6b7280;cursor:pointer;padding:0 14px;height:100%;">
                    Skip
                </button>
                <div style="width:1px;background:#e5e7eb;flex-shrink:0;" class="dark:bg-gray-700"></div>
                <button id="mob-submit-btn" onclick="document.getElementById('submit-btn').click()"
                    style="display:flex;align-items:center;justify-content:center;border:none;background:none;font-size:11px;font-weight:700;color:#16a34a;cursor:pointer;padding:0 14px;height:100%;opacity:0.35;" disabled>
                    Submit
                </button>
            </div>
        </div>
    </div>{{-- end #mobile-tabs --}}

    <style>
    /* Hidden on desktop, shown on mobile via media query */
    #mobile-tabs { display:none; }
    #mob-locality-bar { display:none; }

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
        #mobile-tabs { display:block !important; }
        #mob-locality-bar { display:block !important; }
        #mob-action-bar.mob-loaded { display:flex !important; }
        #mob-action-bar { display:none; }
        #mob-right-bar.mob-loaded { display:flex !important; }
        #mob-right-bar { display:none; }

        #georef-wrap { flex-direction: column !important; padding-bottom: 52px; }

        /* shared drawer style for all 3 panels */
        #left-panel, #specimens-panel, #right-panel {
            position: fixed !important;
            bottom: 52px !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            border-left: none !important;
            border-right: none !important;
            border-top: 2px solid #e5e7eb !important;
            border-radius: 16px 16px 0 0 !important;
            z-index: 40 !important;
            transform: translateY(100%) !important;
            transition: transform 0.3s cubic-bezier(.4,0,.2,1) !important;
            box-shadow: 0 -4px 24px rgba(0,0,0,0.12) !important;
            pointer-events: none !important;
            display: flex !important;
            flex-direction: column !important;
        }
        #left-panel     { height: 60vh !important; max-height: 60vh !important; }
        #specimens-panel{ height: 65vh !important; max-height: 65vh !important; }
        #right-panel    { height: 75vh !important; max-height: 75vh !important; }

        #left-panel.mob-open, #specimens-panel.mob-open, #right-panel.mob-open {
            transform: translateY(0) !important; pointer-events:auto !important;
        }
        #left-panel::before, #specimens-panel::before, #right-panel::before {
            content: '';
            display: block;
            width: 36px; height: 4px;
            background: #d1d5db;
            border-radius: 2px;
            margin: 8px auto 4px;
            flex-shrink: 0;
        }

        #occ-section { flex:1; min-height:0; }

        #map {
            position: fixed !important;
            top: 48px !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 52px !important;
            width: 100% !important;
            height: auto !important;
            z-index: 1 !important;
        }

        #map-loading {
            position: fixed !important;
            top: 48px !important; left: 0 !important; right: 0 !important;
            bottom: 52px !important;
            z-index: 10 !important;
        }

        /* History button — reposition for mobile */
        #georef-wrap > div[style*="left:272px"] {
            left: 8px !important;
            top: 8px !important;
        }

        /* Help button — keep near top-right on mobile */
        #tut-btn {
            top: 56px !important;
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
    var pendingVotes = {};
    var georefMode = 'new'; // 'vote' | 'new'
    var _currentSuggestions = [];
    map = L.map('map', { zoomControl: false }).setView([0, 0], 2);
    const osm           = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors', maxZoom: 19 });
    const cartoVoyager  = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { attribution: '© OpenStreetMap contributors © CARTO', maxZoom: 19, subdomains: 'abcd' });
    const esriSat       = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    const esriLabels    = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, pane: 'overlayPane' });
    const esriSatLabels = L.layerGroup([esriSat, esriLabels]);
    const esriStreet    = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    const esriTopo      = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    osm.addTo(map);
    L.control.layers({ 'OpenStreetMap': osm, 'Carto Voyager (English)': cartoVoyager, 'ESRI Satellite': esriSat, 'ESRI Satellite + Labels': esriSatLabels, 'ESRI Street Map': esriStreet, 'ESRI Topo': esriTopo }, {}, { position: 'bottomleft' }).addTo(map);
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
    map.on('click', e => {
        if (measureMode) return;
        if (georefMode === 'vote') { showVoteModeToast(); return; }
        placeMarker(e.latlng.lat, e.latlng.lng);
    });

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

        const logoIcon = L.icon({
            iconUrl: '{{ asset('images/logo.png') }}',
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40],
        });
        marker = L.marker([lat, lng], { draggable: true, icon: logoIcon }).addTo(map);
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
        updateSubmitBtn();
        var ms = document.getElementById('mob-submit-btn'); if(ms){ms.disabled=false;ms.style.opacity='1';}

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
    panArea.addEventListener('wheel', e => {
        e.preventDefault();
        const d = e.deltaY < 0 ? 0.15 : -0.15;
        const rect = panArea.getBoundingClientRect();
        // Mouse position relative to the pan area
        const mx = e.clientX - rect.left;
        const my = e.clientY - rect.top;
        // Adjust pan so zoom pivots on the mouse point
        const prevScale = imgScale;
        imgScale = Math.max(0.2, Math.min(8, imgScale + d));
        const factor = imgScale / prevScale;
        imgX = mx - factor * (mx - imgX);
        imgY = my - factor * (my - imgY);
        applyImgTransform();
    }, { passive: false });

    async function resolveImageUrl(url) {
        if (url && (url.includes('/manifest') || url.includes('manifest.json') || url.includes('iiif'))) {
            try {
                // Fetch via server-side proxy to avoid CORS issues
                const proxyUrl = '{{ route("georef.iiif-proxy") }}?url=' + encodeURIComponent(url);
                const m = await (await fetch(proxyUrl)).json();
                // IIIF Presentation API 2.x
                const imgRes = m.sequences?.[0]?.canvases?.[0]?.images?.[0]?.resource;
                if (imgRes) {
                    const sid = imgRes.service?.['@id'] || imgRes.service?.id;
                    if (sid) return sid + '/full/max/0/default.jpg';
                    if (imgRes['@id'] || imgRes.id) return imgRes['@id'] || imgRes.id;
                }
                // IIIF Presentation API 3.x
                const body = m.items?.[0]?.items?.[0]?.items?.[0]?.body;
                if (body?.id) return body.id;
                if (body?.service?.[0]?.['@id']) return body.service[0]['@id'] + '/full/max/0/default.jpg';
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

    // ── Occ popup drag & resize ───────────────────────────────────────────────
    (function(){
        const win = document.getElementById('occ-popup');
        const bar = document.getElementById('occ-popup-bar');
        const rh  = document.getElementById('occ-popup-resize');
        let drag=false,dx,dy,res=false,sx,sy,sw,sh;
        bar.addEventListener('mousedown', e=>{ drag=true; dx=e.clientX-win.offsetLeft; dy=e.clientY-win.offsetTop; e.preventDefault(); });
        rh.addEventListener('mousedown',  e=>{ res=true; sx=e.clientX; sy=e.clientY; sw=win.offsetWidth; sh=win.offsetHeight; e.preventDefault(); });
        window.addEventListener('mousemove', e=>{
            if(drag){ win.style.left=Math.max(0,e.clientX-dx)+'px'; win.style.top=Math.max(0,e.clientY-dy)+'px'; win.style.right='auto'; }
            if(res){ win.style.width=Math.max(220,sw+e.clientX-sx)+'px'; win.style.height=Math.max(180,sh+e.clientY-sy)+'px'; }
        });
        window.addEventListener('mouseup', ()=>{ drag=false; res=false; });
    })();

    // ── Nominatim ─────────────────────────────────────────────────────────────
function buildLocalityString(g) {
    const loc = g.verbatim_locality || '';
    const mun = g.municipality || '';
    const cty = g.county || '';
    const parts = [];
    if (loc) parts.push(loc);
    // Add administrative fields only if their text isn't already present in the locality
    const locLower = loc.toLowerCase();
    if (mun && !locLower.includes(mun.toLowerCase())) parts.push(mun);
    if (cty && !locLower.includes(cty.toLowerCase())) parts.push(cty);
    return parts.join(', ');
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
            const bounds=window._nominatimPolygon.getBounds();
            // collect all vertices
            const verts=[]; function cv(c){if(Array.isArray(c[0]))c.forEach(x=>cv(x));else verts.push(c);}
            if(r.geojson.type==='Polygon') r.geojson.coordinates.forEach(rng=>cv(rng));
            else r.geojson.coordinates.forEach(poly=>poly.forEach(rng=>cv(rng)));
            // project to local Cartesian metres (flat-earth ok for locality scale)
            const bc=bounds.getCenter(), RE=6371000, cosLat=Math.cos(bc.lat*Math.PI/180);
            const pts=verts.map(([vLon,vLat])=>({x:(vLon-bc.lng)*Math.PI/180*RE*cosLat, y:(vLat-bc.lat)*Math.PI/180*RE}));
            // minimum enclosing circle – Welzl algorithm
            function _d2(a,b){return(a.x-b.x)**2+(a.y-b.y)**2;}
            function _c2(a,b){return{x:(a.x+b.x)/2,y:(a.y+b.y)/2,r2:_d2(a,b)/4};}
            function _c3(a,b,c){
                const ax=b.x-a.x,ay=b.y-a.y,bx=c.x-a.x,by=c.y-a.y,D=2*(ax*by-ay*bx);
                if(Math.abs(D)<1e-10)return _c2(a,b);
                const ux=(by*(ax*ax+ay*ay)-ay*(bx*bx+by*by))/D;
                const uy=(ax*(bx*bx+by*by)-bx*(ax*ax+ay*ay))/D;
                return{x:a.x+ux,y:a.y+uy,r2:ux*ux+uy*uy};
            }
            function _inC(c,p){return _d2(c,p)<=c.r2*(1+1e-10);}
            function welzl(P,R){
                if(!P.length||R.length===3){
                    if(!R.length)return{x:0,y:0,r2:0};
                    if(R.length===1)return{x:R[0].x,y:R[0].y,r2:0};
                    if(R.length===2)return _c2(R[0],R[1]);
                    return _c3(R[0],R[1],R[2]);
                }
                const[p,...rest]=P, D=welzl(rest,R);
                if(D&&_inC(D,p))return D;
                return welzl(rest,[...R,p]);
            }
            // subsample to avoid stack overflow on very large polygons (e.g. country-level)
            // the final radius is corrected via Haversine over all verts anyway
            const MAX_W=800;
            let wPts=pts;
            if(pts.length>MAX_W){const step=pts.length/MAX_W;wPts=Array.from({length:MAX_W},(_,i)=>pts[Math.floor(i*step)]);}
            // shuffle for expected O(n) performance
            const shuffled=wPts.slice().sort(()=>Math.random()-0.5);
            const mec=welzl(shuffled,[]);
            // convert MEC center back to lat/lon
            const mecLon=bc.lng+mec.x/(RE*cosLat)*180/Math.PI;
            const mecLat=bc.lat+mec.y/RE*180/Math.PI;
            // final radius via Haversine to farthest vertex
            let mx=0;
            verts.forEach(([vLon,vLat])=>{
                const a=Math.sin(((vLat-mecLat)*Math.PI/180)/2)**2+Math.cos(mecLat*Math.PI/180)*Math.cos(vLat*Math.PI/180)*Math.sin(((vLon-mecLon)*Math.PI/180)/2)**2;
                const d=RE*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a)); if(d>mx)mx=d;
            });
            const unc=Math.round(mx);
            document.getElementById('uncertainty-input').value=unc;
            document.getElementById('uncertainty-slider').max=Math.max(500000,Math.round(unc*1.5));
            document.getElementById('uncertainty-slider').value=unc;
            document.getElementById('uncertainty-display').textContent=unc.toLocaleString()+'m';
            placeMarker(mecLat,mecLon); map.fitBounds(bounds,{padding:[20,20]});
        } else {
            placeMarker(lat,lon);
            // Estimate uncertainty from boundingbox when no polygon is available
            if (r.boundingbox && r.boundingbox.length === 4) {
                const RE=6371000;
                const minLat=parseFloat(r.boundingbox[0]),maxLat=parseFloat(r.boundingbox[1]);
                const minLon=parseFloat(r.boundingbox[2]),maxLon=parseFloat(r.boundingbox[3]);
                const cLat=(minLat+maxLat)/2, cLon=(minLon+maxLon)/2;
                const cosLat=Math.cos(cLat*Math.PI/180);
                const dy=(maxLat-minLat)*Math.PI/180*RE/2;
                const dx=(maxLon-minLon)*Math.PI/180*RE*cosLat/2;
                const unc=Math.round(Math.sqrt(dx*dx+dy*dy));
                if (unc > 0) {
                    document.getElementById('uncertainty-input').value=unc;
                    document.getElementById('uncertainty-slider').max=Math.max(500000,Math.round(unc*1.5));
                    document.getElementById('uncertainty-slider').value=unc;
                    document.getElementById('uncertainty-display').textContent=unc.toLocaleString()+'m';
                }
                map.fitBounds([[minLat,minLon],[maxLat,maxLon]],{padding:[20,20]});
            } else {
                map.flyTo([lat,lon],12);
            }
        }
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
    var m = document.getElementById('map-loading');
    if (m) { m.style.display = 'flex'; }
}
function hideOverlay() {
    var o = document.getElementById('panel-overlay');
    if (o) { o.style.display = 'none'; }
    var m = document.getElementById('map-loading');
    if (m) { m.style.display = 'none'; }
}

function toggleCorrectSuggestion(id, checked) {
    if (checked) _correctSuggestionIds.add(id); else _correctSuggestionIds.delete(id);
    updateSubmitBtn();
}
function toggleCorrectGbif(ids, checked) {
    ids.forEach(function(id){ if (checked) _correctGbifOccurrenceIds.add(id); else _correctGbifOccurrenceIds.delete(id); });
    updateSubmitBtn();
}

function updateSubmitBtn() {
    var voteableIds = _currentSuggestions.filter(function(s){ return !s.is_own; }).map(function(s){ return s.id; });
    var allVoted = voteableIds.length > 0 && voteableIds.every(function(id){ return pendingVotes[id]; });
    var hasPoint = !!marker;
    var hasDisagree = Object.values(pendingVotes).some(function(v){ return v === 'disagree'; });
    var hasCorrection = !_isAllGeoref || _correctGbifOccurrenceIds.size > 0 || _correctSuggestionIds.size > 0 || hasDisagree;
    var enabled = ((georefMode === 'vote' && allVoted) || (georefMode === 'new' && hasPoint)) && hasCorrection;
    var btn = document.getElementById('submit-btn');
    btn.disabled = !enabled;
    btn.title = (!enabled && _isAllGeoref && !hasCorrection)
        ? '{{ __("Check \"Correct georef. occurrences\" on at least one card to apply your correction.") }}'
        : '';
    var hint = document.getElementById('submit-hint');
    if (hint) hint.style.display = (_isAllGeoref && !hasCorrection) ? 'block' : 'none';
    var ms = document.getElementById('mob-submit-btn'); if(ms){ ms.disabled=!enabled; ms.style.opacity=enabled?'1':'0.4'; }
}

function initVotingMode(suggestions) {
    _currentSuggestions = suggestions || [];
    pendingVotes = {};
    // Restore previously cast votes from the server
    _currentSuggestions.forEach(function(s) {
        if (s.my_vote) pendingVotes[s.id] = s.my_vote;
    });
    var voteableSuggestions = _currentSuggestions.filter(function(s){ return !s.is_own; });
    if (voteableSuggestions.length > 0) {
        georefMode = 'vote';
        var wrap = document.getElementById('mode-toggle-wrap');
        if (wrap) wrap.style.display = 'block';
        var hint = document.getElementById('map-click-hint');
        if (hint) hint.style.display = 'none';
        var btn = document.getElementById('mode-toggle-btn');
        if (btn) btn.textContent = '+ {{ __("Submit a different point") }}';
    } else {
        georefMode = 'new';
        var wrap = document.getElementById('mode-toggle-wrap');
        if (wrap) wrap.style.display = 'none';
        var hint = document.getElementById('map-click-hint');
        if (hint) hint.style.display = 'block';
    }
    updateSubmitBtn();
}

function toggleVote(id, vote) {
    if (pendingVotes[id] === vote) {
        delete pendingVotes[id];
    } else {
        pendingVotes[id] = vote;
        if (vote === 'agree') {
            _currentSuggestions.forEach(function(s) {
                if (s.id !== id && !s.is_own) pendingVotes[s.id] = 'disagree';
            });
        }
    }
    renderVoteButtonStates();
    updateSubmitBtn();
}

function renderVoteButtonStates() {
    _currentSuggestions.forEach(function(s) {
        var agreeBtn = document.getElementById('agree-btn-'+s.id);
        var disagreeBtn = document.getElementById('disagree-btn-'+s.id);
        if (!agreeBtn || !disagreeBtn) return;
        var vote = pendingVotes[s.id];
        agreeBtn.style.background    = vote === 'agree'    ? '#16a34a' : '#f0fdf4';
        agreeBtn.style.color         = vote === 'agree'    ? '#ffffff' : '#16a34a';
        disagreeBtn.style.background = vote === 'disagree' ? '#ef4444' : '#fff1f2';
        disagreeBtn.style.color      = vote === 'disagree' ? '#ffffff' : '#ef4444';
    });
}

function togglePointMode() {
    if (georefMode === 'vote') {
        // Activate new point mode
        georefMode = 'new';
        _currentSuggestions.forEach(function(s) { if (!s.is_own) pendingVotes[s.id] = 'disagree'; });
        renderVoteButtonStates();
        var btn = document.getElementById('mode-toggle-btn');
        if (btn) btn.textContent = '← {{ __("Back to voting") }}';
        var hint = document.getElementById('map-click-hint');
        if (hint) hint.style.display = 'block';
    } else {
        // Deactivate new point mode — clear marker, go back to vote
        georefMode = 'vote';
        resetPoint();
        pendingVotes = {};
        renderVoteButtonStates();
        var btn = document.getElementById('mode-toggle-btn');
        if (btn) btn.textContent = '+ {{ __("Submit a different point") }}';
        var hint = document.getElementById('map-click-hint');
        if (hint) hint.style.display = 'none';
    }
    updateSubmitBtn();
}

function resetPoint() {
    if(marker){map.removeLayer(marker);marker=null;}
    if(circle){map.removeLayer(circle);circle=null;}
    if(radiusHandle){map.removeLayer(radiusHandle);radiusHandle=null;}
    document.getElementById('lat-input').value='';
    document.getElementById('lng-input').value='';
    document.getElementById('uncertainty-display').textContent='';
    updateSubmitBtn();
}

function showVoteModeToast() {
    var toast = document.getElementById('vote-mode-toast');
    if (!toast) return;
    toast.style.opacity = '1'; toast.style.pointerEvents = 'auto';
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(function(){ toast.style.opacity='0'; toast.style.pointerEvents='none'; }, 3500);
    var btn = document.getElementById('mode-toggle-btn');
    if (btn) {
        btn.classList.remove('btn-flash');
        void btn.offsetWidth; // reflow to restart animation
        btn.classList.add('btn-flash');
    }
}

function clearPanel() {
    showOverlay();
    if(marker){map.removeLayer(marker);marker=null;} if(circle){map.removeLayer(circle);circle=null;} if(radiusHandle){map.removeLayer(radiusHandle);radiusHandle=null;}
    if(window._nominatimPolygon){map.removeLayer(window._nominatimPolygon);window._nominatimPolygon=null;}
    clearSuggestionLayers(); closeImgViewer();
    document.getElementById('submit-btn').disabled=true;
    var ms=document.getElementById('mob-submit-btn'); if(ms){ms.disabled=true;ms.style.opacity='0.4';}
    pendingVotes={}; georefMode='new'; _currentSuggestions=[]; _correctSuggestionIds=new Set(); _correctGbifOccurrenceIds=new Set();
    var _dsr=document.getElementById('dismiss-system-row'); if(_dsr) _dsr.style.display='none';
    var wrap=document.getElementById('mode-toggle-wrap'); if(wrap) wrap.style.display='none';
    var hint=document.getElementById('map-click-hint'); if(hint) hint.style.display='block';
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
    var sgw=document.getElementById('similar-groups-wrap'); if(sgw) sgw.style.display='none';
    var sgl=document.getElementById('similar-groups-list'); if(sgl) sgl.innerHTML='';
}

function loadNextGroup() {
    clearPanel();
    var parts = [];
    if (window._georefFocus) parts.push('focus=' + encodeURIComponent(window._georefFocus));
    if (window._georefCountry) parts.push('country=' + encodeURIComponent(window._georefCountry));
    if (currentGroup) parts.push('exclude=' + currentGroup.id);
    fetch(APP_URL+'/georef/next?' + parts.join('&'), {headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(function(r){ if(!r.ok) throw new Error(r.status); return r.json(); })
    .then(data=>{
        hideOverlay();
        document.getElementById('occurrence-loading').classList.add('hidden');
        if(data.group){
            addToHistory(data.group);
            currentGroup=data.group;
            renderGroup(data.group,data.occurrences,data.ungeoref_total||0,data.georef_occurrences||[],data.suggestions,data.comments,data.similar_groups||[]);
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
    .catch(()=>{ window.location.reload(); });
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
            renderGroup(data.group,data.occurrences,data.ungeoref_total||0,data.georef_occurrences||[],data.suggestions,data.comments,data.similar_groups||[]);
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
    var _ungeorefTotal = 0;
    var _ungeorefLoaded = 0;
    var _correctSuggestionIds = new Set();
    var _correctGbifOccurrenceIds = new Set();
    var _isAllGeoref = false;

    var _hasSuggestionColor = null;

    var _statusBadge = {
        'gbif_georeferenced': ['#6b7280','georeferenced'],
        'gbif_reviewed':      ['#16a34a','georeferenced ✓'],
        'validated':          ['#16a34a','validated ✓'],
        'has_suggestion':     ['#f59e0b','has suggestion'],
        'conflicted':         ['#ef4444','conflicted'],
        'ungeoreferenced':    ['#d1d5db','not georef'],
    };

    function renderOccRowHtml(o, showCheckbox) {
        var hasSugColor = _hasSuggestionColor || '#f59e0b';
        var badges = Object.assign({}, _statusBadge, {'has_suggestion': [hasSugColor,'has suggestion']});
        var label=[o.recorded_by,o.event_date].filter(Boolean).join(' · ')||o.gbif_occurrence_key;
        var taxon=o.scientific_name||'', meta=[o.institution_code,o.collection_code].filter(Boolean).join(' · ');
        var bv = badges[o.georef_status] || ['#d1d5db','—'];
        var badgeColor=bv[0], badgeLabel=bv[1];
        var media = null;
        if (o.media && o.media.length > 0) {
            media = o.media.find(function(m){
                return m.identifier && !m.identifier.includes('manifest') &&
                    (/\.(jpg|jpeg|png|gif|webp|tif|tiff)(\?.*)?$/i.test(m.identifier)||(m.format&&m.format.startsWith('image/')));
            }) || o.media[0];
        }
        var badge='<span style="flex-shrink:0;font-size:9px;font-weight:600;padding:1px 4px;border-radius:3px;background:'+badgeColor+'20;color:'+badgeColor+';border:1px solid '+badgeColor+'40;white-space:nowrap">'+badgeLabel+'</span>';
        var imgBtn='';
        if(media){
            var isDirectImg = media.identifier && !media.identifier.includes('manifest') &&
                (/\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i.test(media.identifier)||(media.format&&media.format.startsWith('image/')));
            var btnStyle='flex-shrink:0;width:28px;height:28px;border-radius:4px;overflow:hidden;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;background:#f9fafb;';
            var btnContent = isDirectImg
                ? '<img src="'+media.identifier+'" style="width:28px;height:28px;object-fit:cover" loading="lazy" onerror="this.style.display=\'none\';this.parentElement.innerHTML=\'📷\';this.parentElement.style.fontSize=\'14px\'">'
                : '<span style="font-size:14px" title="{{ __("View specimen image") }}">📷</span>';
            imgBtn='<button class="img-btn" style="'+btnStyle+'" data-src="'+media.identifier+'" data-title="'+(media.title||'').replace(/"/g,'&quot;')+'" data-link="'+media.identifier+'">'+btnContent+'</button>';
        }
        var cbHtml = showCheckbox ? '<input type="checkbox" class="occurrence-checkbox" value="'+o.id+'" checked style="flex-shrink:0;margin-top:2px">' : '';
        return '<div class="occ-row" data-institution="'+(o.institution_code||'')+'" style="font-size:11px;border-radius:4px;border:1px solid transparent;padding:2px 0">'+
            '<div style="display:flex;align-items:flex-start;gap:6px;padding:4px 6px">'+
            cbHtml+
            '<div style="flex:1;min-width:0">'+
            (taxon?'<div class="dark-text" style="font-style:italic;word-break:break-word;line-height:1.2">'+taxon+'</div>':'')+
            '<div style="color:#9ca3af;word-break:break-word">'+label+'</div>'+
            (meta?'<div style="color:#9ca3af">'+meta+'</div>':'')+
            '</div>'+badge+
            '<a href="https://www.gbif.org/occurrence/'+o.gbif_occurrence_key+'" target="_blank" style="color:#16a34a;flex-shrink:0;text-decoration:none;font-size:10px;white-space:nowrap">GBIF ↗</a>'+
            imgBtn+'</div></div>';
    }

    function renderOccurrenceRows(occurrences, append) {
        var html = occurrences.map(function(o){ return renderOccRowHtml(o, true); }).join('');
        var list = document.getElementById('occurrences-list');
        if (append) { list.insertAdjacentHTML('beforeend', html); }
        else { list.innerHTML = html; }
        list.querySelectorAll('.img-btn').forEach(function(btn){
            btn.addEventListener('click',function(e){e.stopPropagation();openImgViewer(this.dataset.src,this.dataset.title,this.dataset.link);});
        });
    }

    function occSelectAll(checked) {
        document.querySelectorAll('#occurrences-list .occurrence-checkbox').forEach(function(cb){ cb.checked = checked; });
    }
    function occSelectByInstitution(code) {
        document.querySelectorAll('#occurrences-list .occ-row').forEach(function(row){
            var cb = row.querySelector('.occurrence-checkbox');
            if (!cb) return;
            cb.checked = !code || row.dataset.institution === code;
        });
    }

    function loadMoreUngeoref() {
        if (!currentGroup) return;
        fetch(APP_URL+'/georef/group/'+currentGroup.id+'/ungeoref-occurrences?offset='+_ungeorefLoaded,
            {headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
        .then(r=>r.json()).then(function(d){
            if (!d.occurrences || !d.occurrences.length) return;
            _currentOccurrences = _currentOccurrences.concat(d.occurrences);
            _ungeorefLoaded += d.occurrences.length;
            renderOccurrenceRows(d.occurrences, true);
            updateLoadMoreBtn();
        });
    }

    function updateLoadMoreBtn() {
        var btn = document.getElementById('load-more-occ-btn');
        if (!btn) return;
        var remaining = _ungeorefTotal - _ungeorefLoaded;
        if (remaining > 0) {
            btn.style.display = 'block';
            btn.textContent = '{{ __("Load more") }} (' + remaining + ')';
        } else {
            btn.style.display = 'none';
        }
    }

    // Occurrence popup (for suggestion georef occurrences)
    var _occPopupOffset = 0;
    var _occPopupTotal = 0;
    var _occPopupSuggId = null;
    var _occPopupGroupId = null;
    var _occPopupIds = [];

    function openGroupOccPopup(groupId, count) {
        _occPopupSuggId = null;
        _occPopupGroupId = groupId;
        _occPopupOffset = 0;
        _occPopupTotal = count;
        _occPopupIds = [];
        document.getElementById('occ-popup').style.display = 'flex';
        document.getElementById('occ-popup-list').innerHTML = '<p style="color:#9ca3af;font-size:11px;padding:8px">{{ __("Loading...") }}</p>';
        document.getElementById('occ-popup-loadmore').style.display = 'none';
        fetchOccPopupPage(true);
    }

    function openOccPopup(suggId, count, ids) {
        _occPopupGroupId = null;
        _occPopupSuggId = suggId;
        _occPopupOffset = 0;
        _occPopupTotal = count;
        _occPopupIds = ids || [];
        document.getElementById('occ-popup').style.display = 'flex';
        document.getElementById('occ-popup-list').innerHTML = '<p style="color:#9ca3af;font-size:11px;padding:8px">{{ __("Loading...") }}</p>';
        document.getElementById('occ-popup-loadmore').style.display = 'none';
        fetchOccPopupPage(true);
    }

    function openGbifOccPopup(ids) {
        _occPopupSuggId = null;
        _occPopupGroupId = null;
        _occPopupOffset = 0;
        _occPopupTotal = ids.length;
        _occPopupIds = ids;
        document.getElementById('occ-popup').style.display = 'flex';
        document.getElementById('occ-popup-list').innerHTML = '<p style="color:#9ca3af;font-size:11px;padding:8px">{{ __("Loading...") }}</p>';
        document.getElementById('occ-popup-loadmore').style.display = 'none';
        fetchOccPopupPage(true);
    }

    function fetchOccPopupPage(reset) {
        var url;
        if (_occPopupGroupId) {
            url = APP_URL+'/georef/group/'+_occPopupGroupId+'/ungeoref-occurrences?offset='+_occPopupOffset;
        } else {
            var pageIds = _occPopupIds.slice(_occPopupOffset, _occPopupOffset + 100);
            url = _occPopupSuggId
                ? APP_URL+'/georef/suggestion/'+_occPopupSuggId+'/georef-occurrences?ids='+pageIds.join(',')
                : APP_URL+'/georef/occurrences-by-ids?ids='+pageIds.join(',');
        }
        fetch(url,
            {headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
        .then(r=>r.json()).then(function(d){
            var rows = (d.occurrences||[]).map(function(o){ return renderOccRowHtml(o, false); }).join('');
            var list = document.getElementById('occ-popup-list');
            if (reset) {
                list.innerHTML = rows;
                list.querySelectorAll('.img-btn').forEach(function(btn){
                    btn.addEventListener('click',function(e){e.stopPropagation();openImgViewer(this.dataset.src,this.dataset.title,this.dataset.link);});
                });
            } else {
                list.insertAdjacentHTML('beforeend', rows);
                list.querySelectorAll('.img-btn:not([data-bound])').forEach(function(btn){
                    btn.dataset.bound='1';
                    btn.addEventListener('click',function(e){e.stopPropagation();openImgViewer(this.dataset.src,this.dataset.title,this.dataset.link);});
                });
            }
            _occPopupOffset += 100;
            var btn = document.getElementById('occ-popup-loadmore');
            btn.style.display = _occPopupOffset < _occPopupTotal ? 'block' : 'none';
            if (_occPopupOffset < _occPopupTotal)
                btn.textContent = '{{ __("Load more") }} (' + (_occPopupTotal - _occPopupOffset) + ')';
        });
    }

    var _simSuggMap = {};

    function renderSimilarGroups(groups) {
        const wrap = document.getElementById('similar-groups-wrap');
        const list = document.getElementById('similar-groups-list');
        _simSuggMap = {};
        if (!groups || !groups.length) { wrap.style.display = 'none'; list.innerHTML = ''; return; }
        wrap.style.display = 'block';
        list.innerHTML = groups.map(function(g) {
            const statusBits = [];
            if (g.validated_count > 0)       statusBits.push('<span style="color:#16a34a">'+g.validated_count+' validated</span>');
            if (g.pending_count > 0)         statusBits.push('<span style="color:#f59e0b">'+g.pending_count+' pending</span>');
            if (g.ungeoreferenced_count > 0) statusBits.push('<span style="color:#9ca3af">'+g.ungeoreferenced_count+' ungeoref</span>');

            const locationParts = [g.municipality, g.county, g.state_province, g.country_code].filter(Boolean);
            const locationHtml = locationParts.length
                ? '<div style="font-size:10px;color:#9ca3af;margin-top:1px">'+escHtml(locationParts.join(', '))+'</div>'
                : '';

            const suggHtml = (g.suggestions && g.suggestions.length) ? g.suggestions.map(function(s) {
                const key = 'ss_'+s.id;
                _simSuggMap[key] = s;
                const uncM = s.coordinate_uncertainty_m ? Math.round(s.coordinate_uncertainty_m) : 0;
                const remarksHtml = (!s.is_system && s.georeference_remarks)
                    ? '<span class="remarks-btn" data-remarks="'+escHtml(s.georeference_remarks).replace(/'/g,'&#39;')+'" style="cursor:pointer;font-size:9px;font-weight:600;padding:1px 5px;border-radius:3px;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;white-space:nowrap;flex-shrink:0;">remarks</span>'
                    : '';
                return '<div class="sugg-card" style="font-size:11px;border-radius:6px;padding:8px;margin-top:4px;">'
                    + '<div style="display:flex;justify-content:space-between">'
                    + '<span class="dark-text" style="font-weight:500">'+parseFloat(s.decimal_latitude).toFixed(5)+', '+parseFloat(s.decimal_longitude).toFixed(5)+'</span>'
                    + '<span style="color:#9ca3af">±'+uncM+'m</span>'
                    + '</div>'
                    + '<div style="display:flex;justify-content:space-between;margin-top:4px;color:#9ca3af">'
                    + '<span style="display:flex;align-items:center;gap:5px;">'+(s.submitted_by||'System')+remarksHtml+'</span>'
                    + '</div>'
                    + '<div style="display:flex;gap:8px;margin-top:6px;align-items:center;">'
                    + '<button onclick="previewSuggestion('+parseFloat(s.decimal_latitude)+','+parseFloat(s.decimal_longitude)+','+uncM+')" style="color:#3b82f6;background:none;border:none;cursor:pointer;font-size:10px;padding:0">'+TXT.previewMap+'</button>'
                    + '<button onclick="useSimilarSuggestion(\''+key+'\')" class="use-similar-btn" style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:4px;cursor:pointer;">Use this</button>'
                    + '</div>'
                    + '</div>';
            }).join('') : '';

            return '<div style="border:1px solid #fed7aa;border-radius:6px;padding:6px 8px;background:#fff7ed;">'
                + '<label style="display:flex;align-items:flex-start;gap:6px;cursor:pointer;">'
                + '<input type="checkbox" class="similar-group-cb" data-id="'+g.id+'" checked '
                + 'style="margin-top:2px;flex-shrink:0;accent-color:#ea580c;">'
                + '<div style="min-width:0;flex:1;">'
                + '<div style="display:flex;align-items:center;gap:4px;">'
                + '<span style="font-size:10px;font-weight:600;color:#c2410c;word-break:break-word">'+escHtml(g.verbatim_locality || '—')+'</span>'
                + '<span title="{{ __("Checking this will create a new pending suggestion for this location group with the same coordinates. It will need to be validated by other members before being applied to any occurrences.") }}" style="display:inline-flex;align-items:center;justify-content:center;width:13px;height:13px;border-radius:50%;background:#d1d5db;color:#374151;font-size:9px;font-weight:700;cursor:help;flex-shrink:0;">i</span>'
                + '</div>'
                + locationHtml
                + '<div style="font-size:10px;color:#6b7280;margin-top:1px;display:flex;align-items:center;gap:6px;">'
                + (statusBits.join(' · ') || '')
                + '<button onclick="openGroupOccPopup('+g.id+','+g.occurrence_count+')" style="margin-left:auto;font-size:10px;color:#3b82f6;background:none;border:none;cursor:pointer;padding:0;flex-shrink:0;">{{ __("see list") }} ↗</button>'
                + '</div>'
                + '</div>'
                + '</label>'
                + suggHtml
                + '</div>';
        }).join('');

        list.querySelectorAll('.remarks-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var existing = document.getElementById('remarks-popup');
                if (existing) existing.remove();
                var popup = document.createElement('div');
                popup.id = 'remarks-popup';
                popup.style.cssText = 'position:fixed;z-index:9999;background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:8px 10px;font-size:11px;color:#78350f;max-width:240px;box-shadow:0 4px 12px rgba(0,0,0,0.15);line-height:1.5;';
                popup.textContent = btn.dataset.remarks;
                document.body.appendChild(popup);
                var r = btn.getBoundingClientRect();
                popup.style.left = Math.min(r.left, window.innerWidth - popup.offsetWidth - 8) + 'px';
                popup.style.top  = (r.bottom + 4) + 'px';
                var close = function(){ popup.remove(); document.removeEventListener('click', close); };
                setTimeout(function(){ document.addEventListener('click', close); }, 0);
            });
        });
    }

    function useSimilarSuggestion(key) {
        const s = _simSuggMap[key];
        if (!s) return;
        // Pre-fill the georef form with coords+uncertainty from the similar group's suggestion
        const lat = parseFloat(s.decimal_latitude);
        const lng = parseFloat(s.decimal_longitude);
        placeMarker(lat, lng);
        if (s.coordinate_uncertainty_m) {
            const uncInput = document.getElementById('uncertainty-input');
            if (uncInput) { uncInput.value = Math.round(s.coordinate_uncertainty_m); uncInput.dispatchEvent(new Event('input')); }
        }
        const remarksInput = document.getElementById('remarks-input');
        if (remarksInput && s.georeference_remarks) remarksInput.value = s.georeference_remarks;
        // Switch to point-submission mode if currently in voting mode
        if (georefMode !== 'new') {
            const modeBtn = document.getElementById('mode-toggle-btn');
            if (modeBtn) modeBtn.click();
        }
        updateSubmitBtn();
        // Scroll to form
        const formWrap = document.querySelector('.p-4.overflow-y-auto');
        if (formWrap) formWrap.scrollIntoView({behavior:'smooth',block:'nearest'});
    }

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderGroup(group, occurrences, ungeorefTotal, georefOccurrences, suggestions, comments, similarGroups) {
        hideFocusDropdown();
        document.getElementById('occurrence-info').classList.remove('hidden');
        _currentOccurrences = occurrences;
        _ungeorefTotal = ungeorefTotal;
        _ungeorefLoaded = occurrences.length;
        _correctSuggestionIds = new Set();
        _correctGbifOccurrenceIds = new Set();
        _isAllGeoref = (ungeorefTotal === 0 && georefOccurrences.length > 0);
        const fieldDefs = [
            {key:'verbatim_locality', label:'Locality'},
            {key:'municipality',      label:'Municipality'},
            {key:'county',            label:'County'},
            {key:'state_province',    label:'State / Province'},
            {key:'island',            label:'Island'},
            {key:'island_group',      label:'Island group'},
            {key:'water_body',        label:'Water body'},
            {key:'continent',         label:'Continent'},
            {key:'higher_geography',  label:'Higher geography'},
            {key:'location_remarks',  label:'Location remarks'},
            {key:'country_code',      label:'Country'},
        ];
        document.getElementById('locality-fields').innerHTML=fieldDefs
            .filter(d=>group[d.key])
            .map(d=>
                '<div style="margin-bottom:5px">'+
                '<div style="color:#9ca3af;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;font-weight:500">'+d.label+'</div>'+
                '<div class="dark-text" style="font-size:12px;font-weight:500;line-height:1.3;word-break:break-word">'+group[d.key]+'</div>'+
                '</div>'
            ).join('');
        document.getElementById('nominatim-input').value=buildLocalityString(group);
        document.getElementById('nominatim-results').innerHTML='';

        var allGeoref = ungeorefTotal === 0 && georefOccurrences.length > 0;
        document.getElementById('occ-panel-label').textContent = allGeoref
            ? '{{ __("Georeferenced occurrences") }}'
            : '{{ __("Occurrences without coordinates") }}';
        document.getElementById('occ-panel-hint').style.display = allGeoref ? 'none' : '';
        document.getElementById('occ-select-controls').classList.add('hidden');

        var countLabel = allGeoref
            ? georefOccurrences.length + ' {{ __("with GBIF coordinates") }}'
            : ungeorefTotal + (ungeorefTotal > occurrences.length ? ' {{ __("total, showing") }} '+occurrences.length : '');
        document.getElementById('occurrence-count').textContent = countLabel;

        const clusterColors = ['#3b82f6','#f97316','#a855f7','#06b6d4','#22c55e','#ec4899','#eab308','#6366f1','#14b8a6','#f43f5e'];

        // has_suggestion badge: use suggestion color only if exactly one suggestion
        _hasSuggestionColor = (suggestions && suggestions.length === 1) ? clusterColors[0] : '#f59e0b';

        clearSuggestionLayers();

        // Place georef occurrences on map as read-only markers, one color per unique coord
        var _gbifCoordColors = {};
        var _gbifColorIdx = 0;
        georefOccurrences.forEach(function(o){
            if (!o.gbif_decimal_latitude) return;
            var key = parseFloat(o.gbif_decimal_latitude).toFixed(5)+','+parseFloat(o.gbif_decimal_longitude).toFixed(5);
            if (!_gbifCoordColors[key]) _gbifCoordColors[key] = clusterColors[_gbifColorIdx++ % clusterColors.length];
            var m = L.circleMarker([o.gbif_decimal_latitude, o.gbif_decimal_longitude],
                {radius:5,color:_gbifCoordColors[key],fillColor:_gbifCoordColors[key],fillOpacity:0.5,weight:1})
                .bindTooltip((o.scientific_name||o.gbif_occurrence_key||'')+'<br>'+parseFloat(o.gbif_decimal_latitude).toFixed(4)+', '+parseFloat(o.gbif_decimal_longitude).toFixed(4),{permanent:false})
                .addTo(map);
            window._suggestionLayers.push(m);
        });

        if (allGeoref) {
            // Left panel: simple message, occurrences accessible via "see list" on each card
            document.getElementById('occ-panel-label').textContent = '{{ __("Georeferenced occurrences") }}';
            document.getElementById('occ-panel-hint').style.display = 'none';
            document.getElementById('occurrences-list').innerHTML =
                '<p style="font-size:11px;color:#9ca3af;font-style:italic;padding:4px 0">' +
                georefOccurrences.length + ' {{ __("occurrence(s) already georeferenced by GBIF. See each card to browse them.") }}' +
                '</p>';
            document.getElementById('load-more-occ-btn').style.display = 'none';

            // Build per-coord occurrence ID lists for "see list" button
            var gbifCoordIds = {};
            var gbifCounts = {};
            georefOccurrences.forEach(function(o) {
                if (!o.gbif_decimal_latitude) return;
                var key = parseFloat(o.gbif_decimal_latitude).toFixed(5) + ',' + parseFloat(o.gbif_decimal_longitude).toFixed(5);
                if (!gbifCoordIds[key]) gbifCoordIds[key] = [];
                gbifCoordIds[key].push(o.id);
                gbifCounts[key] = (gbifCounts[key] || 0) + 1;
            });

            var gbifSeen = {};
            var gbifHtml = '';
            georefOccurrences.forEach(function(o) {
                if (!o.gbif_decimal_latitude) return;
                var key = parseFloat(o.gbif_decimal_latitude).toFixed(5) + ',' + parseFloat(o.gbif_decimal_longitude).toFixed(5);
                if (gbifSeen[key]) return;
                gbifSeen[key] = true;
                var cnt = gbifCounts[key];
                var ids = gbifCoordIds[key];
                var lat = parseFloat(o.gbif_decimal_latitude);
                var lng = parseFloat(o.gbif_decimal_longitude);
                var color = _gbifCoordColors[key] || clusterColors[0];
                var dot = '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:'+color+';flex-shrink:0;margin-top:2px"></span>';
                gbifHtml += '<div class="sugg-card" style="font-size:11px;border-radius:6px;padding:8px;margin-bottom:4px">' +
                    '<div style="display:flex;align-items:flex-start;gap:4px">' + dot +
                    '<div style="flex:1">' +
                    '<div style="display:flex;justify-content:space-between">' +
                    '<span class="dark-text" style="font-weight:500">' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</span>' +
                    '<span style="color:#9ca3af">'+cnt+' {{ __("occ.") }}</span>' +
                    '</div>' +
                    '<div style="display:flex;justify-content:space-between;margin-top:4px;color:#9ca3af">' +
                    '<span style="display:flex;align-items:center;gap:5px;">GBIF</span>' +
                    '<div style="display:flex;gap:8px"><span style="font-size:10px;color:#9ca3af;font-style:italic">{{ __("Georeferenced by GBIF") }}</span></div>' +
                    '</div>' +
                    '<div class="sugg-bar-bg" style="border-radius:4px;height:4px;margin-top:6px"><div style="background:'+color+';height:4px;border-radius:4px;width:100%"></div></div>' +
                    '<button onclick="previewSuggestion('+lat+','+lng+',0)" style="color:#3b82f6;background:none;border:none;cursor:pointer;font-size:10px;margin-top:4px;padding:0">'+TXT.previewMap+'</button>' +
                    '<div class="sugg-divider" style="display:flex;align-items:center;gap:6px;margin-top:6px;padding-top:6px;">' +
                    '<label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:10px;color:#6b7280;">' +
                    '<input type="checkbox" id="correct-gbif-chk-'+key.replace(',','-')+'" onchange="toggleCorrectGbif('+JSON.stringify(ids)+',this.checked)" style="cursor:pointer;">' +
                    '{{ __("Correct") }} '+cnt+' {{ __("georef. occurrences") }}' +
                    '</label>' +
                    '<button onclick="openGbifOccPopup('+JSON.stringify(ids)+')" style="margin-left:auto;font-size:10px;color:#3b82f6;background:none;border:none;cursor:pointer;padding:0">{{ __("see list") }} ↗</button>' +
                    '</div>' +
                    '</div></div></div>';
            });
            document.getElementById('suggestions-list').innerHTML = gbifHtml ||
                '<p style="font-size:11px;color:#9ca3af;font-style:italic;padding:4px 0">{{ __("No coordinates available.") }}</p>';
        } else {
            renderOccurrenceRows(occurrences, false);
            updateLoadMoreBtn();
        }

        // Populate institution filter and show batch controls
        var instSel = document.getElementById('inst-filter');
        if (instSel) {
            var allOccs = occurrences || [];
            var instCodes = [];
            allOccs.forEach(function(o){ if (o.institution_code && instCodes.indexOf(o.institution_code) === -1) instCodes.push(o.institution_code); });
            instSel.innerHTML = '<option value="">{{ __("All institutions") }}</option>' +
                instCodes.map(function(c){ return '<option value="'+c+'">'+c+'</option>'; }).join('');
        }
        var ctrl = document.getElementById('occ-select-controls');
        if (ctrl && occurrences && occurrences.length > 0) ctrl.classList.remove('hidden');

        initVotingMode(suggestions);
        if (suggestions&&suggestions.length>0) {
            const colors=clusterColors;
            var pillBase = 'font-size:11px;padding:2px 10px;border-radius:999px;border:1px solid;cursor:pointer;font-weight:500;transition:background 0.15s,color 0.15s;';
            var sugHtml='';
            suggestions.forEach(function(s,i){
                var color=colors[i%colors.length];
                var c=L.circle([s.decimal_latitude,s.decimal_longitude],{radius:s.coordinate_uncertainty_m||1000,color:color,fillColor:color,fillOpacity:0.1,weight:2,dashArray:'6'}).addTo(map);
                var m=L.circleMarker([s.decimal_latitude,s.decimal_longitude],{radius:6,color:color,fillColor:color,fillOpacity:0.8,weight:2}).bindTooltip(parseFloat(s.decimal_latitude).toFixed(5)+', '+parseFloat(s.decimal_longitude).toFixed(5)+'<br>'+s.submitted_by+' · ±'+s.coordinate_uncertainty_m+'m · '+s.total_points+'pts',{permanent:false}).addTo(map);
                window._suggestionLayers.push(c,m);
                var pct=Math.min(100,(s.total_points/THRESHOLD)*100);
                var dot='<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:'+color+';flex-shrink:0;margin-top:2px"></span>';
                var valButtons = IS_AUTH
                    ? (s.is_own
                        ? '<span style="font-size:10px;color:#9ca3af;font-style:italic">{{ __("Your submission") }}</span>'+
                          '<button onclick="deleteSuggestion('+s.id+')" class="delete-sug-btn" style="font-size:10px;padding:2px 8px;border-radius:999px;cursor:pointer;">{{ __("Delete") }}</button>'
                        : '<button id="agree-btn-'+s.id+'" onclick="toggleVote('+s.id+',\'agree\')" class="vote-agree-btn" style="'+pillBase+'">'+TXT.agree+'</button>'+
                          '<button id="disagree-btn-'+s.id+'" onclick="toggleVote('+s.id+',\'disagree\')" class="vote-disagree-btn" style="'+pillBase+'">'+TXT.disagree+'</button>')
                    : '<span style="color:#9ca3af;font-style:italic;font-size:10px">'+TXT.loginToVal+'</span>';
                var correctRow = s.cluster_count > 0
                    ? '<div class="sugg-divider" style="display:flex;align-items:center;gap:6px;margin-top:6px;padding-top:6px;">'+
                      '<label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:10px;color:#6b7280;">'+
                      '<input type="checkbox" id="correct-chk-'+s.id+'" onchange="toggleCorrectSuggestion('+s.id+',this.checked)" style="cursor:pointer;">'+
                      '{{ __("Correct") }} '+s.cluster_count+' {{ __("georef. occurrences") }}'+
                      '</label>'+
                      '<button onclick="openOccPopup('+s.id+','+s.cluster_count+','+JSON.stringify(s.cluster_occurrence_ids)+')" style="margin-left:auto;font-size:10px;color:#3b82f6;background:none;border:none;cursor:pointer;padding:0;">{{ __("see list") }} ↗</button>'+
                      '</div>'
                    : '';
                sugHtml+='<div class="sugg-card" style="font-size:11px;border-radius:6px;padding:8px;margin-bottom:4px">'+
                    '<div style="display:flex;align-items:flex-start;gap:4px">'+dot+
                    '<div style="flex:1">'+
                    '<div style="display:flex;justify-content:space-between"><span class="dark-text" style="font-weight:500">'+parseFloat(s.decimal_latitude).toFixed(5)+', '+parseFloat(s.decimal_longitude).toFixed(5)+'</span><span style="color:#9ca3af">±'+s.coordinate_uncertainty_m+'m</span></div>'+
                    '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-top:4px;color:#9ca3af"><span style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">'+s.submitted_by+(!s.is_system && s.georeference_remarks?'<span class="remarks-btn" data-remarks="'+s.georeference_remarks.replace(/"/g,'&quot;').replace(/'/g,'&#39;')+'" style="cursor:pointer;font-size:9px;font-weight:600;padding:1px 5px;border-radius:3px;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;white-space:nowrap;flex-shrink:0;">remarks</span>':'')+'</span><div style="display:flex;gap:8px;flex-shrink:0;margin-left:4px;">'+valButtons+'</div></div>'+
                    '<div class="sugg-bar-bg" style="border-radius:4px;height:4px;margin-top:6px"><div style="background:'+color+';height:4px;border-radius:4px;width:'+pct+'%"></div></div>'+
                    '<button onclick="previewSuggestion('+s.decimal_latitude+','+s.decimal_longitude+','+s.coordinate_uncertainty_m+')" style="color:#3b82f6;background:none;border:none;cursor:pointer;font-size:10px;margin-top:4px;padding:0">'+TXT.previewMap+'</button>'+
                    correctRow+
                    '</div></div></div>';
            });
            document.getElementById('suggestions-list').innerHTML=sugHtml;
            // Show dismiss button only when all pending suggestions are system-generated
            var dismissRow = document.getElementById('dismiss-system-row');
            if (dismissRow) {
                var allSystem = suggestions.every(function(s){ return s.is_system; });
                dismissRow.style.display = (allSystem && _ungeorefTotal === 0) ? '' : 'none';
            }
            renderVoteButtonStates();
            document.querySelectorAll('.remarks-btn').forEach(function(btn){
                btn.addEventListener('click', function(e){
                    e.stopPropagation();
                    var existing = document.getElementById('remarks-popup');
                    if (existing) existing.remove();
                    var popup = document.createElement('div');
                    popup.id = 'remarks-popup';
                    popup.style.cssText = 'position:fixed;z-index:9999;background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:8px 10px;font-size:11px;color:#78350f;max-width:240px;box-shadow:0 4px 12px rgba(0,0,0,0.15);line-height:1.5;';
                    popup.textContent = btn.dataset.remarks;
                    document.body.appendChild(popup);
                    var r = btn.getBoundingClientRect();
                    popup.style.left = Math.min(r.left, window.innerWidth - popup.offsetWidth - 8) + 'px';
                    popup.style.top  = (r.bottom + 4) + 'px';
                    var close = function(){ popup.remove(); document.removeEventListener('click', close); };
                    setTimeout(function(){ document.addEventListener('click', close); }, 0);
                });
            });
        } else if (!allGeoref) {
            document.getElementById('suggestions-list').innerHTML='<p style="font-size:11px;color:#9ca3af;font-style:italic;padding:4px 0">{{ __("No suggestions yet for this group.") }}</p>';
            var _dr = document.getElementById('dismiss-system-row'); if (_dr) _dr.style.display = 'none';
        }

        // Restore user's previous submission if present
        var ownSugg = suggestions ? suggestions.find(function(s){ return s.is_own; }) : null;
        if (ownSugg) {
            georefMode = 'new';
            document.getElementById('lat-input').value = parseFloat(ownSugg.decimal_latitude).toFixed(7);
            document.getElementById('lng-input').value = parseFloat(ownSugg.decimal_longitude).toFixed(7);
            if (ownSugg.coordinate_uncertainty_m) document.getElementById('uncertainty-input').value = ownSugg.coordinate_uncertainty_m;
            if (ownSugg.georeference_remarks) document.getElementById('remarks-input').value = ownSugg.georeference_remarks;
            var modeBtn = document.getElementById('mode-toggle-btn');
            var modeWrap = document.getElementById('mode-toggle-wrap');
            var mapHint = document.getElementById('map-click-hint');
            if (_currentSuggestions.some(function(s){ return !s.is_own; })) {
                if (modeWrap) modeWrap.style.display = 'block';
                if (modeBtn) modeBtn.textContent = '← {{ __("Back to voting") }}';
            }
            if (mapHint) mapHint.style.display = 'block';
            placeMarker(parseFloat(ownSugg.decimal_latitude), parseFloat(ownSugg.decimal_longitude));
            updateSubmitBtn();
        }

        renderComments(comments||[]);
        renderSimilarGroups(similarGroups||[]);
        updateMobileBar(group, (suggestions||[]).length);
        var mab=document.getElementById('mob-action-bar'); if(mab) mab.classList.add('mob-loaded');
        var mrb=document.getElementById('mob-right-bar'); if(mrb) mrb.classList.add('mob-loaded');

// Always zoom to the group's administrative area for geographic context (county → state → country).
// Existing suggestion markers remain visible on the map but do not drive the initial viewport,
// since they may be incorrect and would mislead the user about the expected location.
(function zoomToGroup() {
    const county = group.county;
    const prov   = group.state_province;
    const cc     = group.country_code;
    const ccParam = cc ? '&countrycodes='+cc.toLowerCase() : '';
    const queries = [];
    if (county && prov) queries.push('county='+encodeURIComponent(county)+'&state='+encodeURIComponent(prov));
    if (county)         queries.push('county='+encodeURIComponent(county));
    if (prov)           queries.push('state='+encodeURIComponent(prov));
    if (!queries.length) {
        // Fall back to suggestion bounds if no admin area available
        if (window._suggestionLayers && window._suggestionLayers.length > 0) {
            var bounds = L.featureGroup(window._suggestionLayers).getBounds().pad(0.5);
            if (bounds.isValid()) map.fitBounds(bounds, {maxZoom: 13});
        }
        return;
    }
    function tryNext(i) {
        if (i >= queries.length) {
            // All admin queries failed — fall back to suggestion bounds
            if (window._suggestionLayers && window._suggestionLayers.length > 0) {
                var bounds = L.featureGroup(window._suggestionLayers).getBounds().pad(0.5);
                if (bounds.isValid()) map.fitBounds(bounds, {maxZoom: 13});
            }
            return;
        }
        fetch('https://nominatim.openstreetmap.org/search?'+queries[i]+ccParam+'&format=json&limit=1&polygon_geojson=0', {headers:{'Accept-Language':'en'}})
            .then(r=>r.json()).then(res=>{
                if (!res.length) { tryNext(i+1); return; }
                const bb = res[0].boundingbox;
                map.fitBounds([[parseFloat(bb[0]),parseFloat(bb[2])],[parseFloat(bb[1]),parseFloat(bb[3])]],{maxZoom:13,padding:[20,20]});
            }).catch(()=>tryNext(i+1));
    }
    tryNext(0);
})();
    }

    function renderComments(comments) {
        document.getElementById('comments-list').innerHTML=comments.map(function(c){
            return '<div style="font-size:11px;border-bottom:1px solid #f3f4f6;padding-bottom:4px"><span style="font-weight:500">'+c.user_name+'</span><span style="color:#9ca3af;margin-left:4px">'+c.created_at+'</span><p style="color:#6b7280;margin-top:2px">'+c.body+'</p></div>';
        }).join('');
    }
    function previewSuggestion(lat,lng,unc) {
        if(marker){map.removeLayer(marker);marker=null;} if(circle){map.removeLayer(circle);circle=null;} if(radiusHandle){map.removeLayer(radiusHandle);radiusHandle=null;}
        if(unc) {
            circle=L.circle([lat,lng],{radius:unc,color:'#3b82f6',fillColor:'#3b82f6',fillOpacity:0.1,weight:2,dashArray:'6'}).addTo(map);
            map.fitBounds(circle.getBounds(),{padding:[30,30]});
        } else {
            map.flyTo([lat,lng],12);
        }
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

        // Send pending votes
        var votePromises = Object.keys(pendingVotes).map(function(id){
            var vote = pendingVotes[id];
            var hasCompeting = _currentSuggestions.length > 1;
            var url = (vote==='agree' && hasCompeting) ? APP_URL+'/georef/agree-with/'+id : APP_URL+'/georef/validate/'+id;
            var bodyObj = (vote==='agree' && hasCompeting) ? {} : {vote: vote};
            if (vote === 'agree') {
                var sug = _currentSuggestions.find(function(s){ return s.id == id; });
                if (sug && sug.cluster_occurrence_ids && sug.cluster_occurrence_ids.length > 0 && !_correctSuggestionIds.has(parseInt(id))) {
                    bodyObj.excluded_occurrence_ids = sug.cluster_occurrence_ids;
                }
            }
            return fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(bodyObj)});
        });

        Promise.all(votePromises).then(function(){
            // If new point mode and marker placed, submit georef
            if(georefMode==='new' && marker){
                var excl=Array.from(document.querySelectorAll('.occurrence-checkbox:not(:checked)')).map(function(c){return c.value;});
                var simIds=Array.from(document.querySelectorAll('.similar-group-cb:checked')).map(function(c){return parseInt(c.dataset.id,10);});
                return fetch(APP_URL+'/georef/submit',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},
                    body:JSON.stringify({locality_group_id:currentGroup.id,decimal_latitude:document.getElementById('lat-input').value,decimal_longitude:document.getElementById('lng-input').value,coordinate_uncertainty_m:document.getElementById('uncertainty-input').value,georeference_remarks:document.getElementById('remarks-input').value,anon_name:document.getElementById('anon-name')?document.getElementById('anon-name').value:null,excluded_occurrence_ids:excl,correct_suggestion_ids:Array.from(_correctSuggestionIds),correct_occurrence_ids:Array.from(_correctGbifOccurrenceIds),similar_group_ids:simIds})})
                    .then(r=>r.json());
            }
            return {success:true};
        }).then(function(d){
            btn.innerHTML='{{ __("Submit") }}';
            if(!d||d.success)loadNextGroup();
            else btn.disabled=false;
        }).catch(function(){ btn.innerHTML='{{ __("Submit") }}'; btn.disabled=false; });
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
var urlCountry = urlParams.get('country');
if (urlCountry) window._georefCountry = urlCountry.toUpperCase();
if(urlGbifKey) {
    loadByGbifKey(urlGbifKey);
} else if(urlGroupId) {
    var existingIdx = sessionHistory.findIndex(function(g){ return g.id === parseInt(urlGroupId); });
    if(existingIdx !== -1) { historyIndex = existingIdx; updateHistoryNav(); }
    loadGroup(parseInt(urlGroupId));
} else if(!urlCountry && sessionHistory.length > 0 && historyIndex >= 0 && historyIndex < sessionHistory.length) {
    loadGroup(sessionHistory[historyIndex].id);
} else {
    function applyLocationAndLoad(loc) {
        var countryCode = loc && loc.country_code ? loc.country_code : null;
        if (countryCode && !urlCountry) {
            window._georefCountry = countryCode;
            var sel = document.getElementById('country-select');
            if (sel) {
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].value === countryCode) { sel.value = countryCode; break; }
                }
            }
        }
        if (loc && loc.lat && loc.lon) {
            map.setView([loc.lat, loc.lon], 6);
        }
        loadNextGroup();
    }

    // Cache country+coords in localStorage for 24h
    var cachedLoc = null;
    try {
        var raw = localStorage.getItem('georef_location');
        if (raw) {
            var parsed = JSON.parse(raw);
            if (parsed.ts && (Date.now() - parsed.ts) < 86400000) cachedLoc = parsed;
        }
    } catch(e) {}

    if (cachedLoc) {
        applyLocationAndLoad(cachedLoc);
    } else {
        var _bootTimer = setTimeout(function() { applyLocationAndLoad(null); }, 1500);
        fetch(APP_URL + '/georef/detect-location', { headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'} })
            .then(r => r.json())
            .then(function(loc) {
                clearTimeout(_bootTimer);
                if (loc && loc.country_code) {
                    try { localStorage.setItem('georef_location', JSON.stringify({country_code: loc.country_code, lat: loc.lat, lon: loc.lon, ts: Date.now()})); } catch(e) {}
                }
                applyLocationAndLoad(loc || null);
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
    if (!urlCountry) window._georefCountry = '';

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

    var focusDropdown = document.getElementById('focus-dropdown');
    var _focusTimer = null;

    function hideFocusDropdown() { focusDropdown.style.display = 'none'; }

    function showFocusSuggestions(results) {
        if (!results.length) { hideFocusDropdown(); return; }
        focusDropdown.innerHTML = results.map(function(r) {
            var badge = r.pending > 0
                ? '<span style="font-size:9px;background:#fef9c3;color:#92400e;border-radius:3px;padding:1px 4px;margin-left:4px">'+r.pending+' pending</span>'
                : (r.validated > 0 ? '<span style="font-size:9px;background:#dcfce7;color:#166534;border-radius:3px;padding:1px 4px;margin-left:4px">'+r.validated+' validated</span>' : '');
            return '<div class="focus-ac-item" data-id="'+r.id+'" data-label="'+r.label.replace(/"/g,'&quot;')+'" style="padding:6px 10px;cursor:pointer;font-size:11px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;">' +
                '<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+r.label+'</span>' +
                '<span style="flex-shrink:0;color:#9ca3af;font-size:10px;margin-left:6px;">'+r.occurrence_count+' occ.'+badge+'</span>' +
                '</div>';
        }).join('');
        focusDropdown.querySelectorAll('.focus-ac-item').forEach(function(el) {
            el.addEventListener('mousedown', function(e) {
                e.preventDefault();
                focusInput.value = el.dataset.label;
                window._georefFocus = el.dataset.label;
                focusClear.style.display = 'block';
                hideFocusDropdown();
                loadGroup(parseInt(el.dataset.id));
            });
            el.addEventListener('mouseover', function() { el.style.background = '#f9fafb'; });
            el.addEventListener('mouseout',  function() { el.style.background = ''; });
        });
        focusDropdown.style.display = 'block';
    }

    focusInput.addEventListener('input', function() {
        clearTimeout(_focusTimer);
        var q = focusInput.value.trim();
        if (q.length < 2) { hideFocusDropdown(); return; }
        _focusTimer = setTimeout(function() {
            fetch(APP_URL+'/georef/search-locality?q='+encodeURIComponent(q), {headers:{'Accept':'application/json'}})
                .then(r=>r.json()).then(showFocusSuggestions).catch(function(){});
        }, 250);
    });
    focusInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { hideFocusDropdown(); applyFocus(); }
        if (e.key === 'Escape') hideFocusDropdown();
    });
    focusInput.addEventListener('blur', function() {
        setTimeout(hideFocusDropdown, 150);
        if (focusInput.value.trim() !== window._georefFocus) applyFocus();
    });
    focusClear.addEventListener('click', function() {
        focusInput.value = '';
        window._georefFocus = '';
        focusClear.style.display = 'none';
        focusHint.style.display = 'none';
        hideFocusDropdown();
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
    var spec  = document.getElementById('specimens-panel');
    var right = document.getElementById('right-panel');
    var btnInfo = document.getElementById('mob-btn-info');
    var btnSpec = document.getElementById('mob-btn-specimens');
    var btnSug  = document.getElementById('mob-btn-suggest');
    var active = '#16a34a', inactive = '#6b7280';
    var panels = [left, spec, right];
    var btns   = [btnInfo, btnSpec, btnSug];

    // Ensure occ-section lives in specimens-panel on mobile
    if (window.innerWidth <= 768) {
        var occSection = document.getElementById('occ-section');
        if (occSection && occSection.parentElement !== spec) spec.appendChild(occSection);
    }

    var target = panel === 'info' ? left : panel === 'specimens' ? spec : right;
    var targetBtn = panel === 'info' ? btnInfo : panel === 'specimens' ? btnSpec : btnSug;
    var opening = !target.classList.contains('mob-open');

    panels.forEach(function(p){ if(p) p.classList.remove('mob-open'); });
    btns.forEach(function(b){ if(b) b.style.color = inactive; });

    if (opening) {
        target.classList.add('mob-open');
        if (targetBtn) targetBtn.style.color = active;
        map.invalidateSize();
    }
}

// Add swipe-down-to-close for all 3 mobile panels
(function addSwipeClose() {
    ['left-panel','specimens-panel','right-panel'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        var startY = 0, dragging = false;
        el.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY; dragging = true;
        }, {passive:true});
        el.addEventListener('touchmove', function(e) {
            if (!dragging) return;
            var dy = e.touches[0].clientY - startY;
            if (dy > 0) el.style.transform = 'translateY('+dy+'px)';
        }, {passive:true});
        el.addEventListener('touchend', function(e) {
            if (!dragging) return;
            dragging = false;
            var dy = e.changedTouches[0].clientY - startY;
            el.style.transform = '';
            if (dy > 60) {
                el.classList.remove('mob-open');
                ['mob-btn-info','mob-btn-specimens','mob-btn-suggest'].forEach(function(bid){
                    var b = document.getElementById(bid); if(b) b.style.color='#6b7280';
                });
            }
        }, {passive:true});
    });
})();

function updateMobileBar(group, suggestionCount) {
    // Build locality text for thin bar
    var parts = [];
    if (group.verbatim_locality) parts.push(group.verbatim_locality);
    if (group.municipality && !parts.join(' ').toLowerCase().includes((group.municipality||'').toLowerCase())) parts.push(group.municipality);
    if (group.county       && !parts.join(' ').toLowerCase().includes((group.county||'').toLowerCase()))       parts.push(group.county);
    if (group.state_province) parts.push(group.state_province);
    if (group.country_code)   parts.push(group.country_code);
    var text = parts.join(', ') || '—';

    var el = document.getElementById('mob-locality-text');
    if (el) el.textContent = text;

    var spinner = document.getElementById('mob-locality-spinner');
    if (spinner) spinner.style.display = 'none';

    // Suggestion badge on Georef button
    var badge = document.getElementById('mob-sugg-badge');
    if (badge) {
        badge.style.display = suggestionCount > 0 ? 'inline-block' : 'none';
        if (suggestionCount > 0) badge.textContent = suggestionCount > 9 ? '9+' : suggestionCount;
    }

    // Specimens badge (occurrence count)
    var occBadge = document.getElementById('mob-occ-badge');
    var occCount = document.getElementById('occurrence-count');
    if (occBadge && occCount) {
        var n = parseInt(occCount.textContent) || 0;
        occBadge.style.display = n > 0 ? 'inline-block' : 'none';
        if (n > 0) occBadge.textContent = n > 99 ? '99+' : n;
    }

    // Show right bar (Skip/Submit)
    var rb = document.getElementById('mob-right-bar');
    if (rb) rb.classList.add('mob-loaded');
}

function mobSkip() {
    var el = document.getElementById('mob-locality-text');
    if (el) el.textContent = '';
    var spinner = document.getElementById('mob-locality-spinner');
    if (spinner) spinner.style.display = 'inline-flex';
    // Close any open panel
    ['left-panel','specimens-panel','right-panel'].forEach(function(id){
        var p = document.getElementById(id); if(p) p.classList.remove('mob-open');
    });
    loadNextGroup();
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

var _dismissBtn = document.getElementById('dismiss-system-btn');
if (_dismissBtn) {
    _dismissBtn.addEventListener('click', function() {
        if (!currentGroup) return;
        if (!confirm('{{ __("No conflict — dismiss all system suggestions for this group?") }}')) return;
        fetch(APP_URL+'/georef/group/'+currentGroup.id+'/dismiss-system-suggestions', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
        }).then(function(r){ return r.json(); }).then(function(d) {
            if (d.success) loadNextGroup();
            else if (d.error) alert(d.error);
        });
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

    <script>
    // ── Tutorial ──────────────────────────────────────────────────────────────
    var TUT_STEPS = [
        {
            sel: '',
            title: 'Welcome to Georeferencer!',
            text: 'This quick tutorial walks you through the main tools. You can reopen it at any time by clicking the ? Help button in the top-right corner of the map.',
            pos: 'bottom'
        },
        {
            sel: '#focus-input',
            title: 'Focus Area',
            text: 'Type a place name, region or country to narrow down which localities appear. Leave empty for a random global selection.',
            pos: 'right'
        },
        {
            sel: '#occurrence-info',
            title: 'Locality Data',
            text: 'This is the written locality description from the specimen label — the text you\'ll interpret to place a point on the map.',
            pos: 'right'
        },
        {
            sel: '#nominatim-input',
            title: 'Location Search',
            text: 'Type a place name and press Enter (or 🔍) to search OpenStreetMap. Selecting a result automatically centres the map and places the marker — a great starting point when the locality name is recognisable.',
            pos: 'left'
        },
        {
            sel: '#map',
            title: 'Place a point',
            text: 'Click anywhere on the map to drop a marker. Drag it to adjust. The coordinates fill in automatically on the right. The uncertainty circle shows the precision — drag its edge to resize it.',
            pos: 'left'
        },
        {
            sel: '#occurrences-list',
            title: 'Specimens',
            text: 'These are all the specimens that share this locality description. Check or uncheck them to include or exclude individual records from your georeference.',
            pos: 'right'
        },
        {
            sel: '#existing-suggestions',
            title: 'Existing Suggestions',
            text: 'If someone has already submitted coordinates, you can agree with their suggestion (adding your vote) or submit a competing one if you disagree.',
            pos: 'left'
        },
        {
            sel: '#remarks-input',
            title: 'Remarks',
            text: 'Add a note explaining your georeferencing decision — especially useful for ambiguous or difficult localities. This will be stored with your suggestion.',
            pos: 'left'
        },
        {
            sel: '#discussion-section',
            title: 'Discussion',
            text: 'Use the comment box to discuss with the community if you\'re uncertain. Other georefencers can reply and help reach a consensus.',
            pos: 'left'
        },
        {
            sel: '#submit-btn',
            title: 'Submit',
            text: 'When you\'re satisfied with the point and uncertainty radius, click Submit to save your georeferencing suggestion.',
            pos: 'top'
        },
        {
            sel: '#skip-btn',
            title: 'Skip',
            text: 'Not sure about this locality? Skip it — you\'ll move on to the next one. Skipped localities may appear again later.',
            pos: 'top'
        },
    ];

    var _tutIdx = 0;

    function tutStart() {
        _tutIdx = 0;
        document.getElementById('tut-overlay').style.display = 'block';
        tutRender();
    }

    function tutEnd() {
        document.getElementById('tut-overlay').style.display = 'none';
        try { localStorage.setItem('georef_tutorial_done', '1'); } catch(e) {}
    }

    function tutStep(dir) {
        _tutIdx = Math.max(0, Math.min(TUT_STEPS.length - 1, _tutIdx + dir));
        tutRender();
    }

    function tutIsMobile() { return window.innerWidth <= 768; }

    function videoOpen() {
        const modal = document.getElementById('video-modal');
        document.getElementById('video-iframe').src = 'https://player.vimeo.com/video/1203384672?autoplay=1&badge=0&player_id=0&app_id=58479';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function videoClose() {
        document.getElementById('video-modal').style.display = 'none';
        document.getElementById('video-iframe').src = '';
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') videoClose(); });

    function tutRender() {
        var step  = TUT_STEPS[_tutIdx];
        var el    = step.sel ? document.querySelector(step.sel) : null;
        var mobile = tutIsMobile();

        // update text
        document.getElementById('tut-step-label').textContent = 'Step ' + (_tutIdx + 1) + ' of ' + TUT_STEPS.length;
        document.getElementById('tut-title').textContent = step.title;
        document.getElementById('tut-text').textContent  = step.text;
        document.getElementById('tut-prev').style.visibility = _tutIdx === 0 ? 'hidden' : 'visible';
        var nextBtn = document.getElementById('tut-next');
        nextBtn.textContent = _tutIdx === TUT_STEPS.length - 1 ? 'Done ✓' : 'Next →';
        nextBtn.onclick = _tutIdx === TUT_STEPS.length - 1 ? tutEnd : function() { tutStep(1); };

        // dots
        document.getElementById('tut-dots').innerHTML = TUT_STEPS.map(function(_, i) {
            return '<div style="width:6px;height:6px;border-radius:50%;background:' + (i === _tutIdx ? '#16a34a' : '#e5e7eb') + ';"></div>';
        }).join('');

        var spot = document.getElementById('tut-spot');
        var card = document.getElementById('tut-card');

        var vw = window.innerWidth, vh = window.innerHeight;
        if (mobile || !el) {
            // No spotlight — place spot off-screen so its box-shadow still dims the background
            spot.style.display = 'block';
            spot.style.left = '-9999px'; spot.style.top = '0';
            spot.style.width = '1px';    spot.style.height = '1px';
            // Card centered on screen
            var cw = Math.min(300, vw - 24);
            card.style.width = cw + 'px';
            card.style.left  = Math.round((vw - cw) / 2) + 'px';
            card.style.top   = Math.round(vh * 0.3) + 'px';
        } else {
            // Desktop: spotlight + positioned tooltip
            var pad = 6;
            var r   = el.getBoundingClientRect();
            spot.style.display = 'block';
            spot.style.left   = (r.left - pad) + 'px';
            spot.style.top    = (r.top  - pad) + 'px';
            spot.style.width  = (r.width  + pad * 2) + 'px';
            spot.style.height = (r.height + pad * 2) + 'px';
            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

            var cw = 280, ch = 260;
            var cx, cy;
            if (step.pos === 'right' && r.right + cw + 20 < vw) {
                cx = r.right + 14; cy = Math.min(r.top, vh - ch - 20);
            } else if (step.pos === 'left' && r.left - cw - 14 > 0) {
                cx = r.left - cw - 14; cy = Math.min(r.top, vh - ch - 20);
            } else if (step.pos === 'top') {
                cx = Math.max(10, Math.min(r.left + r.width / 2 - cw / 2, vw - cw - 10));
                // prefer above; fall below if not enough room
                cy = r.top - ch - 14;
                if (cy < 10) cy = r.bottom + 14;
            } else {
                cx = Math.max(10, Math.min(r.left + r.width / 2 - cw / 2, vw - cw - 10)); cy = r.bottom + 14;
            }
            card.style.width = cw + 'px';
            card.style.left  = cx + 'px';
            card.style.top   = Math.max(10, Math.min(cy, vh - ch - 10)) + 'px';
        }
    }

    // Show tutorial on first visit, after a short delay to let the page settle
    window.addEventListener('load', function() {
        // Move Help button inside #map so it's anchored to the map top-right corner
        var tutBtn = document.getElementById('tut-btn');
        document.getElementById('map').appendChild(tutBtn);
        tutBtn.style.display = 'block';
        try {
            if (!localStorage.getItem('georef_tutorial_done')) {
                setTimeout(tutStart, 1200);
            }
        } catch(e) {}
    });
    </script>
    @endpush
</x-layouts.georef>
