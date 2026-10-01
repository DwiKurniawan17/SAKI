/**
 * produk-selector.js
 * Logika pemilihan Paket Produk & Bandwidth langsung dari Katalog Price List RSO.
 * Sesuai kebutuhan:
 * 1. Kategori produk dihilangkan dari tampilan UI (disimpan via hidden input untuk kompatibilitas).
 * 2. Paket Produk HANYA menampilkan produk yang diinput oleh RSO pada katalog / Excel matriks.
 * 3. Bandwidth HANYA menampilkan bandwidth yang ada pada katalog / Excel matriks untuk produk tersebut.
 * 4. Kompatibilitas terjaga: data simulasi lama tetap bisa ditampilkan tanpa error.
 */

function initProdukSelector(prefix, initialValues, dbCatalog = null) {
    const elKategori  = document.getElementById(`${prefix}_kategori`);
    const elPaket     = document.getElementById(`${prefix}_paketisasi`);
    const elBwWrap    = document.getElementById(`${prefix}_bw_wrap`);
    const elBwSelect  = document.getElementById(`${prefix}_bw_select`);
    const elBwCustom  = document.getElementById(`${prefix}_bw_custom`);
    const elBwHidden  = document.getElementById(`${prefix}_bw_hidden`);

    if (!elPaket) return;

    // dbCatalog format: { "Astinet": { kategori: "connectivity", bw: ["1 Mbps", "2 Mbps", ...] }, ... }
    const catalog = (dbCatalog && typeof dbCatalog === 'object') ? dbCatalog : {};

    // 1. Populasi Paket Produk (hanya produk yang ada di katalog RSO)
    function populatePaket(selectedPaket) {
        elPaket.innerHTML = '<option value="">-- Pilih Paket Produk --</option>';
        const paketKeys = Object.keys(catalog);

        // Jika katalog kosong
        if (paketKeys.length === 0 && !selectedPaket) {
            elPaket.innerHTML = '<option value="">-- Katalog Belum Diisi RSO --</option>';
            return;
        }

        // Jika ada selectedPaket dari data lama tapi tidak ada di katalog saat ini,
        // tetap sertakan sebagai opsi agar data lama tidak hilang (backward-compatible)
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

    // 2. Update Bandwidth dropdown sesuai paket yang dipilih
    function updateBandwidth(paket, selectedBw) {
        if (!elBwWrap) return;

        if (!paket) {
            if (elBwSelect) {
                elBwSelect.innerHTML = '<option value="">-- Pilih Paket terlebih dahulu --</option>';
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
            // Tampilkan dropdown bandwidth resmi dari matriks katalog
            if (elBwSelect) {
                elBwSelect.classList.remove('hidden');
                elBwSelect.setAttribute('required', '');
                elBwSelect.innerHTML = '<option value="">-- Pilih Bandwidth --</option>';

                // Jika data lama punya bandwidth di luar matriks baru, tetap sertakan agar aman
                if (selectedBw && !bws.includes(selectedBw)) {
                    const optOld = document.createElement('option');
                    optOld.value = selectedBw;
                    optOld.textContent = selectedBw;
                    optOld.selected = true;
                    elBwSelect.appendChild(optOld);
                }

                bws.forEach(bw => {
                    const opt = document.createElement('option');
                    opt.value = bw;
                    opt.textContent = bw;
                    if (bw === selectedBw) opt.selected = true;
                    elBwSelect.appendChild(opt);
                });

                elBwSelect.onchange = () => {
                    if (elBwHidden) {
                        elBwHidden.value = elBwSelect.value;
                        elBwHidden.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                };

                if (selectedBw) {
                    elBwSelect.value = selectedBw;
                    if (elBwHidden) elBwHidden.value = selectedBw;
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
            // Jika produk tidak punya daftar bandwidth (misal custom/non-matriks), tampilkan input text
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
                        elBwHidden.dispatchEvent(new Event('change', { bubbles: true }));
                    }
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
        updateBandwidth(elPaket.value, '');
        // Trigger bubble change agar lookupPrice() di parent form terpanggil
        elPaket.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Inisialisasi awal nilai dari server
    const initPaket = initialValues?.paketisasi || '';
    const initBw    = initialValues?.bandwidth  || '';

    populatePaket(initPaket);
    updateBandwidth(initPaket, initBw);
}
