document.addEventListener('DOMContentLoaded', () => {
    const openMenuBtn = document.getElementById('open-menu-btn');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    // Fungsi untuk membuka sidebar mobile
    if (openMenuBtn) {
        openMenuBtn.addEventListener('click', () => {
            if (!mobileMenu) return;
            // remove translate to slide in and fade to opacity-100
            mobileMenu.classList.remove('-translate-x-full');
            mobileMenu.classList.remove('opacity-0');
            mobileMenu.classList.add('opacity-100');
            mobileMenu.setAttribute('aria-hidden', 'false');
            openMenuBtn.setAttribute('aria-expanded', 'true');
            // lock body scroll
            document.body.style.overflow = 'hidden';
            // set focus to first focusable element in menu for accessibility
            const firstFocusable = mobileMenu.querySelector('a, button, input, [tabindex]');
            if (firstFocusable) firstFocusable.focus();
            // enable focus trap
            enableFocusTrap();
        });
    }

    // Fungsi untuk menutup sidebar mobile
    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', () => {
            if (!mobileMenu) return;
            // add opacity-0 to fade out, then translate to slide out
            // disable focus trap first to avoid blocking focus changes
            disableFocusTrap();
            // return focus to menu button before hiding content to avoid aria-hidden on focused element
            if (openMenuBtn) {
                try { openMenuBtn.focus(); } catch (e) {}
                openMenuBtn.setAttribute('aria-expanded', 'false');
            }
            // restore body scroll
            document.body.style.overflow = '';
            // fade/slide out, then mark hidden for assistive tech
            mobileMenu.classList.remove('opacity-100');
            mobileMenu.classList.add('opacity-0');
            mobileMenu.classList.add('-translate-x-full');
            mobileMenu.setAttribute('aria-hidden', 'true');
        });
    }

    // Close mobile menu when clicking outside the panel (on overlay)
    if (mobileMenu) {
        mobileMenu.addEventListener('click', (e) => {
            // the overlay is the mobileMenu itself; the inner panel is the first child
            const panel = mobileMenu.querySelector('div');
            if (panel && !panel.contains(e.target)) {
                // disable focus trap first
                disableFocusTrap();
                // return focus to menu button before hiding content
                if (openMenuBtn) {
                    try { openMenuBtn.focus(); } catch (e) {}
                    openMenuBtn.setAttribute('aria-expanded', 'false');
                }
                document.body.style.overflow = '';
                mobileMenu.classList.remove('opacity-100');
                mobileMenu.classList.add('opacity-0');
                mobileMenu.classList.add('-translate-x-full');
                mobileMenu.setAttribute('aria-hidden', 'true');
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !mobileMenu.classList.contains('-translate-x-full')) {
                // disable focus trap first
                disableFocusTrap();
                // return focus to menu button before marking content hidden
                if (openMenuBtn) {
                    try { openMenuBtn.focus(); } catch (e) {}
                    openMenuBtn.setAttribute('aria-expanded', 'false');
                }
                document.body.style.overflow = '';
                mobileMenu.classList.remove('opacity-100');
                mobileMenu.classList.add('opacity-0');
                mobileMenu.classList.add('-translate-x-full');
                mobileMenu.setAttribute('aria-hidden', 'true');
            }
        });
    }

    // ---- Focus trap implementation ----
    let previouslyFocusedElement = null;
    let focusTrapHandler = null;

    function getFocusableElements(container) {
        if (!container) return [];
        const selectors = 'a[href], area[href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, [tabindex]:not([tabindex="-1"]), [contenteditable]';
        return Array.from(container.querySelectorAll(selectors)).filter(el => el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length);
    }

    function enableFocusTrap() {
        if (!mobileMenu) return;
        previouslyFocusedElement = document.activeElement;
        const focusable = getFocusableElements(mobileMenu);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        focusTrapHandler = function (e) {
            if (e.key !== 'Tab') return;
            // recompute focusable elements in case DOM changed
            const list = getFocusableElements(mobileMenu);
            const firstEl = list[0];
            const lastEl = list[list.length - 1];
            if (!firstEl) {
                e.preventDefault();
                return;
            }
            if (e.shiftKey) { // SHIFT + TAB
                if (document.activeElement === firstEl) {
                    e.preventDefault();
                    lastEl.focus();
                }
            } else { // TAB
                if (document.activeElement === lastEl) {
                    e.preventDefault();
                    firstEl.focus();
                }
            }
        };

        document.addEventListener('keydown', focusTrapHandler, true);
    }

    function disableFocusTrap() {
        if (focusTrapHandler) {
            document.removeEventListener('keydown', focusTrapHandler, true);
            focusTrapHandler = null;
        }
        if (previouslyFocusedElement && previouslyFocusedElement.focus) {
            previouslyFocusedElement.focus();
            previouslyFocusedElement = null;
        }
    }

    // Fungsi untuk menangani upload file (klik dan drag-drop)
    document.querySelectorAll('.upload-zone').forEach(zone => {
        const zoneId = zone.dataset.zoneId;
        const input = document.getElementById(`${zoneId}-upload`);
        const filenameDisplay = document.getElementById(`${zoneId}-filename`);
        const filenameSpan = filenameDisplay.querySelector('span');
        const dropLabel = zone.querySelector('label');

        if (!input || !filenameDisplay || !filenameSpan || !dropLabel) return;

        // Menampilkan nama file saat dipilih via klik
        input.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                    // Custom tampilan filenameDisplay
                    filenameDisplay.innerHTML = `
                        <div style="display:flex;align-items:center;gap:12px;background:#fff;border:1.5px solid #e5e7eb;border-radius:10px;padding:16px 20px;box-shadow:0 1px 4px 0 rgba(0,0,0,0.03);">
                            <svg style="width:32px;height:32px;flex-shrink:0;" viewBox="0 0 48 48" fill="none"><rect width="48" height="48" rx="8" fill="#E8F0FE"/><path d="M16 14a2 2 0 0 1 2-2h8.586a2 2 0 0 1 1.414.586l5.414 5.414A2 2 0 0 1 34 19.414V34a2 2 0 0 1-2 2H18a2 2 0 0 1-2-2V14Z" fill="#34A853"/><path d="M28 12v5a2 2 0 0 0 2 2h5" fill="#34A853"/></svg>
                            <span style="font-size:1rem;font-weight:500;color:#222;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${e.target.files[0].name}</span>
                            <button type="button" aria-label="Hapus file" style="background:transparent;border:none;outline:none;cursor:pointer;padding:4px;display:flex;align-items:center;" class="remove-file-btn">
                                <svg style="width:22px;height:22px;color:#888;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    `;
                    filenameDisplay.classList.remove('hidden');
                    zone.classList.add('hidden');
                    // Event untuk tombol hapus
                    filenameDisplay.querySelector('.remove-file-btn').onclick = function() {
                        input.value = '';
                        filenameDisplay.classList.add('hidden');
                        zone.classList.remove('hidden');
                    };
            } else {
                    filenameDisplay.classList.add('hidden');
                    zone.classList.remove('hidden');
            }
        });

        // Efek visual saat drag-over
        dropLabel.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropLabel.classList.add('drag-over');
        });

        // Efek visual saat drag-leave
        dropLabel.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropLabel.classList.remove('drag-over');
        });

        // Menangani file saat di-drop
        dropLabel.addEventListener('drop', (e) => {
            e.preventDefault();
            dropLabel.classList.remove('drag-over');
                if (e.dataTransfer.files.length > 0) {
                        input.files = e.dataTransfer.files;
                        // Custom tampilan filenameDisplay
                        filenameDisplay.innerHTML = `
                            <div style="display:flex;align-items:center;gap:12px;background:#fff;border:1.5px solid #e5e7eb;border-radius:10px;padding:16px 20px;box-shadow:0 1px 4px 0 rgba(0,0,0,0.03);">
                                <svg style="width:32px;height:32px;flex-shrink:0;" viewBox="0 0 48 48" fill="none"><rect width="48" height="48" rx="8" fill="#E8F0FE"/><path d="M16 14a2 2 0 0 1 2-2h8.586a2 2 0 0 1 1.414.586l5.414 5.414A2 2 0 0 1 34 19.414V34a2 2 0 0 1-2 2H18a2 2 0 0 1-2-2V14Z" fill="#34A853"/><path d="M28 12v5a2 2 0 0 0 2 2h5" fill="#34A853"/></svg>
                                <span style="font-size:1rem;font-weight:500;color:#222;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${e.dataTransfer.files[0].name}</span>
                                <button type="button" aria-label="Hapus file" style="background:transparent;border:none;outline:none;cursor:pointer;padding:4px;display:flex;align-items:center;" class="remove-file-btn">
                                    <svg style="width:22px;height:22px;color:#888;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        `;
                        filenameDisplay.classList.remove('hidden');
                        zone.classList.add('hidden');
                        // Event untuk tombol hapus
                        filenameDisplay.querySelector('.remove-file-btn').onclick = function() {
                            input.value = '';
                            filenameDisplay.classList.add('hidden');
                            zone.classList.remove('hidden');
                        };
                }
        // Hilangkan event klik pada filenameDisplay, hanya tombol X yang reset
        });

        // Fitur upload peserta
        if (zoneId === 'peserta') {
            const uploadBtn = zone.parentElement.querySelector('button');
            if (uploadBtn) {
                uploadBtn.addEventListener('click', function () {
                    if (!input.files || input.files.length === 0) {
                        Swal.fire({
                            toast: true,
                                position: 'top',
                            icon: 'warning',
                            title: 'Silakan pilih file Excel terlebih dahulu.',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        return;
                    }
                    const file = input.files[0];
                    const formData = new FormData();
                    formData.append('file', file);
                    uploadBtn.disabled = true;
                    uploadBtn.textContent = 'Mengupload...';
                    fetch('api/upload_peserta.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(t => { throw new Error(t || response.statusText); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        uploadBtn.disabled = false;
                        uploadBtn.textContent = 'Upload Data Peserta';
                        if (data.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top',
                                icon: 'success',
                                title: 'Upload berhasil! Data peserta berhasil ditambahkan: ' + data.inserted,
                                showConfirmButton: false,
                                timer: 3500,
                                timerProgressBar: true
                            });
                            if (typeof refreshRiwayatUpload === 'function') refreshRiwayatUpload();
                        } else {
                            Swal.fire({
                                toast: true,
                                position: 'top',
                                icon: 'error',
                                title: 'Gagal upload: ' + (data.error || 'Terjadi kesalahan.'),
                                showConfirmButton: false,
                                timer: 3500,
                                timerProgressBar: true
                            });
                        }
                    })
                    .catch(err => {
                        uploadBtn.disabled = false;
                        uploadBtn.textContent = 'Upload Data Peserta';
                        Swal.fire({
                            toast: true,
                                position: 'top',
                            icon: 'error',
                            title: 'Gagal upload: ' + (err && err.message ? err.message : err),
                            showConfirmButton: false,
                            timer: 3500,
                            timerProgressBar: true
                        });
                    });
                });
            }
        }
    });

    // Handler untuk tombol Upload Invoice (bukan zone-based)
    const btnUploadInvoice = document.getElementById('btn-upload-invoice');
    if (btnUploadInvoice) {
        btnUploadInvoice.addEventListener('click', function() {
            // Langsung redirect ke halaman manajemen invoice
            window.location.href = 'manajemen_invoice.php';
        });
    }
});