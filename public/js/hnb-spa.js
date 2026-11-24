/* HN BLOOM · Smart Health SPA · visite 360° (même moteur que Smart Hotel / NCS) */
(function () {
    'use strict';
    const root = document.getElementById('sp');
    if (!root || !window.THREE) return;
    const $ = (id) => document.getElementById(id);
    const BASE = root.dataset.base, HOT = JSON.parse(root.dataset.hot);
    const stage = $('spStage'), canvas = $('spCanvas');

    /* ---------- État ---------- */
    /* Séance démo : 1 s réelle = 30 s sur le minuteur (30:00 s'écoule en 60 s) */
    const DEMO_SPEED = 30, DUR_MIN = 300, DUR_MAX = 5400, DUR_STEP = 300;
    const S = { zone: 'room', state: 'privacy', dur: 1800, left: 1800, running: false, ack: false, zoom: null, tour: false };
    /* Vue de départ de chaque zone [longitude °, latitude °] — à ajuster selon les panoramas */
    const VIEW = { room: [290, 2], corridor: [85, 2], station: [173, 2] };
    const FOV = 72, FOV_ZOOM = 42;
    const TITLE = { room: 'La salle de soin.', corridor: 'Le couloir connecté.', station: 'La réception du spa.' };
    const ZNAME = { room: 'Salle de soin V1', corridor: 'Couloir · porte V1', station: 'Réception' };
    const STATUS = {
        privacy: 'Changement de vêtements', ready: 'Prêt · en attente du thérapeute', session: 'Soin en cours',
        finished: 'Séance terminée', cleaning: 'Nettoyage', preparing: 'Préparation'
    };
    const COLOR = { privacy: '#c65cff', ready: '#4dff88', session: '#ff4b4b', finished: '#ff2f2f', cleaning: '#3fb8ff', preparing: '#ffc233' };
    /* Une panorama par zone et par grande étape (finished réutilise session, preparing réutilise cleaning) */
    const PANO = { privacy: 'privacy', ready: 'ready', session: 'session', finished: 'session', cleaning: 'cleaning', preparing: 'cleaning' };
    const SUB = {
        privacy: {
            room: 'Le client se change : la salle passe en mode confidentialité. Touchez une icône du panneau pour changer d’état.',
            corridor: 'L’afficheur de la porte V1 indique « Confidentialité » : personne n’entre.',
            station: 'La réception voit la salle V1 en violet : client en cours de changement.'
        },
        ready: {
            room: 'Le client est installé. La salle est prête, le thérapeute peut commencer.',
            corridor: 'Voyant vert · la salle V1 attend le thérapeute.',
            station: 'Salle V1 prête · le thérapeute peut être appelé.'
        },
        session: {
            room: 'Soin en cours · le minuteur décompte la séance. L’éclairage passe en mode relaxation.',
            corridor: 'Voyant rouge · salle occupée, le minuteur s’affiche sur la porte.',
            station: 'Salle V1 occupée · temps restant visible depuis la réception.'
        },
        finished: {
            room: 'Le temps est écoulé : le voyant clignote. Le thérapeute termine la séance.',
            corridor: 'Alerte de fin de séance clignotante sur la porte V1.',
            station: 'ALERTE · fin de séance en salle V1. Clôturez-la pour lancer le nettoyage.'
        },
        cleaning: {
            room: 'La salle est en cours de nettoyage avant le prochain client.',
            corridor: 'Voyant bleu · nettoyage en cours.',
            station: 'Salle V1 en nettoyage · indisponible.'
        },
        preparing: {
            room: 'Préparation de la salle : serviettes, huiles, ambiance.',
            corridor: 'Voyant jaune · préparation en cours.',
            station: 'Salle V1 en préparation · bientôt disponible.'
        }
    };

    const ease = (x) => x * x * (3 - 2 * x), lerp = (a, b, t) => a + (b - a) * t;
    const near = (t, c) => t + 360 * Math.round((c - t) / 360);
    const key = (z, st) => (z || S.zone) + '-' + PANO[st || S.state];
    const fmt = (s) => { s = Math.max(0, Math.round(s)); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); };

    /* ---------- WebGL : fondu entre panoramas ---------- */
    let renderer;
    try { renderer = new THREE.WebGLRenderer({ canvas, antialias: false }); }
    catch (e) { $('spLoadT').textContent = 'WebGL indisponible'; return; }
    renderer.setPixelRatio(Math.min(devicePixelRatio || 1, 1.5));
    const scene = new THREE.Scene(), camera = new THREE.PerspectiveCamera(FOV, 1, 0.1, 1100);
    const geo = new THREE.SphereGeometry(500, 64, 40); geo.scale(-1, 1, 1);
    const blank = new THREE.DataTexture(new Uint8Array([20, 18, 15, 255]), 1, 1, THREE.RGBAFormat); blank.needsUpdate = true;
    const U = { tA: { value: blank }, tB: { value: blank }, uMix: { value: 0 } };
    scene.add(new THREE.Mesh(geo, new THREE.ShaderMaterial({
        uniforms: U,
        vertexShader: 'varying vec2 vUv; void main(){ vUv=uv; gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0); }',
        fragmentShader: 'precision highp float; uniform sampler2D tA,tB; uniform float uMix; varying vec2 vUv;' +
            'void main(){ gl_FragColor=vec4(mix(texture2D(tA,vUv).rgb,texture2D(tB,vUv).rgb,uMix),1.); }'
    })));
    let SW = 1, SH = 1;
    function resize() {
        SW = Math.max(1, stage.clientWidth); SH = Math.max(1, stage.clientHeight);
        renderer.setSize(SW, SH, false); camera.aspect = SW / SH; camera.updateProjectionMatrix();
    }
    new ResizeObserver(resize).observe(stage);
    document.addEventListener('fullscreenchange', resize);

    /* ---------- Textures (cache limité : les panoramas 4096×2048 sont lourdes) ---------- */
    const loader = new THREE.TextureLoader(), tex = new Map(), pend = new Map(), order = [];
    let cur = null, fade = null, seq = 0, wantKey = null, missing = false;
    function evict() {
        while (order.length > 6) {
            const i = order.findIndex((k) => k !== cur && k !== wantKey && !(fade && fade.to === k));
            if (i < 0) break;
            const k = order.splice(i, 1)[0]; tex.get(k).dispose(); tex.delete(k);
        }
    }
    function load(k) {
        if (tex.has(k)) return Promise.resolve(tex.get(k));
        if (pend.has(k)) return pend.get(k);
        const p = new Promise((res) => loader.load(BASE + '/pano/' + k + '.webp', (t) => {
            t.minFilter = THREE.LinearFilter; t.generateMipmaps = false;
            try { renderer.initTexture(t); } catch (_) {}
            tex.set(k, t); order.push(k); pend.delete(k); evict(); res(t);
        }, undefined, () => { pend.delete(k); res(null); }));
        pend.set(k, p); return p;
    }
    function show(k, dur) {
        const my = ++seq; wantKey = k;
        return load(k).then((t) => {
            if (!t) return false;
            if (my !== seq) return true;
            if (!cur) { U.tA.value = U.tB.value = t; U.uMix.value = 0; cur = k; fade = null; return true; }
            if (fade) { U.tA.value = U.tB.value; U.uMix.value = 0; cur = fade.to; fade = null; }
            if (k === cur) return true;
            U.tA.value = tex.get(cur); U.tB.value = t; U.uMix.value = 0;
            fade = { t0: performance.now(), dur: dur || 700, to: k };
            return true;
        });
    }

    /* ---------- Caméra ---------- */
    const d2r = Math.PI / 180;
    let lon = VIEW.room[0], lat = VIEW.room[1], tLon = lon, tLat = lat, fovT = FOV;
    let auto = true, drag = false, px = 0, py = 0, last = performance.now();
    const setAuto = (v) => { auto = v; $('spAuto').classList.toggle('is-on', v); };
    $('spAuto').onclick = () => setAuto(!auto);
    $('spFs').onclick = async () => { try { document.fullscreenElement ? await document.exitFullscreen() : await stage.requestFullscreen(); } catch (_) {} };
    $('spLogBtn').onclick = () => $('spLog').classList.toggle('on');
    canvas.addEventListener('pointerdown', (e) => { drag = true; setAuto(false); tourStop(); px = e.clientX; py = e.clientY; canvas.setPointerCapture(e.pointerId); });
    canvas.addEventListener('pointermove', (e) => {
        if (!drag) return;
        const k = camera.fov / FOV * 0.075;
        tLon -= (e.clientX - px) * k; tLat = Math.max(-65, Math.min(65, tLat + (e.clientY - py) * k)); px = e.clientX; py = e.clientY;
    });
    ['pointerup', 'pointercancel'].forEach((n) => canvas.addEventListener(n, () => { drag = false; }));

    /* ---------- Sons ---------- */
    let ac;
    const A = () => { const C = window.AudioContext || window.webkitAudioContext; if (!ac && C) ac = new C(); if (ac && ac.state === 'suspended') ac.resume(); return ac; };
    function tone(f, t, d, type, v) {
        const o = ac.createOscillator(), g = ac.createGain(); o.type = type; o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, t); g.gain.linearRampToValueAtTime(v, t + 0.01); g.gain.exponentialRampToValueAtTime(0.0001, t + d);
        o.connect(g); g.connect(ac.destination); o.start(t); o.stop(t + d + 0.05);
    }
    const click = () => { if (A()) tone(1900, ac.currentTime, 0.05, 'square', 0.03); };
    const buzz = () => { if (A()) tone(180, ac.currentTime, 0.25, 'square', 0.05); };
    const soft = () => { if (A()) { tone(523, ac.currentTime, 0.6, 'sine', 0.12); tone(784, ac.currentTime + 0.18, 0.8, 'sine', 0.12); } };
    const gong = () => { if (A()) { tone(196, ac.currentTime, 1.6, 'sine', 0.2); tone(392, ac.currentTime, 1.2, 'sine', 0.07); } };
    const bell = () => { if (A()) { tone(988, ac.currentTime, 0.35, 'sine', 0.16); tone(1319, ac.currentTime + 0.22, 0.5, 'sine', 0.16); } };
    let alarmT;
    function alarm() {
        clearInterval(alarmT);
        if (S.state === 'finished') { bell(); alarmT = setInterval(bell, 2600); }
        else if (S.state === 'session') gong();
        else if (S.state === 'ready') soft();
    }

    /* ---------- Minuteur ---------- */
    let tickT;
    function renderTimer() {
        const t = fmt(S.left);
        root.querySelectorAll('[data-tm]').forEach((e) => { e.textContent = t; });
    }
    function stopTick() { clearInterval(tickT); tickT = null; S.running = false; }
    function startTick() {
        stopTick(); S.running = true;
        tickT = setInterval(() => {
            S.left -= DEMO_SPEED;
            if (S.left <= 0) { S.left = 0; stopTick(); renderTimer(); setState('finished', 'Temps écoulé · fin de séance', 'Fin de séance · Salle V1'); return; }
            renderTimer();
        }, 1000);
    }

    /* ---------- Interface ---------- */
    let toastT;
    function toast(m) { const t = $('spToast'); t.textContent = m; t.classList.add('on'); clearTimeout(toastT); toastT = setTimeout(() => t.classList.remove('on'), 2800); }
    const logs = [];
    function log(m) {
        logs.unshift({ m, t: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) });
        $('spLogList').innerHTML = logs.slice(0, 30).map((r) => '<li><b>' + r.m + '</b><small>' + r.t + '</small></li>').join('');
    }
    function caption() { $('spTitle').textContent = TITLE[S.zone]; $('spSub').textContent = SUB[S.state][S.zone]; }
    function ui() {
        root.dataset.state = S.state; root.dataset.zone = S.zone;
        root.style.setProperty('--sig', COLOR[S.state]);
        $('spStatusT').textContent = STATUS[S.state];
        root.querySelectorAll('.sp__h[data-a="state"]').forEach((h) => h.classList.toggle('is-on', h.dataset.s === S.state));
        root.querySelectorAll('.sp__grp').forEach((g) => g.classList.toggle('is-active', g.dataset.zone === S.zone));
        $('spSteps').hidden = !S.tour;
        root.querySelectorAll('#spSteps [data-s]').forEach((e) => e.classList.toggle('on', e.dataset.s === S.zone));
        const a = $('spAck'), can = S.state === 'finished';
        a.disabled = !can; a.textContent = can ? 'Clôturer la séance' : 'Aucune alerte';
        renderTimer();
    }
    function setState(s, msg, logMsg) {
        S.state = s; S.ack = false;
        if (s === 'session' && !S.running) { if (S.left <= 0) S.left = S.dur; startTick(); }
        if (s !== 'session' && s !== 'finished') { stopTick(); S.left = S.dur; }
        if (s === 'finished') { stopTick(); S.left = 0; }
        show(key(), 600); alarm(); ui(); caption();
        if (logMsg) log(logMsg);
        if (msg) toast(msg);
        tour(s);
    }
    function setZone(z, quiet) {
        if (S.zone === z) return;
        S.zone = z; S.zoom = null; fovT = FOV; camera.fov = FOV; camera.updateProjectionMatrix();
        lon = tLon = VIEW[z][0]; lat = tLat = VIEW[z][1];
        show(key(), 650); ui(); caption(); if (!quiet) toast(ZNAME[z]);
    }
    function zoomTo(id) {
        const h = HOT.find((x) => x.id === id); if (!h) return;
        if (S.zoom === id) { S.zoom = null; fovT = FOV; tLon = near(VIEW[S.zone][0], lon); tLat = VIEW[S.zone][1]; return; }
        S.zoom = id; setAuto(false); fovT = FOV_ZOOM;
        tLon = near(h.u * 360, lon); tLat = 90 - 180 * h.v;
        toast(h.name + ' · ' + h.text);
    }

    /* ---------- Parcours automatique : salle → porte (couloir) → réception → salle ---------- */
    const STEP = {
        privacy:   ['Le client se change · mode confidentialité', 'La porte V1 affiche « Confidentialité »', 'La réception voit la salle V1 en violet'],
        ready:     ['Le client est installé · salle prête', 'Voyant vert à la porte V1', 'La réception sait que le thérapeute peut commencer'],
        session:   ['Le thérapeute lance la séance · le minuteur démarre', 'Voyant rouge · salle occupée, minuteur sur la porte', 'La réception suit le temps restant'],
        finished:  ['Fin du temps : le voyant clignote', 'Alerte de fin de séance à la porte V1', 'Alerte au poste de réception'],
        cleaning:  ['Le personnel nettoie la salle', 'Voyant bleu à la porte V1', 'Salle V1 indisponible pendant le nettoyage'],
        preparing: ['La salle est préparée pour le client suivant', 'Voyant jaune à la porte V1', 'Salle V1 bientôt disponible']
    };
    let tourT = [];
    function tourStop() { tourT.forEach(clearTimeout); tourT = []; if (S.tour) { S.tour = false; ui(); } }
    function tour(state) {
        tourStop();
        const lab = STEP[state]; if (!lab) return;
        ['room', 'corridor', 'station'].forEach((z) => load(key(z, state)));
        const d = [2200, 3800, 4200];
        S.tour = true; setAuto(false);
        let at = 0;
        ['room', 'corridor', 'station'].forEach((z, i) => {
            tourT.push(setTimeout(() => { setZone(z, true); $('spStepT').textContent = lab[i]; ui(); }, at));
            at += d[i];
        });
        tourT.push(setTimeout(() => { setZone('room', true); S.tour = false; ui(); }, at));
    }

    const ACT = {
        state(btn) {
            const s = btn.dataset.s;
            if (s === S.state) { buzz(); toast('La salle est déjà dans cet état'); return; }
            if (s === 'finished') { if (S.state !== 'session') { buzz(); toast('Aucune séance en cours'); return; } }
            const L = { privacy: 'Confidentialité · Salle V1', ready: 'Salle V1 prête', session: 'Séance démarrée · Salle V1', finished: 'Fin de séance · Salle V1', cleaning: 'Nettoyage · Salle V1', preparing: 'Préparation · Salle V1' };
            setState(s, STATUS[s] + ' · salle V1', L[s]);
        },
        play() {
            if (S.state === 'session') { buzz(); toast('Séance déjà en cours'); return; }
            S.left = S.dur;
            setState('session', 'Séance démarrée · ' + fmt(S.dur), 'Séance démarrée · ' + fmt(S.dur));
        },
        stop() {
            if (S.state !== 'session') { buzz(); toast('Aucune séance en cours'); return; }
            setState('finished', 'Séance arrêtée', 'Séance arrêtée manuellement');
        },
        inc() { adj(DUR_STEP); },
        dec() { adj(-DUR_STEP); },
        ack() {
            if (S.state !== 'finished') { buzz(); toast('Aucune alerte à acquitter'); return; }
            setState('cleaning', 'Séance clôturée · nettoyage lancé', 'Séance clôturée · réception');
        },
        skip() { tourStop(); setZone('room', true); },
        reset() {
            tourStop(); stopTick(); S.dur = 1800; S.left = 1800; S.zoom = null; fovT = FOV;
            setState('privacy', 'Système réinitialisé', 'Système réinitialisé'); tourStop(); setZone('room', true);
        }
    };
    function adj(d) {
        if (S.state === 'session') { buzz(); toast('Réglez la durée avant de démarrer'); return; }
        S.dur = Math.max(DUR_MIN, Math.min(DUR_MAX, S.dur + d)); S.left = S.dur; renderTimer();
        toast('Durée de la séance · ' + fmt(S.dur));
    }

    root.addEventListener('click', (e) => {
        const pt = e.target.closest('.sp__pt');
        if (pt) { click(); tourStop(); zoomTo(pt.dataset.id); return; }
        const a = e.target.closest('[data-a]');
        if (a) { if (a.disabled) return; click(); if (ACT[a.dataset.a]) ACT[a.dataset.a](a); return; }
        const g = e.target.closest('[data-zone]');
        if (g) { click(); tourStop(); setZone(g.dataset.zone); }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && S.zoom) { S.zoom = null; fovT = FOV; tLon = near(VIEW[S.zone][0], lon); tLat = VIEW[S.zone][1]; }
    });

    /* ---------- Points interactifs projetés sur la panorama ---------- */
    const pts = HOT.map((h) => ({ h, el: root.querySelector('.sp__pt[data-id="' + h.id + '"]') })).filter((p) => p.el);
    const V = new THREE.Vector3();
    function place() {
        pts.forEach((p) => {
            const h = p.h;
            if (h.zone !== S.zone || missing) { p.el.hidden = true; return; }
            const th = h.u * 2 * Math.PI, ph = h.v * Math.PI;
            V.set(Math.cos(th) * Math.sin(ph), Math.cos(ph), Math.sin(th) * Math.sin(ph)).multiplyScalar(400).project(camera);
            const on = V.z < 1 && Math.abs(V.x) < 1.05 && Math.abs(V.y) < 1.05;
            p.el.hidden = !on;
            if (on) {
                p.el.style.left = ((V.x + 1) / 2 * SW) + 'px'; p.el.style.top = ((1 - V.y) / 2 * SH) + 'px';
                p.el.classList.toggle('is-zoom', S.zoom === h.id);
            }
        });
    }

    /* ---------- Boucle ---------- */
    let visible = true;
    new IntersectionObserver((es) => { visible = es[0].isIntersecting; }).observe(stage);
    function animate(now) {
        requestAnimationFrame(animate);
        if (!visible || document.hidden) { last = now; return; }
        const dt = Math.min(0.05, (now - last) / 1000); last = now;
        if (auto && !drag && !S.zoom) tLon += dt * 2;
        lon = lerp(lon, tLon, 0.07); lat = lerp(lat, tLat, 0.07);
        if (Math.abs(camera.fov - fovT) > 0.05) { camera.fov = lerp(camera.fov, fovT, 0.08); camera.updateProjectionMatrix(); }
        if (fade) {
            const p = Math.min(1, (now - fade.t0) / fade.dur); U.uMix.value = ease(p);
            if (p >= 1) { U.tA.value = U.tB.value; U.uMix.value = 0; cur = fade.to; fade = null; }
        }
        const phi = (90 - lat) * d2r, th = lon * d2r;
        camera.lookAt(Math.sin(phi) * Math.cos(th), Math.cos(phi), Math.sin(phi) * Math.sin(th));
        renderer.render(scene, camera);
        place();
    }

    /* ---------- Démarrage ---------- */
    function preload() {
        const q = [S.zone + '-ready'].concat(Object.keys(VIEW).filter((z) => z !== S.zone).map((z) => z + '-privacy'));
        (function next() { const k = q.shift(); if (k) load(k).then(() => setTimeout(next, 250)); })();
    }
    show(key(), 0).then((ok) => {
        if (!ok) {
            /* Panoramas absentes : l'interface reste utilisable (fond sombre) pour tester les produits */
            missing = true; setAuto(false);
            $('spLoadT').textContent = 'Panoramas introuvables dans public/images/medical/health-spa/pano/ — mode produits seul';
            setTimeout(() => $('spLoad').classList.add('done'), 2600);
            return;
        }
        $('spLoad').classList.add('done'); preload();
    });
    ui(); caption(); resize(); requestAnimationFrame(animate);
})();
