{{--
  Workflow overview (GBIF import -> community work -> reuse).

  To change the STRUCTURE (add/remove/reorder a step) edit the $phases array below.
  To change or translate any TEXT edit lang/<locale>.json: every visible string goes through __(),
  keyed by its English text, so adding a language never means touching this file.

  Step tone: 'system' = automatic work, 'people' = volunteer work.
  Phase 'flow' => true draws arrows between the steps; false shows them as parallel outputs.
  Phase 'then' is the caption under the arrow that leads to the next phase.
--}}
@php
    $phases = [
        [
            'title' => __('1. Monthly import from GBIF'),
            'note'  => __('Repeats every month'),
            'flow'  => true,
            'steps' => [
                ['system', __('GBIF download'),         __('Monthly, specimens only')],
                ['system', __('Locality groups'),       __('Same text, one group')],
                ['system', __('Automatic suggestions'), __('From specimens that already have coordinates')],
            ],
            'then'  => __('Groups go to volunteers; automatic suggestions go to validation'),
        ],
        [
            'title' => __('2. Community work'),
            'note'  => __('Each volunteer chooses: georeference, validate or both'),
            'flow'  => true,
            'steps' => [
                ['people', __('Georeference'), __('Point and uncertainty')],
                ['people', __('Validate'),     __('Weighted community votes')],
                ['people', __('Validated'),    __('Consensus threshold reached')],
            ],
            'then'  => __('Served with their verification status'),
        ],
        [
            'title' => __('3. Reuse'),
            'note'  => null,
            'flow'  => false,
            'steps' => [
                ['system', __('Public API'),            __('Darwin Core: JSON, JSON-LD, CSV')],
                ['system', __('Browser extension'),     __('Shown on GBIF pages')],
                ['system', __('Data publishers'),       __('Corrections in Darwin Core')],
                ['system', __('Researchers and tools'), __('Queries and scripts')],
            ],
            'then'  => null,
        ],
    ];
@endphp

<figure class="wf" aria-label="{{ __('The workflow at a glance') }}">
    <style>
        .wf{--wf-sys-bg:#f1efe8;--wf-sys-bd:#b4b2a9;--wf-sys-tx:#2c2c2a;--wf-sys-sub:#5f5e5a;--wf-ppl-bg:#e1f5ee;--wf-ppl-bd:#5dcaa5;--wf-ppl-tx:#085041;--wf-ppl-sub:#0f6e56;--wf-line:#b4b2a9;--wf-muted:#6b7280;margin:0}
        @media (prefers-color-scheme:dark){.wf{--wf-sys-bg:#2c2c2a;--wf-sys-bd:#5f5e5a;--wf-sys-tx:#f1efe8;--wf-sys-sub:#b4b2a9;--wf-ppl-bg:#04342c;--wf-ppl-bd:#1d9e75;--wf-ppl-tx:#e1f5ee;--wf-ppl-sub:#9fe1cb;--wf-line:#5f5e5a;--wf-muted:#9ca3af}}
        .wf-phase{border:1px dashed var(--wf-line);border-radius:12px;padding:14px 16px}
        .wf-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:baseline;gap:4px 16px;margin-bottom:12px}
        .wf-title{margin:0;font-size:.95rem;font-weight:600}
        .wf-note{font-size:.8rem;color:var(--wf-muted)}
        .wf-row{list-style:none;margin:0;padding:0}
        .wf-flow{display:flex;gap:28px}
        .wf-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px}
        .wf-step{position:relative;flex:1 1 0;min-width:0;border:1px solid;border-radius:8px;padding:10px 12px;text-align:center;display:flex;flex-direction:column;gap:2px}
        .wf-step strong{font-size:.9rem;font-weight:600}
        .wf-step span{font-size:.8rem}
        .wf-system{background:var(--wf-sys-bg);border-color:var(--wf-sys-bd);color:var(--wf-sys-tx)}
        .wf-system span{color:var(--wf-sys-sub)}
        .wf-people{background:var(--wf-ppl-bg);border-color:var(--wf-ppl-bd);color:var(--wf-ppl-tx)}
        .wf-people span{color:var(--wf-ppl-sub)}
        .wf-flow .wf-step + .wf-step::before{content:"\2192";position:absolute;left:-28px;width:28px;top:50%;transform:translateY(-50%);text-align:center;color:var(--wf-line);font-size:1.1rem}
        .wf-link{display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 0;color:var(--wf-muted);text-align:center}
        .wf-link span{font-size:1.2rem;line-height:1;color:var(--wf-line)}
        .wf-link small{font-size:.8rem}
        .wf-legend{display:flex;flex-wrap:wrap;gap:6px 20px;margin-top:12px;font-size:.8rem;color:var(--wf-muted)}
        .wf-legend i{display:inline-block;width:12px;height:12px;border:1px solid;border-radius:3px;margin-inline-end:6px;vertical-align:-1px}
        @media (max-width:560px){
            .wf-flow{flex-direction:column;gap:26px}
            .wf-flow .wf-step + .wf-step::before{content:"\2193";left:50%;top:-26px;width:auto;transform:translateX(-50%)}
        }
    </style>

    @foreach ($phases as $phase)
        <section class="wf-phase">
            <header class="wf-head">
                <h3 class="wf-title" dir="auto">{{ $phase['title'] }}</h3>
                @if (!empty($phase['note']))
                    <span class="wf-note" dir="auto">{{ $phase['note'] }}</span>
                @endif
            </header>
            <ol class="wf-row {{ $phase['flow'] ? 'wf-flow' : 'wf-grid' }}">
                @foreach ($phase['steps'] as [$tone, $title, $sub])
                    <li class="wf-step wf-{{ $tone }}">
                        <strong dir="auto">{{ $title }}</strong>
                        <span dir="auto">{{ $sub }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
        @if (!empty($phase['then']))
            <div class="wf-link">
                <span aria-hidden="true">&darr;</span>
                <small dir="auto">{{ $phase['then'] }}</small>
            </div>
        @endif
    @endforeach

    <figcaption class="wf-legend">
        <span><i class="wf-system"></i>{{ __('Automatic (system)') }}</span>
        <span><i class="wf-people"></i>{{ __('Volunteer work') }}</span>
    </figcaption>
</figure>
