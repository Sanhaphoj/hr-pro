@php
    /** @var \App\Models\WorkLocation|null $location */
    $location ??= null;

    // Falls back to central Bangkok so the map opens somewhere recognisable
    // when a brand new zone has no coordinates yet.
    $mapLat = $location?->latitude ?? 13.7563;
    $mapLng = $location?->longitude ?? 100.5018;
    $mapRadius = $location?->radius_meters ?? 150;
    $hasPoint = $location?->latitude !== null;
@endphp

<div class="form-grid">
    <x-input name="name" label="ชื่อบริเวณ" :value="$location?->name" :required="true" hint="เช่น สำนักงานใหญ่, สาขาลาดพร้าว" />
    <x-input name="code" label="รหัสบริเวณ" :value="$location?->code" :required="true" hint="เช่น HQ, BR-LP" />

    <div class="col-span-2">
        <x-input name="address" label="ที่อยู่" :value="$location?->address" hint="แสดงให้พนักงานเห็นในหน้าลงเวลา (ไม่บังคับ)" />
    </div>

    {{-- ---------------- Map picker ---------------- --}}
    <div class="col-span-2">
        <div class="field">
            <label for="zone-map">เลือกจุดบนแผนที่ <span class="req">*</span></label>

            <div class="mappick" id="zone-map-wrap"
                 data-lat="{{ $mapLat }}" data-lng="{{ $mapLng }}"
                 data-radius="{{ $mapRadius }}" data-has-point="{{ $hasPoint ? '1' : '0' }}">
                <div class="mappick__canvas" id="zone-map"></div>

                <div class="mappick__bar">
                    <button type="button" class="btn btn--secondary btn--sm" id="pick-here">
                        <x-icon name="crosshair" width="16" height="16" />ใช้ตำแหน่งปัจจุบัน
                    </button>
                    <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn--ghost btn--sm is-disabled" id="verify-map">
                        <x-icon name="map-pin" width="16" height="16" />เปิดใน Google Maps
                    </a>
                    <span class="mappick__readout" id="pick-status">คลิกบนแผนที่หรือลากหมุดเพื่อกำหนดจุดศูนย์กลาง</span>
                </div>
            </div>

            <span class="field__hint">วงกลมสีเขียวคือขอบเขตที่พนักงานลงเวลาได้ ปรับขนาดได้ที่ช่อง “รัศมี” ด้านล่าง</span>
        </div>
    </div>

    <x-input name="latitude" type="number" step="any" min="-90" max="90" label="ละติจูด"
             :value="$location?->latitude" :required="true" hint="กรอกเองได้ หรือปล่อยให้แผนที่เติมให้" />
    <x-input name="longitude" type="number" step="any" min="-180" max="180" label="ลองจิจูด"
             :value="$location?->longitude" :required="true" hint="ทศนิยม เช่น 100.501765" />

    <x-input name="radius_meters" type="number" min="20" max="20000" label="รัศมี (เมตร)"
             :value="$mapRadius" :required="true"
             hint="ระยะจากจุดศูนย์กลางที่ยังลงเวลาได้ — แนะนำ 100–300 ม. เผื่อความคลาดเคลื่อนของ GPS" />
    <x-input name="notes" label="หมายเหตุ" :value="$location?->notes" hint="บันทึกภายใน (ไม่บังคับ)" />

    <x-checkbox name="is_active" label="เปิดใช้งานบริเวณนี้" :checked="$location?->is_active ?? true"
                hint="ปิดไว้เพื่อพักการใช้งานชั่วคราวโดยไม่ต้องลบ" />
</div>

@push('head')
    {{-- Leaflet 1.9.4 is vendored into public/vendor rather than pulled from a
         CDN: no build step to add, it keeps working on networks that block
         public CDNs, and there is no SRI hash to drift out of date. Map tiles
         still come from OpenStreetMap over the network. --}}
    <link rel="stylesheet" href="{{ asset_v('vendor/leaflet/leaflet.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset_v('vendor/leaflet/leaflet.min.js') }}"></script>
<script>
    (function () {
        var wrap = document.getElementById('zone-map-wrap');
        if (!wrap) return;

        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');
        var radiusInput = document.getElementById('radius_meters');
        var status = document.getElementById('pick-status');
        var mapLink = document.getElementById('verify-map');
        var hereBtn = document.getElementById('pick-here');

        function currentRadius() {
            var r = parseInt(radiusInput.value, 10);
            return isNaN(r) || r < 1 ? 150 : r;
        }

        function syncMapLink() {
            var lat = parseFloat(latInput.value);
            var lng = parseFloat(lngInput.value);
            var usable = !isNaN(lat) && !isNaN(lng);

            mapLink.href = usable ? 'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lng : '#';
            mapLink.classList.toggle('is-disabled', !usable);
        }

        // --- No Leaflet (offline, blocked CDN): the numeric fields still work ---
        if (typeof L === 'undefined') {
            wrap.classList.add('mappick--down');
            status.textContent = 'โหลดแผนที่ไม่สำเร็จ — กรอกละติจูด/ลองจิจูดด้านล่างได้ตามปกติ';
            hereBtn.addEventListener('click', function () {
                useCurrentPosition(function (lat, lng, accuracy) {
                    latInput.value = lat.toFixed(7);
                    lngInput.value = lng.toFixed(7);
                    syncMapLink();
                    status.textContent = 'ใช้ตำแหน่งปัจจุบันแล้ว (คลาดเคลื่อน ±' + accuracy + ' ม.)';
                });
            });
            latInput.addEventListener('input', syncMapLink);
            lngInput.addEventListener('input', syncMapLink);
            syncMapLink();
            return;
        }

        var hasPoint = wrap.dataset.hasPoint === '1';
        var start = [parseFloat(wrap.dataset.lat), parseFloat(wrap.dataset.lng)];

        // Scroll wheel stays with the page: zoom is via the +/- control,
        // double-click or pinch, so scrolling the form never hijacks.
        var map = L.map('zone-map', { scrollWheelZoom: false }).setView(start, hasPoint ? 16 : 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        // Built but not attached: a brand new zone must not look like it already
        // has a centre when the coordinate fields are still empty. They join the
        // map the moment a point is actually chosen.
        var circle = L.circle(start, {
            radius: currentRadius(),
            color: '#0e8f88', weight: 2,
            fillColor: '#0e8f88', fillOpacity: .15
        });
        var marker = L.marker(start, { draggable: true, autoPan: true });
        var placed = false;

        function place(lat, lng, opts) {
            opts = opts || {};
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]);

            if (!placed) {
                circle.addTo(map);
                marker.addTo(map);
                placed = true;
            }

            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            syncMapLink();

            status.textContent = opts.note || ('จุดศูนย์กลาง ' + lat.toFixed(6) + ', ' + lng.toFixed(6));
            if (opts.recentre) { map.setView([lat, lng], Math.max(map.getZoom(), 16)); }
        }

        if (hasPoint) { place(start[0], start[1]); }

        map.on('click', function (e) { place(e.latlng.lat, e.latlng.lng); });
        marker.on('dragend', function () {
            var p = marker.getLatLng();
            place(p.lat, p.lng);
        });

        // Typing coordinates by hand keeps the map in step.
        function fromInputs() {
            var lat = parseFloat(latInput.value);
            var lng = parseFloat(lngInput.value);
            if (isNaN(lat) || isNaN(lng)) return;
            place(lat, lng);
            map.setView([lat, lng]);
        }
        latInput.addEventListener('change', fromInputs);
        lngInput.addEventListener('change', fromInputs);

        radiusInput.addEventListener('input', function () {
            circle.setRadius(currentRadius());
        });

        hereBtn.addEventListener('click', function () {
            useCurrentPosition(function (lat, lng, accuracy) {
                place(lat, lng, { recentre: true, note: 'ใช้ตำแหน่งปัจจุบันแล้ว (คลาดเคลื่อน ±' + accuracy + ' ม.)' });
            });
        });

        syncMapLink();

        // Leaflet measures the container on creation; inside a form that is
        // still settling it can come up short, so re-measure once laid out.
        setTimeout(function () { map.invalidateSize(); }, 200);

        function useCurrentPosition(onFix) {
            if (!navigator.geolocation || !window.isSecureContext) {
                status.textContent = 'เบราว์เซอร์ระบุตำแหน่งไม่ได้ (ต้องเปิดผ่าน HTTPS) — คลิกบนแผนที่แทนได้';
                return;
            }
            status.textContent = 'กำลังอ่านตำแหน่ง…';
            navigator.geolocation.getCurrentPosition(function (position) {
                onFix(position.coords.latitude, position.coords.longitude, Math.round(position.coords.accuracy));
            }, function () {
                status.textContent = 'อ่านตำแหน่งไม่สำเร็จ — คลิกบนแผนที่หรือกรอกพิกัดเองได้';
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        }
    })();
</script>
@endpush
