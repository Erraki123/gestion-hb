@php
    /* ------------------------------------------------------------------
       SMART DENTAL CLINIC · visite 360° (même moteur que Smart Hotel / NCS)
       Dossiers :
         public/images/medical/dental-clinic/pano/{zone}-{état}.webp
         public/images/medical/dental-clinic/products/*.webp
       Zones : room · door · reception   États : ready · occupied · busy · cleaning
       Points + : u (0→1 horizontal) et v (0→1 vertical) dans la panorama.
       ------------------------------------------------------------------ */
    $dcBase = asset('images/medical/dental-clinic');
    $dcHot = [
        ['id' => 'nurse', 'zone' => 'room',      'u' => 0.822, 'v' => 0.482, 'name' => 'Nurse Panel',
         'text' => 'L’assistante change l’état de la salle : libre, patient installé, rayons X ou préparation.'],
        ['id' => 'door',  'zone' => 'door',      'u' => 0.598, 'v' => 0.498, 'name' => 'Status Signage · porte',
         'text' => 'Affiche le numéro de la salle et son état à côté de la porte, sans ouvrir.'],
        ['id' => 'disp',  'zone' => 'reception', 'u' => 0.500, 'v' => 0.400, 'name' => 'Status Signage · intérieur',
         'text' => 'Écran central : l’état de toutes les salles de la clinique, dont la salle 01.'],
    ];
    $dcP = fn ($f) => $dcBase . '/products/' . $f . '.webp';
    /* Boîtes des 4 icônes (en % de l'image produit) : prêt · occupé · rayons X · désinfection */
    $quad = [
        'nurse-panel' => [
            'ready' => [19, 17, 26, 31], 'occupied' => [55, 17, 26, 31],
            'busy' => [19, 49.5, 34.5, 30], 'cleaning' => [51, 49.5, 34.5, 30],
        ],
    ];
    $lab = ['ready' => 'Libre', 'occupied' => 'Patient installé', 'busy' => 'Rayons X', 'cleaning' => 'Désinfection'];
@endphp

<link rel="stylesheet" href="{{ asset('css/hnb-dental.css') }}?v={{ filemtime(public_path('css/hnb-dental.css')) }}">

<section class="dc" id="dc" data-base="{{ $dcBase }}" data-hot='@json($dcHot)'
         data-state="ready" data-zone="room" aria-label="Démonstration Smart Dental Clinic · salle de soins 01">
    <div class="dc__stage" id="dcStage">
        <canvas class="dc__canvas" id="dcCanvas" aria-label="Visite 360° d’une clinique dentaire. Glissez pour regarder autour de vous."></canvas>
        <div class="dc__vig"></div>
        <div class="dc__loading" id="dcLoad"><span class="dc__spin"></span><span id="dcLoadT">Préparation de la visite…</span></div>

        <header class="dc__brand">
            <span class="dc__mark">+</span>
            <span><b>HN BLOOM</b><h1>Smart Dental Clinic · Salle 01</h1></span>
        </header>

        <nav class="dc__tools">
            <span class="dc__status" role="status"><i></i><span id="dcStatusT">Salle libre</span></span>
            <a class="dc__tool" href="{{ route('domotique.show', 'smart-hospital') }}" title="Retour à Smart Hospital">←</a>
            <button class="dc__tool is-on" id="dcAuto" type="button" title="Rotation automatique">⟳</button>
            <button class="dc__tool" id="dcReset" type="button" title="Réinitialiser" data-a="reset">↺</button>
            <button class="dc__tool" id="dcLogBtn" type="button" title="Journal de la clinique">☰</button>
            <button class="dc__tool" id="dcFs" type="button" title="Plein écran">⛶</button>
        </nav>
        <div class="dc__log" id="dcLog"><h3>Réception · journal salle 01</h3><ul id="dcLogList"><li class="is-empty">Aucun événement.</li></ul></div>

        <div class="dc__cap"><h2 id="dcTitle">La salle de soins.</h2><p id="dcSub"></p></div>
        <div class="dc__steps" id="dcSteps" hidden>
            <div class="dc__stepbar">
                <span data-s="room"><b>1</b> Salle de soins</span><i></i>
                <span data-s="door"><b>2</b> Porte · couloir</span><i></i>
                <span data-s="reception"><b>3</b> Réception</span>
                <button type="button" class="dc__skip" data-a="skip">Passer ✕</button>
            </div>
            <p id="dcStepT"></p>
        </div>
        <div class="dc__toast" id="dcToast"></div>

        @foreach ($dcHot as $h)
            <button type="button" class="dc__pt" data-id="{{ $h['id'] }}" hidden aria-label="{{ $h['name'] }}">
                <span>+</span><small>{{ $h['name'] }}</small>
            </button>
        @endforeach

        <div class="dc__dock">
            {{-- 1 · Salle de soins : un seul panneau interactif --}}
            <div class="dc__grp is-active" data-zone="room">
                <button type="button" class="dc__lbl">1 · Dans la salle de soins</button>
                <figure class="dc__dev" style="--w:190">
                    <div class="dc__box"><img class="dc__img" src="{{ $dcP('dental-nurse-panel') }}" alt="Status Change Panel for Clinic Nurse" draggable="false">
                        @foreach ($quad['nurse-panel'] as $s => $b)
                            <button type="button" class="dc__h dc__q" data-a="{{ $s }}" data-for="{{ $s }}" aria-label="{{ $lab[$s] }}" title="{{ $lab[$s] }}"
                                    style="left:{{ $b[0] }}%;top:{{ $b[1] }}%;width:{{ $b[2] }}%;height:{{ $b[3] }}%"></button>
                        @endforeach
                    </div>
                    <figcaption><b>Nurse Panel</b><small>Touchez une icône pour changer l’état de la salle</small></figcaption>
                </figure>
            </div>

            {{-- 2 · Couloir : signalétique à l'extérieur de la porte (lecture seule) --}}
            <div class="dc__grp" data-zone="door">
                <button type="button" class="dc__lbl">2 · Couloir · porte salle 01</button>
                <figure class="dc__dev" style="--w:230">
                    <div class="dc__box"><img class="dc__img" src="{{ $dcP('dental-outside-signage') }}" alt="Status Signage Outside Clinic Door" draggable="false">
                        <i class="dc__q" data-for="ready"         style="left:9%;top:20%;width:21%;height:32%"></i>
                        <i class="dc__q" data-for="occupied busy" style="left:41%;top:20%;width:19%;height:32%"></i>
                        <i class="dc__q" data-for=""              style="left:68%;top:20%;width:24%;height:32%"></i>
                        <i class="dc__q" data-for="cleaning"      style="left:4%;top:58%;width:39%;height:26%"></i>
                    </div>
                    <figcaption><b>Status Signage · porte</b><small>Extérieur de la porte</small></figcaption>
                </figure>
            </div>

            {{-- 3 · Réception : écran central (lecture seule) --}}
            <div class="dc__grp" data-zone="reception">
                <button type="button" class="dc__lbl">3 · Réception</button>
                <figure class="dc__dev" style="--w:300">
                    <div class="dc__box"><img class="dc__img" src="{{ $dcP('dental-central-display') }}" alt="Status Signage Inside Clinic" draggable="false">
                        <b class="dc__cell" style="left:9.5%;top:36.2%;width:13.7%;height:10.4%">01</b>
                        <b class="dc__cell dc__cell--x" style="left:44.2%;top:21.4%;width:13.5%;height:11.2%">X1</b>
                    </div>
                    <figcaption><b>Status Signage · réception</b><small>Intérieur · vue de toutes les salles</small></figcaption>
                </figure>
            </div>
        </div>
    </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="{{ asset('js/hnb-dental.js') }}?v={{ filemtime(public_path('js/hnb-dental.js')) }}" defer></script>
