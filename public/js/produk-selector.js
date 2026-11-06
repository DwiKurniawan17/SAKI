/**
 * produk-selector.js
 * Logika pemilihan Paket Produk, Wilayah Zonasi, & Bandwidth langsung dari Katalog Price List RSO.
 * Sesuai kebutuhan:
 * 1. Kategori produk dihilangkan dari tampilan UI (disimpan via hidden input untuk kompatibilitas).
 * 2. Paket Produk HANYA menampilkan produk yang diinput oleh RSO pada katalog / Excel matriks.
 * 3. Wilayah Zonasi HANYA menampilkan wilayah/zona yang relevan dengan paket yang dipilih.
 * 4. Bandwidth HANYA menampilkan bandwidth yang ada pada katalog / Excel matriks untuk produk tersebut.
 * 5. Sinkronisasi otomatis ke Alpine.js model dan trigger lookupPrice secara instan.
 */

function initProdukSelector(prefix, initialValues, dbCatalog = null, allWilayahList = []) {
    const elKategori = document.getElementById(`${prefix}_kategori`);
    const elPaket    = document.getElementById(`${prefix}_paketisasi`);
    const elWilayah  = document.getElementById(`${prefix}_wilayah`);
    const elBwWrap   = document.getElementById(`${prefix}_bw_wrap`);
    const elBwSelect = document.getElementById(`${prefix}_bw_select`);
    const elBwCustom = document.getElementById(`${prefix}_bw_custom`);
    const elBwHidden = document.getElementById(`${prefix}_bw_hidden`);

    if (!elPaket) return;

    // dbCatalog format: { "Astinet": { kategori: "connectivity", bw: ["1 Mbps", ...], wilayah: ["Jabodetabek", ...], prices: { "1 Mbps": { "Jabodetabek": 10000 } } } }
    const catalog = (dbCatalog && typeof dbCatalog === 'object') ? dbCatalog : {};
    const allWilayah = Array.isArray(allWilayahList) ? allWilayahList : [];

    // Helper untuk memicu lookupPrice pada Alpine component induk
    function triggerPriceLookup(forceAutoFill = true) {
        const root = elPaket.closest('[x-data]');
        if (root && window.Alpine) {
            try {
                const alpineData = window.Alpine.$data(root);
                if (alpineData && typeof alpineData.lookupPrice === 'function') {
                    // Update wilayah di Alpine jika ada dropdown wilayah
                    if (elWilayah && elWilayah.value) {
                        alpineData.wilayah = elWilayah.value;
                    }
                    setTimeout(() => {
                        alpineData.lookupPrice(prefix, forceAutoFill);
                    }, 30);
                }
            } catch (e) {
                // Ignore silent errors
            }
        }
    }

    // 1. Populasi Paket Produk (hanya produk yang ada di katalog RSO)
    function populatePaket(selectedPaket) {
        elPaket.innerHTML = '<option value="">-- Pilih Paket Produk --</option>';
        const paketKeys = Object.keys(catalog);

        if (paketKeys.length === 0 && !selectedPaket) {
            elPaket.innerHTML = '<option value="">-- Katalog Belum Diisi RSO --</option>';
            return;
        }

        if (selectedPaket && !paketKeys.includes(selectedPaket)) {
            const optOld = document.createElement('option');
            optOld.value = selectedPaket;
            optOld.textContent = selectedPaket;
            optOld.selected = true;
            elPaket.appendChild(optOld);
        }

        paketKeys.forEach(name => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            if (name === selectedPaket) opt.selected = true;
            elPaket.appendChild(opt);
        });
    }

    // 2. Populasi Wilayah Zonasi sesuai Paket yang dipilih
    function populateWilayah(paket, selectedWilayah) {
        if (!elWilayah) return;

        if (!paket) {
            elWilayah.innerHTML = '<option value="">-- Pilih Paket Terlebih Dahulu --</option>';
            if (allWilayah.length > 0) {
                allWilayah.forEach(w => {
                    const opt = document.createElement('option');
                    opt.value = w;
                    opt.textContent = w;
                    if (w === selectedWilayah) opt.selected = true;
                    elWilayah.appendChild(opt);
                });
            }
            return;
        }

        const info = catalog[paket];
        const wilayahList = (info && Array.isArray(info.wilayah) && info.wilayah.length > 0)
            ? info.wilayah
            : allWilayah;

        elWilayah.innerHTML = '<option value="">-- Pilih Wilayah / Zona --</option>';

        // Jika ada selectedWilayah dari data lama tapi tidak ada di katalog saat ini, tetap sertakan
        if (selectedWilayah && !wilayahList.includes(selectedWilayah)) {
            const optOld = document.createElement('option');
            optOld.value = selectedWilayah;
            optOld.textContent = selectedWilayah;
            optOld.selected = true;
            elWilayah.appendChild(optOld);
        }

        let hasSelected = false;
        wilayahList.forEach(w => {
            const opt = document.createElement('option');
            opt.value = w;
            opt.textContent = w;
            if (w === selectedWilayah) {
                opt.selected = true;
                hasSelected = true;
            }
            elWilayah.appendChild(opt);
        });

        // Jika hanya ada 1 pilihan wilayah untuk paket ini, otomatis pilihkan
        if (!hasSelected && wilayahList.length === 1) {
            elWilayah.value = wilayahList[0];
            hasSelected = true;
        }

        // Sinkronisasi ke Alpine model
        elWilayah.dispatchEvent(new Event('input', { bubbles: true }));
        elWilayah.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // 3. Update Bandwidth dropdown sesuai paket yang dipilih
    function updateBandwidth(paket, selectedBw) {
        if (!elBwWrap) return;

        if (!paket) {
            if (elBwSelect) {
                elBwSelect.innerHTML = '<option value="">-- Pilih Paket Terlebih Dahulu --</option>';
                elBwSelect.classList.remove('hidden');
                elBwSelect.removeAttribute('required');
            }
            if (elBwCustom) {
                elBwCustom.classList.add('hidden');
                elBwCustom.removeAttribute('required');
            }
            if (elBwHidden) {
                elBwHidden.value = '';
                elBwHidden.removeAttribute('required');
            }
            return;
        }

        // Sinkronisasi kategori ke hidden input (default: connectivity)
        if (elKategori) {
            elKategori.value = catalog[paket]?.kategori || elKategori.value || 'connectivity';
        }

        const info = catalog[paket];
        const bws = (info && Array.isArray(info.bw)) ? info.bw : [];

        if (bws.length > 0) {
            if (elBwSelect) {
                elBwSelect.classList.remove('hidden');
                elBwSelect.setAttribute('required', '');
                elBwSelect.innerHTML = '<option value="">-- Pilih Bandwidth --</option>';

                if (selectedBw && !bws.includes(selectedBw)) {
                    const optOld = document.createElement('option');
                    optOld.value = selectedBw;
                    optOld.textContent = selectedBw;
                    optOld.selected = true;
                    elBwSelect.appendChild(optOld);
                }

                let bwMatched = false;
                bws.forEach(bw => {
                    const opt = document.createElement('option');
                    opt.value = bw;
                    opt.textContent = bw;
                    if (bw === selectedBw) {
                        opt.selected = true;
                        bwMatched = true;
                    }
                    elBwSelect.appendChild(opt);
                });

                elBwSelect.onchange = () => {
                    if (elBwHidden) {
                        elBwHidden.value = elBwSelect.value;
                        elBwHidden.dispatchEvent(new Event('input', { bubbles: true }));
                        elBwHidden.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    triggerPriceLookup(true);
                };

                if (selectedBw && bwMatched) {
                    elBwSelect.value = selectedBw;
                    if (elBwHidden) elBwHidden.value = selectedBw;
                } else if (bws.length === 1) {
                    elBwSelect.value = bws[0];
                    if (elBwHidden) elBwHidden.value = bws[0];
                } else {
                    if (elBwHidden) elBwHidden.value = elBwSelect.value;
                }
            }

            if (elBwCustom) {
                elBwCustom.classList.add('hidden');
                elBwCustom.removeAttribute('required');
            }

            if (elBwHidden) {
                elBwHidden.setAttribute('required', '');
            }
        } else {
            // Produk non-matriks / custom
            if (elBwSelect) {
                elBwSelect.classList.add('hidden');
                elBwSelect.removeAttribute('required');
            }
            if (elBwCustom) {
                elBwCustom.classList.remove('hidden');
                elBwCustom.setAttribute('required', '');
                elBwCustom.oninput = () => {
                    if (elBwHidden) {
                        elBwHidden.value = elBwCustom.value;
                        elBwHidden.dispatchEvent(new Event('input', { bubbles: true }));
                        elBwHidden.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    triggerPriceLookup(true);
                };
                if (selectedBw) {
                    elBwCustom.value = selectedBw;
                    if (elBwHidden) elBwHidden.value = selectedBw;
                }
            }
            if (elBwHidden) {
                elBwHidden.setAttribute('required', '');
            }
        }
    }

    // Event listener saat paket dipilih
    elPaket.addEventListener('change', () => {
        const chosenPaket = elPaket.value;
        populateWilayah(chosenPaket, '');
        updateBandwidth(chosenPaket, '');
        elPaket.dispatchEvent(new Event('input', { bubbles: true }));
        triggerPriceLookup(true);
    });

    // Event listener saat wilayah dipilih
    if (elWilayah) {
        elWilayah.addEventListener('change', () => {
            elWilayah.dispatchEvent(new Event('input', { bubbles: true }));
            triggerPriceLookup(true);
        });
    }

    // Inisialisasi awal nilai dari server
    const initPaket   = initialValues?.paketisasi || '';
    const initWilayah = initialValues?.wilayah    || '';
    const initBw      = initialValues?.bandwidth  || '';

    populatePaket(initPaket);
    populateWilayah(initPaket, initWilayah);
    updateBandwidth(initPaket, initBw);
}
