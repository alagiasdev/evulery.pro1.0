/**
 * Evulery.Pro - Booking Widget JS (TheFork Style)
 * 4-step flow: Date (calendar) -> Time (grouped) -> Party Size -> Contact
 */
document.addEventListener('DOMContentLoaded', function() {
    const widget = document.getElementById('booking-widget');
    if (!widget) return;

    const config = window.BOOKING_CONFIG || {};
    const apiUrl = config.apiUrl || '';
    const slug = config.slug || '';
    const advanceMin = config.advanceMin || 0;
    const advanceMax = config.advanceMax || 60;

    // ===== STATE =====
    var state = {
        currentStep: 1,
        selectedDate: null,
        selectedTime: null,
        selectedPartySize: null,
        calendarMonth: new Date().getMonth(),
        calendarYear: new Date().getFullYear(),
        groupedSlots: [],
        todayBookings: 0,
        lastFetchPartySize: 2,
        lastFetchDate: null
    };

    // ===== ITALIAN LOCALE =====
    var MONTHS = [
        'Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
        'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'
    ];
    var DAYS_SHORT = ['lun','mar','mer','gio','ven','sab','dom'];

    // ===== DOM ELEMENTS =====
    function getEl(id) { return document.getElementById(id); }

    var steps = { 1: getEl('step-1'), 2: getEl('step-2'), 3: getEl('step-3'), 4: getEl('step-4'), confirm: getEl('step-confirm') };
    var progs = { 1: getEl('prog-1'), 2: getEl('prog-2'), 3: getEl('prog-3'), 4: getEl('prog-4') };
    var pills = { date: getEl('pill-date'), time: getEl('pill-time'), party: getEl('pill-party') };
    var slotsContainer = getEl('grouped-slots-container');
    var partyGrid = getEl('party-grid');
    var partyExtended = getEl('party-extended');
    var socialProof = getEl('social-proof');
    var socialProofText = getEl('social-proof-text');
    var loadingOverlay = getEl('loading');
    var errorContainer = getEl('error-container');
    var errorMessage = getEl('error-message');

    // ===== CALENDAR =====
    function renderCalendar() {
        var year = state.calendarYear;
        var month = state.calendarMonth;

        getEl('cal-month-label').textContent = MONTHS[month] + ' ' + year;

        // Day name headers
        var headerEl = getEl('cal-days-header');
        headerEl.innerHTML = DAYS_SHORT.map(function(d) {
            return '<div class="bw-cal-day-name">' + d + '</div>';
        }).join('');

        // First day of month (adjusted: 0=Mon)
        var firstDay = new Date(year, month, 1);
        var startDow = firstDay.getDay() - 1;
        if (startDow < 0) startDow = 6;

        var daysInMonth = new Date(year, month + 1, 0).getDate();

        var today = new Date();
        today.setHours(0,0,0,0);
        var minDate = new Date(today);
        minDate.setDate(minDate.getDate() + advanceMin);
        var maxDate = new Date(today);
        maxDate.setDate(maxDate.getDate() + advanceMax);

        var html = '';

        // Empty cells before first day
        for (var i = 0; i < startDow; i++) {
            html += '<div class="bw-cal-cell bw-cal-empty"></div>';
        }

        // Day cells
        for (var day = 1; day <= daysInMonth; day++) {
            var d = new Date(year, month, day);
            d.setHours(0,0,0,0);
            var dateStr = formatDateISO(d);

            var classes = 'bw-cal-cell';

            if (d.getTime() === today.getTime()) {
                classes += ' bw-cal-today';
            }

            if (d < minDate || d > maxDate) {
                classes += ' bw-cal-disabled';
            }

            if (state.selectedDate === dateStr) {
                classes += ' bw-cal-selected';
            }

            html += '<div class="' + classes + '" data-date="' + dateStr + '">' + day + '</div>';
        }

        getEl('cal-grid').innerHTML = html;

        // Bind clicks
        getEl('cal-grid').querySelectorAll('.bw-cal-cell:not(.bw-cal-disabled):not(.bw-cal-empty)').forEach(function(cell) {
            cell.addEventListener('click', function() {
                selectDate(this.dataset.date);
            });
        });

        // Nav buttons
        var prevBtn = getEl('cal-prev');
        var nextBtn = getEl('cal-next');
        var nowMonth = today.getMonth();
        var nowYear = today.getFullYear();
        prevBtn.disabled = (year === nowYear && month <= nowMonth);

        var nextMonthFirst = new Date(year, month + 1, 1);
        nextBtn.disabled = (nextMonthFirst > maxDate);
    }

    getEl('cal-prev').addEventListener('click', function() {
        state.calendarMonth--;
        if (state.calendarMonth < 0) { state.calendarMonth = 11; state.calendarYear--; }
        renderCalendar();
    });

    getEl('cal-next').addEventListener('click', function() {
        state.calendarMonth++;
        if (state.calendarMonth > 11) { state.calendarMonth = 0; state.calendarYear++; }
        renderCalendar();
    });

    function selectDate(dateStr) {
        state.selectedDate = dateStr;
        state.selectedTime = null;
        updatePill('date', formatDatePill(dateStr));
        if (pills.time) pills.time.style.display = 'none';
        renderCalendar();
        setTimeout(function() { goToStep(2); }, 250);
    }

    // ===== TIME SLOTS =====
    function loadGroupedSlots() {
        var partySize = state.selectedPartySize || 2;
        state.lastFetchPartySize = partySize;
        state.lastFetchDate = state.selectedDate;

        slotsContainer.innerHTML = '<div class="bw-loading-inline"><div class="spinner-border spinner-border-sm"></div> Caricamento orari...</div>';

        fetch(apiUrl + '/tenants/' + slug + '/availability?date=' + state.selectedDate + '&party_size=' + partySize + '&grouped=1')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    slotsContainer.innerHTML = '<div class="bw-no-slots">Errore nel caricamento.</div>';
                    return;
                }

                state.groupedSlots = data.data.grouped_slots || [];
                state.todayBookings = data.data.today_bookings || 0;

                showSocialProof(state.todayBookings);
                renderGroupedSlots();
            })
            .catch(function() {
                slotsContainer.innerHTML = '<div class="bw-no-slots">Errore di connessione.</div>';
            });
    }

    function renderGroupedSlots() {
        if (!state.groupedSlots.length) {
            slotsContainer.innerHTML = '<div class="bw-no-slots">Nessun orario disponibile per questa data.</div>';
            return;
        }

        var html = '';
        state.groupedSlots.forEach(function(group) {
            html += '<div class="bw-slot-group">';
            html += '<div class="bw-slot-group-label">' + escapeHtml(group.display_name) + '</div>';
            html += '<div class="bw-slot-group-times">';
            group.slots.forEach(function(slot) {
                var active = (state.selectedTime === slot.time) ? ' bw-slot-active' : '';
                var disabled = !slot.is_available ? ' bw-slot-disabled' : '';
                var title = slot.is_available
                    ? slot.available_covers + ' posti disponibili'
                    : 'Non disponibile';
                html += '<button type="button" class="bw-slot-btn' + active + disabled + '" data-time="' + slot.time + '" title="' + title + '"' + (!slot.is_available ? ' disabled' : '') + '>' + slot.time + '</button>';
            });
            html += '</div></div>';
        });
        slotsContainer.innerHTML = html;

        // Bind clicks
        slotsContainer.querySelectorAll('.bw-slot-btn:not(.bw-slot-disabled)').forEach(function(btn) {
            btn.addEventListener('click', function() {
                slotsContainer.querySelectorAll('.bw-slot-btn').forEach(function(b) { b.classList.remove('bw-slot-active'); });
                this.classList.add('bw-slot-active');
                state.selectedTime = this.dataset.time;
                updatePill('time', state.selectedTime);
                setTimeout(function() { goToStep(3); }, 250);
            });
        });
    }

    // ===== PARTY SIZE =====
    function renderPartyGrid() {
        var html = '';
        for (var i = 1; i <= 12; i++) {
            html += '<button type="button" class="bw-party-btn" data-size="' + i + '">' + i + '</button>';
        }
        partyGrid.innerHTML = html;

        var extHtml = '';
        for (var i = 13; i <= 20; i++) {
            extHtml += '<button type="button" class="bw-party-btn" data-size="' + i + '">' + i + '</button>';
        }
        partyExtended.innerHTML = extHtml;

        bindPartyClicks();
    }

    function bindPartyClicks() {
        widget.querySelectorAll('.bw-party-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                widget.querySelectorAll('.bw-party-btn').forEach(function(b) { b.classList.remove('bw-party-active'); });
                this.classList.add('bw-party-active');
                state.selectedPartySize = parseInt(this.dataset.size);
                updatePill('party', state.selectedPartySize + ' Pers.');
                setTimeout(function() { goToStep(4); }, 250);
            });
        });
    }

    getEl('party-more-link').addEventListener('click', function(e) {
        e.preventDefault();
        var isHidden = partyExtended.style.display === 'none';
        partyExtended.style.display = isHidden ? 'grid' : 'none';
        this.innerHTML = isHidden
            ? 'Nascondi opzioni extra <i class="bi bi-dash"></i>'
            : 'Opzioni per piu persone <i class="bi bi-plus"></i>';
    });

    // ===== STEP NAVIGATION =====
    function goToStep(stepNum) {
        // If going to step 2, check if we need to re-fetch slots
        if (stepNum === 2) {
            var currentParty = state.selectedPartySize || 2;
            if (currentParty !== state.lastFetchPartySize || state.selectedDate !== state.lastFetchDate || !slotsContainer.querySelector('.bw-slot-group')) {
                loadGroupedSlots();
            }
        }

        // Hide all steps
        Object.keys(steps).forEach(function(key) {
            if (steps[key]) steps[key].style.display = 'none';
        });

        // Show target
        if (steps[stepNum]) steps[stepNum].style.display = 'block';

        // Show progress bar (might be hidden after confirmation)
        getEl('step-progress').style.display = 'flex';

        // Update progress bar
        for (var i = 1; i <= 4; i++) {
            var prog = progs[i];
            if (!prog) continue;
            prog.classList.remove('active', 'completed');
            if (i === stepNum) {
                prog.classList.add('active');
            } else if (i < stepNum) {
                prog.classList.add('completed');
            }
        }

        state.currentStep = stepNum;
        widget.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Progress bar clicks (navigate back to completed steps)
    [1,2,3,4].forEach(function(step) {
        if (progs[step]) {
            progs[step].addEventListener('click', function() {
                if (step < state.currentStep) {
                    goToStep(step);
                }
            });
        }
    });

    // Back buttons
    getEl('btn-back-1').addEventListener('click', function() { goToStep(1); });
    getEl('btn-back-2').addEventListener('click', function() { goToStep(2); });
    getEl('btn-back-3').addEventListener('click', function() { goToStep(3); });

    // ===== PILLS =====
    function updatePill(type, text) {
        var pill = pills[type];
        if (!pill) return;
        pill.textContent = text;
        pill.style.display = 'inline-block';
    }

    function formatDatePill(dateStr) {
        var today = new Date();
        today.setHours(0,0,0,0);
        var d = new Date(dateStr + 'T00:00:00');
        var tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        if (d.getTime() === today.getTime()) return 'Oggi';
        if (d.getTime() === tomorrow.getTime()) return 'Domani';
        return d.getDate() + ' ' + MONTHS[d.getMonth()].substring(0, 3);
    }

    // ===== SUBMIT =====
    getEl('btn-submit').addEventListener('click', function() {
        var firstName = getEl('booking-first-name').value.trim();
        var lastName = getEl('booking-last-name').value.trim();
        var phone = getEl('booking-phone').value.trim();
        var email = getEl('booking-email').value.trim();
        var notes = getEl('booking-notes').value.trim();

        if (!firstName || !lastName || !phone || !email) {
            showError('Compila tutti i campi obbligatori.');
            return;
        }

        if (!email.includes('@') || !email.includes('.')) {
            showError('Inserisci un indirizzo email valido.');
            return;
        }

        loadingOverlay.style.display = 'flex';
        hideError();

        var body = {
            date: state.selectedDate,
            time: state.selectedTime,
            party_size: state.selectedPartySize,
            first_name: firstName,
            last_name: lastName,
            phone: phone,
            email: email
        };

        if (notes) {
            body.notes = notes;
        }

        fetch(apiUrl + '/tenants/' + slug + '/reservations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loadingOverlay.style.display = 'none';

            if (data.success) {
                var details = getEl('confirmation-details');
                var confHtml =
                    '<div class="bw-conf-summary">' +
                    '<div class="bw-conf-row"><span>Data</span><strong>' + formatDatePill(body.date) + ' (' + formatDateDMY(body.date) + ')</strong></div>' +
                    '<div class="bw-conf-row"><span>Orario</span><strong>' + body.time + '</strong></div>' +
                    '<div class="bw-conf-row"><span>Persone</span><strong>' + body.party_size + '</strong></div>' +
                    '<div class="bw-conf-row"><span>Nome</span><strong>' + escapeHtml(firstName + ' ' + lastName) + '</strong></div>';

                if (notes) {
                    confHtml += '<div class="bw-conf-row"><span>Note</span><strong>' + escapeHtml(notes) + '</strong></div>';
                }

                confHtml += '</div>' +
                    '<p>Riceverai una conferma via email a <strong>' + escapeHtml(email) + '</strong></p>';

                details.innerHTML = confHtml;

                if (data.data && data.data.deposit_required) {
                    details.innerHTML += '<div class="bw-deposit-info" style="margin-top:12px;">Verrai reindirizzato alla pagina di pagamento per la caparra.</div>';
                }

                // Hide progress, show confirmation
                getEl('step-progress').style.display = 'none';
                Object.keys(steps).forEach(function(key) {
                    if (steps[key]) steps[key].style.display = 'none';
                });
                steps.confirm.style.display = 'block';

                if (data.data && data.data.stripe_checkout_url) {
                    setTimeout(function() { window.location.href = data.data.stripe_checkout_url; }, 1500);
                }
            } else {
                var msg = data.error ? data.error.message : 'Errore nella prenotazione.';

                if (data.suggestions && data.suggestions.length > 0) {
                    goToStep(2);
                    showError('Orario non disponibile. Scegli un altro orario.');
                } else {
                    showError(msg);
                }
            }
        })
        .catch(function() {
            loadingOverlay.style.display = 'none';
            showError('Errore di connessione. Riprova.');
        });
    });

    // ===== SOCIAL PROOF =====
    function showSocialProof(count) {
        if (count > 0) {
            socialProofText.textContent = 'Gia ' + count + ' prenotazioni per oggi';
            socialProof.style.display = 'flex';
        } else {
            socialProof.style.display = 'none';
        }
    }

    // ===== ERROR =====
    function showError(msg) {
        errorContainer.style.display = 'block';
        errorMessage.textContent = msg;
        setTimeout(hideError, 5000);
    }

    function hideError() {
        errorContainer.style.display = 'none';
    }

    // ===== UTILITIES =====
    function formatDateISO(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function formatDateDMY(dateStr) {
        var parts = dateStr.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ===== INIT =====
    renderCalendar();
    renderPartyGrid();

    // Load social proof count on page load
    fetch(apiUrl + '/tenants/' + slug + '/availability?date=' + formatDateISO(new Date()) + '&party_size=2&grouped=1')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.data.today_bookings) {
                showSocialProof(data.data.today_bookings);
            }
        })
        .catch(function() {});
});
