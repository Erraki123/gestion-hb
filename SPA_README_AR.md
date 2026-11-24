# Smart Health SPA — دليل الإدماج والـ Panoramas

## 1. الملفات (انسخها فنفس المسارات ديال Laravel)
| الملف | الدور |
|---|---|
| `resources/views/pages/medical/health-spa.blade.php` | الصفحة (HTML + النقط التفاعلية + الـ dock) |
| `resources/views/pages/medical/show.blade.php` | تعدّل فيه: `in_array($slug, ['ncs','health-spa'])` (النسخة جاهزة) |
| `public/css/hnb-spa.css` | الستايل (prefix `.sp`) |
| `public/js/hnb-spa.js` | المحرك 360° + الحالات + المؤقت + الجولة التلقائية |
| `public/images/medical/health-spa/products/*.webp` | الصور الستة (900px، 384 KB فالمجموع) |
| `public/images/medical/health-spa/pano/` | هنا غادي تحط الـ 12 panorama |

بعد النسخ: `php artisan view:clear` ثم `Ctrl+F5`.
بلا panoramas الصفحة كتخدم فـ"mode produits seul" (خلفية سوداء + المنتجات تفاعلية) باش تقدر تجرب.

## 2. السيناريو (6 حالات، 3 أماكن)
- **الحالات**: Privacy (تبديل الملابس) → Ready → In Session (مؤقت) → Session Finished (وميض + جرس) → Cleaning → Preparing.
- **الأماكن**: salle de soin (لوحة المعالج + لوحة الخصوصية) · couloir (شاشة الباب V1 + Session Status) · réception (شاشة الاستقبال، الخلية V1 كتتبدل لونها).
- **المؤقت**: 30:00 كتسالي فـ 60 ثانية (سرعة العرض ×30). `+` / `−` كيبدلو المدة بـ 5 دقائق، `▶` كيبدا، `■` كيوقف.
- كل ضغطة كتلانسي **الجولة التلقائية**: salle → porte → réception → salle.
- لما كتوصل Session Finished كيبان الزر **« Clôturer la séance »** فالاستقبال → كيدوز لـ Cleaning.

## 3. الـ panoramas المطلوبة (12 صورة)
الاسم: `{zone}-{état}.webp` فـ `public/images/medical/health-spa/pano/`

| | privacy | ready | session | cleaning |
|---|---|---|---|---|
| room | room-privacy | room-ready | room-session | room-cleaning |
| corridor | corridor-privacy | corridor-ready | corridor-session | corridor-cleaning |
| station | station-privacy | station-ready | station-session | station-cleaning |

`finished` كيستعمل صورة `session`، و`preparing` كيستعمل `cleaning`.

### Bloc commun (يتلصق فكل prompt)
```
equirectangular 360° panorama, 2:1 aspect ratio, seamless left-right edges, camera at eye level (1.5 m), level horizon, photorealistic architectural photography, premium day spa, warm beige and light oak wood palette with natural stone and brushed brass accents, soft diffused light, clean and calm, no people, no text, no logos, no readable signs
```

### A. Generation من الصفر (كل مكان بحالة `ready`)
**room-ready**
```
[bloc commun] Private spa treatment room: a massage table with folded white towels and a rolled towel, a warm oak side console with stacked stones and a small orchid, indirect warm cove lighting, a wall-mounted black glass control panel (about 12 cm) next to the door at 1.4 m height, a dark glass door, neutral linen curtains. The control panel faces the camera on the right side of the view.
```
**corridor-ready**
```
[bloc commun] Spa corridor with several oak treatment-room doors in a row, indirect warm lighting, stone floor, a small black glass wall display (about 25 × 12 cm) mounted beside the door marked V1 at 1.4 m, a bench with folded towels, a plant at the end. A thin green LED glow above the V1 door.
```
**station-ready**
```
[bloc commun] Spa reception desk in light oak and stone, a landscape black glass screen (about 30 × 20 cm) on the desk facing the camera, lotus-themed decor without text, a small lounge area with sofas, hanging plants, warm lighting. Green accent on the screen.
```

### B. باقي الحالات بـ **édition d'image** (باش يبقى نفس الإطار)
قل لـ AI: *"Same room, same camera and framing, change only …"*

| état | ما يتبدل (room) | corridor | station |
|---|---|---|---|
| **privacy** | curtains drawn, lights dimmed to soft violet-warm, towels laid on a chair, panel glow violet | LED above V1 door violet | screen shows violet cell |
| **session** | massage table in use is NOT shown (no people): table covered with a sheet and warm candles lit, light very dim amber, panel glow red | LED above V1 door red | screen shows red cell |
| **cleaning** | table stripped, a cleaning cart with fresh towels, bright neutral light, panel glow blue | LED above V1 door blue | screen shows blue cell |

⚠️ لا أشخاص فالصور. لا نصوص قابلة للقراءة.

## 4. وضع النقط التفاعلية `+`
فـ `health-spa.blade.php` داخل `$spHot`: `u` = x/عرض الصورة، `v` = y/ارتفاع الصورة (من الأعلى يسار).
مثال: اللوحة فـ x=2785 من 4096 و y=960 من 2048 → `u=0.680`, `v=0.469`.
القيم الحالية placeholders ديال NCS، تبدلها بعد ما توجد الـ panoramas.
زاوية البداية ديال كل مكان: `VIEW` فـ `hnb-spa.js`.

## 5. Cover
`config/hnbloom_medical.php` كيتسنى `images/medical/health-spa/cover.webp` — خاصك صورة غلاف (تقدر تستعمل `spa-reception-display.webp` مؤقتا).


## ✅ تحديث
الـ12 panorama و`cover.webp` تزادو، والنقط `+` والزوايا مضبوطة على الصور الحقيقية.
