@php
    /* ------------------------------------------------------------------
       SMART HEALTH SPA · visite 360° (même moteur que Nurse Call System)
       Dossiers attendus :
         public/images/medical/health-spa/pano/{zone}-{état}.webp   (zone: room|corridor|station ; état: privacy|ready|session|cleaning)
         public/images/medical/health-spa/products/*.webp           (6 produits, déjà fournis)
       Points interactifs : u = position horizontale (0→1), v = verticale (0→1)
       dans la panorama équirectangulaire. À ajuster une fois les panoramas générés.
       ------------------------------------------------------------------ */
    $spBase = asset('images/medical/health-spa');
    $spHot = [
        ['id' => 'ctrl', 'zone' => 'room',     'u' => 0.857, 'v' => 0.423, 'name' => 'Panneau du thérapeute',
         'text' => 'Le thérapeute change l’état de la salle de soin en un geste.'],
        ['id' => 'disp', 'zone' => 'corridor', 'u' => 0.221, 'v' => 0.449, 'name' => 'Afficheur de salle V1',
         'text' => 'Affiche l’état de la salle et le minuteur depuis le couloir.'],
        ['id' => 'recp', 'zone' => 'station',  'u' => 0.480, 'v' => 0.529, 'name' => 'Écran de réception',
         'text' => 'Surveille toutes les salles du spa en temps réel.'],
    ];
    $spImg = fn ($f) => $spBase . '/products/' . $f . '.webp';
@endphp

<link rel="stylesheet" href="{{ asset('css/hnb-spa.css') }}?v={{ filemtime(public_path('css/hnb-spa.css')) }}">

<section class="sp" id="sp" data-base="{{ $spBase }}" data-hot='@json($spHot)'
         data-state="privacy" data-zone="room" aria-label="Démonstration Smart Health SPA · salle de soin V1">
    <div class="sp__stage" id="spStage">
        <canvas class="sp__canvas" id="spCanvas" aria-label="Visite 360° d’un spa. Glissez pour regarder autour de vous."></canvas>
        <div class="sp__vig"></div>
        <div class="sp__loading" id="spLoad"><span class="sp__spin"></span><span id="spLoadT">Préparation de la visite…</span></div>

        <header class="sp__brand">
            <span class="sp__mark">❋</span>
            <span><b>HN BLOOM</b><h1>Smart Health SPA · Salle de soin V1</h1></span>
        </header>

        <nav class="sp__tools">
            <span class="sp__status" role="status"><i></i><span id="spStatusT">Changement de vêtements</span></span>
            <a class="sp__tool" href="{{ route('domotique.show', 'smart-hospital') }}" title="Retour à Smart Hospital">←</a>
            <button class="sp__tool is-on" id="spAuto" type="button" title="Rotation automatique">⟳</button>
            <button class="sp__tool" id="spReset" type="button" title="Réinitialiser" data-a="reset">↺</button>
            <button class="sp__tool" id="spLogBtn" type="button" title="Journal du spa">☰</button>
            <button class="sp__tool" id="spFs" type="button" title="Plein écran">⛶</button>
        </nav>
        <div class="sp__log" id="spLog"><h3>Réception · journal salle V1</h3><ul id="spLogList"><li class="is-empty">Aucun événement.</li></ul></div>

        <div class="sp__cap"><h2 id="spTitle">La salle de soin.</h2><p id="spSub"></p></div>
        <div class="sp__steps" id="spSteps" hidden>
            <div class="sp__stepbar">
                <span data-s="room"><b>1</b> Salle de soin</span><i></i>
                <span data-s="corridor"><b>2</b> Porte · couloir</span><i></i>
                <span data-s="station"><b>3</b> Réception</span>
                <button type="button" class="sp__skip" data-a="skip">Passer ✕</button>
            </div>
            <p id="spStepT"></p>
        </div>
        <div class="sp__toast" id="spToast"></div>

        {{-- Points interactifs projetés sur la panorama --}}
        @foreach ($spHot as $h)
            <button type="button" class="sp__pt" data-id="{{ $h['id'] }}" hidden aria-label="{{ $h['name'] }}">
                <span>+</span><small>{{ $h['name'] }}</small>
            </button>
        @endforeach

        <div class="sp__dock">
            {{-- Salle de soin : panneau thérapeute + panneau confidentialité --}}
            <div class="sp__grp is-active" data-zone="room">
                <button type="button" class="sp__lbl">Dans la salle de soin</button>
                <div class="sp__row">
                    <figure class="sp__dev" style="--w:125">
                        <div class="sp__box">
                        <img class="sp__img" src="{{ $spImg('spa-therapist-control') }}" alt="Panneau de contrôle du thérapeute" draggable="false">
                        <button type="button" class="sp__h" data-a="state" data-s="privacy"   title="Changement de vêtements" aria-label="Changement de vêtements" style="left:23.8%;top:19.8%;width:21.5%;height:18%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="ready"     title="Ready"                    aria-label="Ready"                    style="left:65%;top:19.8%;width:21.5%;height:18%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="session"   title="In Session"               aria-label="In Session"               style="left:44.8%;top:39.6%;width:21%;height:17.8%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="cleaning"  title="Cleaning"                 aria-label="Cleaning"                 style="left:21.5%;top:58.4%;width:20.7%;height:17.8%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="preparing" title="Preparing"                aria-label="Preparing"                style="left:67%;top:58.4%;width:20.7%;height:17.8%"></button>
                        </div>
                        <figcaption>Panneau du thérapeute</figcaption>
                    </figure>
                    <figure class="sp__dev" style="--w:125">
                        <div class="sp__box">
                        <img class="sp__img" src="{{ $spImg('spa-privacy-control') }}" alt="Panneau confidentialité" draggable="false">
                        <button type="button" class="sp__h" data-a="state" data-s="privacy"  title="Confidentialité" aria-label="Confidentialité" style="left:25.8%;top:15.8%;width:13.8%;height:18.6%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="ready"    title="Ready"           aria-label="Ready"           style="left:42.9%;top:37.8%;width:14.2%;height:19.2%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="session"  title="In Session"      aria-label="In Session"      style="left:24.8%;top:57.5%;width:14.8%;height:19.7%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="cleaning" title="Cleaning"        aria-label="Cleaning"        style="left:60.6%;top:57.5%;width:14%;height:19.7%"></button>
                        </div>
                        <figcaption>Confidentialité</figcaption>
                    </figure>
                </div>
            </div>

            {{-- Couloir : afficheur de porte + statut de séance --}}
            <div class="sp__grp" data-zone="corridor">
                <button type="button" class="sp__lbl">Couloir · porte V1</button>
                <div class="sp__row">
                    <figure class="sp__dev" style="--w:230">
                        <div class="sp__box">
                        <img class="sp__img" src="{{ $spImg('spa-room-display-v1') }}" alt="Afficheur de salle V1" draggable="false">
                        <span class="sp__tm" data-tm style="left:43.4%;top:60.6%;width:11.8%;height:7.4%">30:00</span>
                        <button type="button" class="sp__h" data-a="state" data-s="privacy"   title="Changement de vêtements" aria-label="Changement de vêtements" style="left:30.6%;top:35%;width:11%;height:18.8%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="ready"     title="Ready"     aria-label="Ready"     style="left:42.6%;top:35%;width:11%;height:18.8%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="session"   title="In Session" aria-label="In Session" style="left:54.5%;top:35%;width:11%;height:18.8%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="cleaning"  title="Cleaning"   aria-label="Cleaning"   style="left:67.1%;top:35%;width:10.8%;height:18.8%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="preparing" title="Preparing"  aria-label="Preparing"  style="left:79.2%;top:35%;width:11%;height:18.8%"></button>
                        <button type="button" class="sp__h" data-a="dec"   title="−5 min" aria-label="Moins 5 minutes" style="left:35%;top:60.6%;width:5.4%;height:7.4%"></button>
                        <button type="button" class="sp__h" data-a="inc"   title="+5 min" aria-label="Plus 5 minutes"  style="left:58.3%;top:60.6%;width:4.6%;height:7.4%"></button>
                        <button type="button" class="sp__h" data-a="play"  title="Démarrer la séance" aria-label="Démarrer" style="left:69%;top:60.6%;width:7.6%;height:7.4%"></button>
                        <button type="button" class="sp__h" data-a="stop"  title="Arrêter" aria-label="Arrêter" style="left:78.9%;top:60.6%;width:7%;height:7.4%"></button>
                        </div>
                        <figcaption>Afficheur de salle V1</figcaption>
                    </figure>
                    <figure class="sp__dev" style="--w:150">
                        <div class="sp__box">
                        <img class="sp__img" src="{{ $spImg('spa-session-status') }}" alt="Statut de séance" draggable="false">
                        <span class="sp__tm" data-tm style="left:40.5%;top:62.6%;width:24.2%;height:11.4%">30:00</span>
                        <button type="button" class="sp__h" data-a="state" data-s="privacy"  title="Changement de vêtements" aria-label="Changement de vêtements" style="left:26.7%;top:14.7%;width:15.5%;height:23%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="ready"    title="Ready"     aria-label="Ready"     style="left:39.6%;top:41.4%;width:25.5%;height:17.9%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="session"  title="In Session" aria-label="In Session" style="left:23.8%;top:61.3%;width:13.5%;height:20.4%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="cleaning" title="Cleaning"   aria-label="Cleaning"   style="left:67.7%;top:60.8%;width:10.8%;height:18.4%"></button>
                        <button type="button" class="sp__h" data-a="state" data-s="finished" title="Fin de séance" aria-label="Fin de séance" style="left:40.9%;top:77%;width:23.7%;height:6.3%"></button>
                        </div>
                        <figcaption>Statut de séance</figcaption>
                    </figure>
                </div>
            </div>

            {{-- Réception --}}
            <div class="sp__grp" data-zone="station">
                <button type="button" class="sp__lbl">Réception</button>
                <figure class="sp__dev" style="--w:270">
                        <div class="sp__box">
                    <img class="sp__img" src="{{ $spImg('spa-reception-display') }}" alt="Écran de réception du spa" draggable="false">
                    <span class="sp__cell" style="left:27.7%;top:45.5%;width:10.1%;height:6.2%">V1</span>
                    <span class="sp__tm" data-tm style="left:69.6%;top:29%;width:11.4%;height:7%">30:00</span>
                    <span class="sp__ring" style="left:62.3%;top:28.2%;width:20%;height:12.9%"></span>
                    <button type="button" class="sp__h" data-a="ack" title="Acquitter l’alerte" aria-label="Acquitter l’alerte" style="left:62.3%;top:28.2%;width:20%;height:12.9%"></button>
                    </div>
                        <figcaption>Écran de réception</figcaption>
                </figure>
                <button type="button" class="sp__pill" id="spAck" data-a="ack" disabled>Clôturer la séance</button>
            </div>
        </div>
    </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="{{ asset('js/hnb-spa.js') }}?v={{ filemtime(public_path('js/hnb-spa.js')) }}" defer></script>
