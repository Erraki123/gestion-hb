/* HN BLOOM · Smart Dental Clinic · visite 360° (même moteur que Smart Hotel / NCS) */
(function () {
    'use strict';
    const root = document.getElementById('dc');
    if (!root || !window.THREE) return;
    const $ = (id) => document.getElementById(id);
    const BASE = root.dataset.base, HOT = JSON.parse(root.dataset.hot);
    const stage = $('dcStage'), canvas = $('dcCanvas');

    /* ---------- État ---------- */
    const S = { zone: 'room', state: 'ready', zoom: null, tour: false };
    /* Vue de départ de chaque zone [longitude °, latitude °] (lon = 360·u) */
    const VIEW = { room: [292, 0], door: [200, 0], reception: [180, 0] };
    const FOV = 72, FOV_ZOOM = 42;
    const TITLE = { room: 'La salle de soins.', door: 'Le couloir connecté.', reception: 'La réception.' };
    const ZNAME = { room: 'Salle de soins 01', door: 'Couloir · porte salle 01', reception: 'Réception' };
    const STATUS = { ready: 'Salle libre', occupied: 'Patient installé', busy: 'Radiographie en cours', cleaning: 'Désinfection' };
    const COLOR = { ready: '#3ddc84', occupied: '#ff4d4d', busy: '#ffd23f', cleaning: '#28d7ee' };
    const CELL = { ready: '#2fe06f', occupied: '#ff2b2b', busy: '#ff2fb5', cleaning: '#18c8ff' };
    const SUB = {
        ready: {
            room: 'Salle prête. Touchez une icône du Nurse Panel pour changer l’état : patient installé, rayons X ou désinfection.',
            door: 'Le voyant vert au-dessus de la porte indique que la salle est libre.',
            reception: 'La salle 01 apparaît en vert sur l’écran central : prête à accueillir.'
        },
        occupied: {
            room: 'Patient installé · le soin commence. Le panneau passe au rouge.',
            door: 'Voyant rouge : la salle est occupée, inutile de frapper.',
            reception: 'La réception voit la salle 01 occupée en temps réel.'
        },
        busy: {
            room: 'Radiographie en cours · le panneau passe au jaune, la salle est protégée.',
            door: 'Voyant jaune : rayons X en cours, ne pas entrer.',
            reception: 'La salle 01 est signalée « rayons X » à la réception.'
        },
        cleaning: {
            room: 'Désinfection en cours · le soignant prépare la salle pour le patient suivant.',
            door: 'Voyant cyan : préparation et désinfection, porte entrouverte.',
            reception: 'La salle 01 est en préparation : le prochain patient peut patienter.'
        }
    };
    const STEP = {
        ready:    ['La salle est désinfectée et prête', 'Voyant vert · salle libre', 'La réception voit la salle 01 disponible'],
        occupied: ['Le patient est installé, le soin commence', 'Voyant rouge · salle occupée', 'La réception voit la salle 01 occupée'],
        busy:     ['La radiographie démarre', 'Voyant jaune · ne pas entrer', 'La réception sait que la salle est indisponible'],
        cleaning: ['Le soignant lance la désinfection', 'Voyant cyan · préparation en cours', 'La réception anticipe le prochain patient']
    };
    const LOGM = { ready: 'Salle prête · salle 01', occupied: 'Patient installé · salle 01', busy: 'Radiographie en cours · salle 01', cleaning: 'Désinfection · salle 01' };

    const ease = (x) => x * x * (3 - 2 * x), lerp = (a, b, t) => a + (b - a) * t;
    const near = (t, c) => t + 360 * Math.round((c - t) / 360);
    const key = () => S.zone + '-' + S.state;

    /* ---------- WebGL : fondu entre panoramas ---------- */
    let renderer;
    try { renderer = new THREE.WebGLRenderer({ canvas, antialias: false }); }
    catch (e) { $('dcLoadT').textContent = 'WebGL indisponible'; return; }
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

    /* ---------- Textures (cache LRU de 6 : panoramas 4096×2048) ---------- */
    const loader = new THREE.TextureLoader(), tex = new Map(), pend = new Map(), order = [];
    let cur = null, fade = null, seq = 0, wantKey = null;
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
    const setAuto = (v) => { auto = v; $('dcAuto').classList.toggle('is-on', v); };
    $('dcAuto').onclick = () => setAuto(!auto);
    $('dcFs').onclick = async () => { try { document.fullscreenElement ? await document.exitFullscreen() : await stage.requestFullscreen(); } catch (_) {} };
    $('dcLogBtn').onclick = () => $('dcLog').classList.toggle('on');
    canvas.addEventListener('pointerdown', (e) => { drag = true; setAuto(false); tourStop(); px = e.clientX; py = e.clientY; canvas.setPointerCapture(e.pointerId); });
    canvas.addEventListener('pointermove', (e) => {
        if (!drag) return;
        const k = camera.fov / FOV * 0.075;
        tLon -= (e.clientX - px) * k; tLat = Math.max(-65, Math.min(65, tLat + (e.clientY - py) * k)); px = e.clientX; py = e.clientY;
    });
    ['pointerup', 'pointercancel'].forEach((n) => canvas.addEventListener(n, () => { drag = false; }));

    /* ---------- Sons (WebAudio, discrets) ---------- */
    let ac;
    const A = () => { const C = window.AudioContext || window.webkitAudioContext; if (!ac && C) ac = new C(); if (ac && ac.state === 'suspended') ac.resume(); return ac; };
    function tone(f, t, d, type, v) {
        const o = ac.createOscillator(), g = ac.createGain(); o.type = type; o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, t); g.gain.linearRampToValueAtTime(v, t + 0.01); g.gain.exponentialRampToValueAtTime(0.0001, t + d);
        o.connect(g); g.connect(ac.destination); o.start(t); o.stop(t + d + 0.05);
    }
    const click = () => { if (A()) tone(1900, ac.currentTime, 0.05, 'square', 0.03); };
    const buzz = () => { if (A()) tone(180, ac.currentTime, 0.25, 'square', 0.05); };
    const SND = {
        ready()    { tone(660, ac.currentTime, 0.4, 'sine', 0.14); tone(880, ac.currentTime + 0.2, 0.6, 'sine', 0.14); },
        occupied() { tone(520, ac.currentTime, 0.3, 'sine', 0.14); tone(390, ac.currentTime + 0.18, 0.4, 'sine', 0.14); },
        busy()     { tone(740, ac.currentTime, 0.14, 'triangle', 0.12); tone(740, ac.currentTime + 0.22, 0.14, 'triangle', 0.12); },
        cleaning() { tone(1175, ac.currentTime, 0.2, 'sine', 0.1); tone(1568, ac.currentTime + 0.12, 0.35, 'sine', 0.1); }
    };
    const sound = (s) => { if (A() && SND[s]) SND[s](); };

    /* ---------- Interface ---------- */
    let toastT;
    function toast(m) { const t = $('dcToast'); t.textContent = m; t.classList.add('on'); clearTimeout(toastT); toastT = setTimeout(() => t.classList.remove('on'), 2800); }
    const logs = [];
    function log(m) {
        logs.unshift({ m, t: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) });
        $('dcLogList').innerHTML = logs.slice(0, 30).map((r) => '<li><b>' + r.m + '</b><small>' + r.t + '</small></li>').join('');
    }
    function caption() { $('dcTitle').textContent = TITLE[S.zone]; $('dcSub').textContent = SUB[S.state][S.zone]; }
    function ui() {
        root.dataset.state = S.state; root.dataset.zone = S.zone;
        root.style.setProperty('--sig', COLOR[S.state]); root.style.setProperty('--cell', CELL[S.state]);
        $('dcStatusT').textContent = STATUS[S.state];
        root.querySelectorAll('.dc__grp').forEach((g) => g.classList.toggle('is-active', g.dataset.zone === S.zone));
        $('dcSteps').hidden = !S.tour;
        root.querySelectorAll('#dcSteps [data-s]').forEach((e) => e.classList.toggle('on', e.dataset.s === S.zone));
    }
    function setState(s, msg, logMsg) {
        S.state = s;
        show(key(), 600); sound(s); ui(); caption();
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

    /* ---------- Parcours automatique : salle → porte → réception → salle ---------- */
    const ZONES = ['room', 'door', 'reception'];
    let tourT = [];
    function tourStop() { tourT.forEach(clearTimeout); tourT = []; if (S.tour) { S.tour = false; ui(); } }
    function tour(state) {
        tourStop();
        const lab = STEP[state]; if (!lab) return;
        ZONES.forEach((z) => load(z + '-' + state));
        const d = [2200, 3800, 4200];
        S.tour = true; setAuto(false);
        let at = 0;
        ZONES.forEach((z, i) => {
            tourT.push(setTimeout(() => { setZone(z, true); $('dcStepT').textContent = lab[i]; ui(); }, at));
            at += d[i];
        });
        tourT.push(setTimeout(() => { setZone('room', true); S.tour = false; ui(); }, at));
    }

    function act(s) {
        if (S.state === s) { buzz(); toast('La salle est déjà en état « ' + STATUS[s].toLowerCase() + ' »'); return; }
        setState(s, STATUS[s] + ' · salle 01', LOGM[s]);
    }
    const ACT = {
        ready: () => act('ready'), occupied: () => act('occupied'), busy: () => act('busy'), cleaning: () => act('cleaning'),
        skip() { tourStop(); setZone('room', true); },
        reset() {
            tourStop(); S.zoom = null; fovT = FOV;
            if (S.state !== 'ready') setState('ready', 'Système réinitialisé', 'Système réinitialisé');
            tourStop(); setZone('room', true);
        }
    };

    root.addEventListener('click', (e) => {
        const pt = e.target.closest('.dc__pt');
        if (pt) { click(); tourStop(); zoomTo(pt.dataset.id); return; }
        const a = e.target.closest('[data-a]');
        if (a) { if (a.disabled) return; click(); if (ACT[a.dataset.a]) ACT[a.dataset.a](); return; }
        const g = e.target.closest('[data-zone]');
        if (g) { click(); tourStop(); setZone(g.dataset.zone); }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && S.zoom) { S.zoom = null; fovT = FOV; tLon = near(VIEW[S.zone][0], lon); tLat = VIEW[S.zone][1]; }
    });

    /* ---------- Points interactifs projetés sur la panorama ---------- */
    const pts = HOT.map((h) => ({ h, el: root.querySelector('.dc__pt[data-id="' + h.id + '"]') })).filter((p) => p.el);
    const V = new THREE.Vector3();
    function place() {
        pts.forEach((p) => {
            const h = p.h;
            if (h.zone !== S.zone) { p.el.hidden = true; return; }
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
        const q = ['door-ready', 'reception-ready', 'room-occupied', 'room-busy', 'room-cleaning'];
        (function next() { const k = q.shift(); if (k) load(k).then(() => setTimeout(next, 300)); })();
    }
    show(key(), 0).then((ok) => {
        if (!ok) { $('dcLoadT').textContent = 'Images introuvables dans public/images/medical/dental-clinic/pano/'; return; }
        $('dcLoad').classList.add('done'); preload();
    });
    ui(); caption(); resize(); requestAnimationFrame(animate);
})();
