from pathlib import Path
from datetime import datetime
import shutil,sys,re

root=Path.cwd()
dash=root/'resources/views/admin/dashboard.blade.php'
review=root/'resources/views/prospects/_discovery_review.blade.php'
layout=root/'resources/views/layouts/app.blade.php'
css_dest=root/'public/css/hnb-ui-sprint3.css'
css_src=Path(__file__).resolve().parent/'hnb-ui-sprint3.css'
paths=[dash,review,layout]
if not (root/'artisan').is_file() or not all(p.is_file() for p in paths):
    sys.exit('ERROR: Start at the Laravel project root; required views not found.')
source={p:p.read_text(encoding='utf-8-sig') for p in paths}
D=source[dash];R=source[review];L=source[layout]

# Idempotent: refuse unexpected structures rather than silently corrupting Blade templates.
if 'HNB_UI_SPRINT3_DASH' not in D:
    start=D.find("        @include('admin._whatsapp_alert')")
    end=D.find("        @include('dashboard._period')",start)
    kpi_start=D.find("        {{-- 1. L'essentiel",end)
    next_block=D.find("        {{-- 2.",kpi_start)
    if min(start,end,kpi_start,next_block)<0 or not start<end<kpi_start<next_block:
        sys.exit('ERROR: Dashboard anchors not found. No files modified.')
    # Remove tall priority section and reinsert as compact strip AFTER the 4 KPI cards.
    D=D[:start]+D[end:]
    anchor=D.find("        {{-- 2.")
    if anchor<0: sys.exit('ERROR: Dashboard KPI insertion anchor missing.')
    notifications='''        {{-- HNB_UI_SPRINT3_DASH: small alerts AFTER main business cards --}}
        @php
            $hnbAtlasPending = \\App\\Models\\DiscoveredProspect::query()->where('status', 'pending')->count();
        @endphp
        <section class="hnb-attention" aria-labelledby="hnb-attention-heading">
          <div class="hnb-section-heading">
            <div><span class="hnb-eyebrow">HN BLOOM / DIRECTION</span><h3 id="hnb-attention-heading">Notifications et actions</h3></div>
            <span class="hnb-attention-sub">À examiner aujourd'hui</span>
          </div>
          <div class="hnb-notice-grid">
            <a class="hnb-notice" href="{{ route('admin.approvals.index') }}">
              <span class="hnb-notice-icon" aria-hidden="true">✓</span>
              <span class="hnb-notice-text"><strong>Validations</strong><small>Devis et commandes en attente</small></span>
              <span class="hnb-notice-number">{{ $pendingQuoteApprovals + $pendingOrderApprovals }}</span>
            </a>
            <a class="hnb-notice" href="{{ route('prospects.index', ['late' => 1]) }}">
              <span class="hnb-notice-icon" aria-hidden="true">↻</span>
              <span class="hnb-notice-text"><strong>Relances</strong><small>Prospects à contacter</small></span>
              <span class="hnb-notice-number">{{ $lateProspectsCount }}</span>
            </a>
            <a class="hnb-notice" href="{{ route('prospects.index', ['atlas' => 'open']) }}">
              <span class="hnb-notice-icon" aria-hidden="true">✦</span>
              <span class="hnb-notice-text"><strong>ATLAS</strong><small>Opportunités à examiner</small></span>
              <span class="hnb-notice-number">{{ $hnbAtlasPending }}</span>
            </a>
            <div class="hnb-notice-whatsapp">@include('admin._whatsapp_alert')</div>
          </div>
        </section>

'''
    D=D[:anchor]+notifications+D[anchor:]
    D=D.replace('class="hnb-dash-v2 py-8 ', 'class="hnb-dash-v2 hnb-dash-v3 py-8 ',1)
    if 'hnb-dash-v3' not in D:
        D=D.replace('class="py-8 max-w-7xl', 'class="hnb-dash-v3 py-8 max-w-7xl',1)

if 'HNB_UI_SPRINT3_ATLAS' not in R:
    opening=re.search(r'<section class="mx-auto mb-5 max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Prospection intelligente">\s*<details[\s\S]*?</summary>\s*<div class="space-y-4 p-4">',R)
    if not opening:sys.exit('ERROR: ATLAS opening section does not match known version; no changes applied.')
    closing=re.search(r'\s*</div>\s*</details>\s*</section>\s*@endif\s*$',R)
    if not closing:sys.exit('ERROR: ATLAS closing anchors not found; no changes applied.')
    newopen='''{{-- HNB_UI_SPRINT3_ATLAS: compact entry + accessible modal; original forms retained --}}
@php
 $hnbOpenAtlas = request()->hasAny(['atlas','d_q','d_city','d_category','d_source','d_score','discovery_page']) || $errors->has('csv') || $errors->has('source') || $errors->has('candidate');
@endphp
<section class="hnb-atlas-entry mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Prospection intelligente"
         x-data="{open: {{ $hnbOpenAtlas ? 'true' : 'false' }} }" @keydown.escape.window="open=false">
  <div class="hnb-atlas-compact">
    <div class="hnb-atlas-compact-icon" aria-hidden="true">✦</div>
    <div class="hnb-atlas-compact-label"><strong>ATLAS Intelligence</strong><span>Opportunités identifiées, sans prise de contact automatique</span></div>
    <span class="hnb-atlas-count">{{ $candidateCount }} à examiner</span>
    <button type="button" class="hnb-atlas-action" @click="open=true; $nextTick(() => $refs.hnbAtlasClose.focus())">Examiner <span aria-hidden="true">→</span></button>
  </div>
  <template x-if="open"><div class="hnb-atlas-backdrop" @click="open=false" aria-hidden="true"></div></template>
  <div class="hnb-atlas-overlay" x-show="open" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-label="ATLAS - Prospection intelligente">
    <div class="hnb-atlas-panel" @click.outside="open=false">
      <div class="hnb-atlas-panel-head">
        <div><span class="hnb-eyebrow">HN BLOOM / PROSPECTION</span><h2>Opportunités ATLAS</h2><p>{{ $candidateCount }} à examiner · {{ $qualifiedCount }} évaluées · Données à vérifier</p></div>
        <button type="button" class="hnb-atlas-close" x-ref="hnbAtlasClose" @click="open=false" aria-label="Fermer ATLAS">×</button>
      </div>
      <div class="hnb-atlas-panel-body space-y-4">
'''
    newclose='''      </div>
    </div>
  </div>
</section>
@endif
'''
    R=R[:opening.start()]+newopen+R[opening.end():closing.start()]+newclose

if '<!-- HNB_UI_SPRINT3_CSS -->' not in L:
    if '</head>' not in L:sys.exit('ERROR: layout </head> missing; no changes applied.')
    L=L.replace('</head>', '''    <!-- HNB_UI_SPRINT3_CSS -->
    <link rel="stylesheet" href="{{ asset('css/hnb-ui-sprint3.css') }}">
</head>''',1)

new={dash:D,review:R,layout:L}
if not css_src.is_file():sys.exit('ERROR: CSS asset not found; no changes applied.')
backup=root/('backup-hnb-ui-sprint3-'+datetime.now().strftime('%Y%m%d-%H%M%S-%f'))
backup.mkdir()
for p in paths+[css_dest]:
    if p.is_file():
        target=backup/p.relative_to(root);target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,target)
try:
    for p,t in new.items():p.write_text(t,encoding='utf-8')
    css_dest.parent.mkdir(parents=True,exist_ok=True)
    shutil.copyfile(css_src,css_dest)
except Exception:
    for p in paths+[css_dest]:
        saved=backup/p.relative_to(root)
        if saved.exists():shutil.copy2(saved,p)
        elif p.exists():p.unlink()
    raise
print('UI SPRINT3 installed. Backup:',backup)
print('No controllers, migrations, queue jobs, or messages changed.')
