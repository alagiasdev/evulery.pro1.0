/**
 * Evulery.Pro - Booking Widget JS (TheFork Style)
 * 4-step flow: Date (calendar) -> Party Size -> Time (grouped) -> Contact
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
        lastFetchDate: null,
        selectedDiscount: 0,
        closedDates: {},       // { 'YYYY-MM': ['YYYY-MM-DD', ...] }
        closedDatesLoading: {}, // { 'YYYY-MM': true }
        workingWeekdays: null,  // [0..6] weekdays (Mon=0..Sun=6) with at least one active slot; null = unknown
        maxPartyForDate: null   // max available covers across all slots for selectedDate; null = unknown
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

    // ===== CLOSED DATES =====
    function fetchClosedDates(year, month) {
        var key = year + '-' + String(month + 1).padStart(2, '0');
        if (state.closedDates[key] || state.closedDatesLoading[key]) return;

        state.closedDatesLoading[key] = true;
        var from = key + '-01';
        var lastDay = new Date(year, month + 1, 0).getDate();
        var to = key + '-' + String(lastDay).padStart(2, '0');

        fetch(apiUrl + '/tenants/' + slug + '/closures?from=' + from + '&to=' + to)
            .then(function(r) { return checkApiResponse(r); })
            .then(function(data) {
                if (data.success && data.data.closed_dates) {
                    state.closedDates[key] = data.data.closed_dates;
                } else {
                    state.closedDates[key] = [];
                }
                if (data.success && Array.isArray(data.data.working_weekdays)) {
                    state.workingWeekdays = data.data.working_weekdays.map(Number);
                }
                delete state.closedDatesLoading[key];
                renderCalendar();
            })
            .catch(function() {
                state.closedDates[key] = [];
                delete state.closedDatesLoading[key];
            });
    }

    function isDateClosed(dateStr) {
        var key = dateStr.substring(0, 7); // 'YYYY-MM'
        var dates = state.closedDates[key];
        return dates && dates.indexOf(dateStr) !== -1;
    }

    // ===== CALENDAR =====
    function renderCalendar() {
        var year = state.calendarYear;
        var month = state.calendarMonth;

        getEl('cal-month-label').textContent = MONTHS[month] + ' ' + year;

        // Fetch closed dates for this month (async, re-renders when ready)
        fetchClosedDates(year, month);

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
            var isPast = d < today;
            var isClosed = !isPast && isDateClosed(dateStr);

            // Weekday not operative (no active slots for that day-of-week)
            var dow = d.getDay() - 1; if (dow < 0) dow = 6; // Mon=0..Sun=6
            var isNonWorking = !isPast
                && Array.isArray(state.workingWeekdays)
                && state.workingWeekdays.indexOf(dow) === -1;

            if (d.getTime() === today.getTime()) {
                classes += ' bw-cal-today';
            }

            if (isPast || d < minDate || d > maxDate || isClosed || isNonWorking) {
                classes += ' bw-cal-disabled';
            }

            if (isClosed) {
                classes += ' bw-cal-closed';
            } else if (isNonWorking && d >= minDate && d <= maxDate) {
                classes += ' bw-cal-nonworking';
            }

            if (state.selectedDate === dateStr) {
                classes += ' bw-cal-selected';
            }

            var cellTitle = '';
            if (isClosed) cellTitle = ' title="Chiuso"';
            else if (isNonWorking && d >= minDate && d <= maxDate) cellTitle = ' title="Non operativo"';

            html += '<div class="' + classes + '" data-date="' + dateStr + '"' + cellTitle + '>' + day + '</div>';
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
        state.maxPartyForDate = null;
        updatePill('date', formatDatePill(dateStr));
        if (pills.time) pills.time.style.display = 'none';
        renderCalendar();
        fetchMaxParty(dateStr);
        setTimeout(function() { goToStep(2); }, 250);
    }

    // ===== MAX PARTY FOR DATE =====
    function fetchMaxParty(dateStr) {
        applyPartyAvailability(); // reset grid to loading state
        fetch(apiUrl + '/tenants/' + slug + '/availability?date=' + dateStr + '&party_size=1')
            .then(function(r) { return checkApiResponse(r); })
            .then(function(data) {
                if (!data.success || !Array.isArray(data.data.slots)) {
                    state.maxPartyForDate = null;
                } else {
                    var max = 0;
                    data.data.slots.forEach(function(s) {
                        var avail = parseInt(s.available_covers, 10) || 0;
                        if (avail > max) max = avail;
                    });
                    state.maxPartyForDate = max;
                }
                applyPartyAvailability();
            })
            .catch(function() {
                state.maxPartyForDate = null;
                applyPartyAvailability();
            });
    }

    function applyPartyAvailability() {
        var max = state.maxPartyForDate;
        var hint = getEl('party-max-hint');
        widget.querySelectorAll('.bw-party-btn').forEach(function(btn) {
            var size = parseInt(btn.dataset.size, 10) || 0;
            var disabled = (max !== null && size > max);
            btn.classList.toggle('bw-party-disabled', disabled);
            if (disabled) {
                btn.setAttribute('title', 'Non disponibile per questa data');
                btn.setAttribute('aria-disabled', 'true');
            } else {
                btn.removeAttribute('title');
                btn.removeAttribute('aria-disabled');
            }
        });
        if (hint) {
            if (max === null) {
                hint.textContent = '';
                hint.style.display = 'none';
            } else if (max === 0) {
                hint.textContent = 'Nessun posto disponibile in questa data';
                hint.style.display = '';
            } else {
                hint.textContent = 'Capienza disponibile: fino a ' + max + (max === 1 ? ' persona' : ' persone');
                hint.style.display = '';
            }
        }
    }

    // ===== TIME SLOTS =====
    function loadGroupedSlots() {
        var partySize = state.selectedPartySize || 2;
        state.lastFetchPartySize = partySize;
        state.lastFetchDate = state.selectedDate;

        slotsContainer.innerHTML = '<div class="bw-loading-inline"><div class="spinner-border spinner-border-sm"></div> Caricamento orari...</div>';

        fetch(apiUrl + '/tenants/' + slug + '/availability?date=' + state.selectedDate + '&party_size=' + partySize + '&grouped=1')
            .then(function(r) { return checkApiResponse(r); })
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
        var hasAnySlot = false;
        state.groupedSlots.forEach(function(group) {
            // Filter out past slots for the public widget
            var visibleSlots = group.slots.filter(function(slot) { return !slot.is_past; });
            if (!visibleSlots.length) return;
            hasAnySlot = true;

            html += '<div class="bw-slot-group">';
            html += '<div class="bw-slot-group-label">' + escapeHtml(group.display_name) + '</div>';
            html += '<div class="bw-slot-group-times">';
            visibleSlots.forEach(function(slot) {
                var active = (state.selectedTime === slot.time) ? ' bw-slot-active' : '';
                var disabled = !slot.is_available ? ' bw-slot-disabled' : '';
                var title = slot.is_available
                    ? slot.available_covers + ' posti disponibili'
                    : 'Non disponibile';
                var promoBadge = (slot.discount_percent && slot.discount_percent > 0)
                    ? '<span class="bw-promo-badge">-' + slot.discount_percent + '%</span>'
                    : '';
                html += '<button type="button" class="bw-slot-btn' + active + disabled + '" data-time="' + slot.time + '" data-discount="' + (slot.discount_percent || 0) + '" title="' + title + '"' + (!slot.is_available ? ' disabled' : '') + '>' + slot.time + promoBadge + '</button>';
            });
            html += '</div></div>';
        });

        if (!hasAnySlot) {
            slotsContainer.innerHTML = '<div class="bw-no-slots">Nessun orario disponibile per questa data.</div>';
            return;
        }

        slotsContainer.innerHTML = html;

        // Bind clicks
        slotsContainer.querySelectorAll('.bw-slot-btn:not(.bw-slot-disabled)').forEach(function(btn) {
            btn.addEventListener('click', function() {
                slotsContainer.querySelectorAll('.bw-slot-btn').forEach(function(b) { b.classList.remove('bw-slot-active'); });
                this.classList.add('bw-slot-active');
                state.selectedTime = this.dataset.time;
                state.selectedDiscount = parseInt(this.dataset.discount) || 0;
                updatePill('time', state.selectedTime);
                setTimeout(function() { goToStep(4); }, 250);
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

    function updateDepositInfo() {
        var el = getEl('deposit-text');
        var wrap = getEl('deposit-info');
        if (!el || !config.depositEnabled) return;
        var base = config.depositAmount || 0;
        var mode = config.depositMode || 'per_table';
        var party = state.selectedPartySize || 2;
        var minParty = config.depositMinParty || 0;

        // Hide deposit info if below threshold
        if (wrap && minParty > 0 && party < minParty) {
            wrap.style.display = 'none';
            return;
        }
        if (wrap) wrap.style.display = '';

        if (mode === 'per_person') {
            var total = (base * party).toFixed(2);
            el.innerHTML = 'Caparra richiesta: <strong>&euro;' + total + '</strong> (&euro;' + parseFloat(base).toFixed(2) + ' &times; ' + party + ' persone)';
        } else {
            el.innerHTML = 'Caparra richiesta: <strong>&euro;' + parseFloat(base).toFixed(2) + '</strong>';
        }
    }

    function bindPartyClicks() {
        widget.querySelectorAll('.bw-party-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (this.classList.contains('bw-party-disabled')) return;
                widget.querySelectorAll('.bw-party-btn').forEach(function(b) { b.classList.remove('bw-party-active'); });
                this.classList.add('bw-party-active');
                state.selectedPartySize = parseInt(this.dataset.size);
                state.selectedTime = null;
                if (pills.time) pills.time.style.display = 'none';
                updatePill('party', state.selectedPartySize + ' Pers.');
                updateDepositInfo();
                setTimeout(function() { goToStep(3); }, 250);
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
        // If going to step 3 (time slots), check if we need to re-fetch slots
        if (stepNum === 3) {
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

    // ===== INLINE VALIDATION =====
    var validationRules = {
        'booking-first-name': { required: true, msg: 'Inserisci il nome' },
        'booking-last-name':  { required: true, msg: 'Inserisci il cognome' },
        'booking-phone':      { required: true, msg: 'Inserisci il telefono', minDigits: 8, msgFormat: 'Inserisci un numero valido (min. 8 cifre)' },
        'booking-email':      { required: true, msg: 'Inserisci l\'email', email: true, msgFormat: 'Inserisci un indirizzo email valido' }
    };

    function validateField(fieldId) {
        var el = getEl(fieldId);
        if (!el) return true;
        var val = el.value.trim();
        var rule = validationRules[fieldId];
        if (!rule) return true;

        var group = el.closest('.bw-form-group');
        var errorEl = group.querySelector('.bw-field-error');

        // Create error element if missing
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'bw-field-error';
            errorEl.innerHTML = '<i class="bi bi-exclamation-circle"></i> <span></span>';
            group.appendChild(errorEl);
        }
        var errorText = errorEl.querySelector('span');

        // Reset
        group.classList.remove('bw-has-error', 'bw-has-success');

        if (rule.required && !val) {
            group.classList.add('bw-has-error');
            errorText.textContent = rule.msg;
            return false;
        }

        if (val && rule.email) {
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(val)) {
                group.classList.add('bw-has-error');
                errorText.textContent = rule.msgFormat;
                return false;
            }
        }

        if (val && rule.minDigits) {
            var digits = val.replace(/\D/g, '');
            if (digits.length < rule.minDigits) {
                group.classList.add('bw-has-error');
                errorText.textContent = rule.msgFormat;
                return false;
            }
        }

        if (val) {
            group.classList.add('bw-has-success');
        }
        return true;
    }

    function validateAllFields() {
        var allValid = true;
        var firstError = null;
        Object.keys(validationRules).forEach(function(fieldId) {
            if (!validateField(fieldId)) {
                allValid = false;
                if (!firstError) firstError = getEl(fieldId);
            }
        });
        if (firstError) firstError.focus();
        return allValid;
    }

    // Validate on blur
    Object.keys(validationRules).forEach(function(fieldId) {
        var el = getEl(fieldId);
        if (el) {
            el.addEventListener('blur', function() { validateField(fieldId); });
            el.addEventListener('input', function() {
                var group = this.closest('.bw-form-group');
                if (group.classList.contains('bw-has-error')) {
                    validateField(fieldId);
                }
            });
        }
    });

    // ===== SUBMIT =====
    function submitBooking(forceDuplicate) {
        var firstName = getEl('booking-first-name').value.trim();
        var lastName = getEl('booking-last-name').value.trim();
        var phone = getEl('booking-phone').value.trim();
        var email = getEl('booking-email').value.trim();
        var notes = getEl('booking-notes').value.trim();

        if (!validateAllFields()) {
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

        if (forceDuplicate) {
            body.force_duplicate = true;
        }

        fetch(apiUrl + '/tenants/' + slug + '/reservations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function(r) { return checkApiResponse(r); })
        .then(function(data) {
            loadingOverlay.style.display = 'none';

            // Handle duplicate warning — show custom modal
            if (!data.success && data.error && data.error.code === 'DUPLICATE_WARNING') {
                showDuplicateModal(data.error.message);
                return;
            }

            if (data.success) {
                var details = getEl('confirmation-details');
                var confHtml =
                    '<div class="bw-conf-summary">' +
                    '<div class="bw-conf-row"><span>Data</span><strong>' + formatDatePill(body.date) + ' (' + formatDateDMY(body.date) + ')</strong></div>' +
                    '<div class="bw-conf-row"><span>Orario</span><strong>' + body.time + '</strong></div>' +
                    '<div class="bw-conf-row"><span>Persone</span><strong>' + body.party_size + '</strong></div>' +
                    '<div class="bw-conf-row"><span>Nome</span><strong>' + escapeHtml(firstName + ' ' + lastName) + '</strong></div>';

                if (state.selectedDiscount > 0) {
                    confHtml += '<div class="bw-conf-row bw-conf-promo"><span><i class="bi bi-percent"></i> Promozione</span><strong>-' + state.selectedDiscount + '% sconto al tavolo</strong></div>';
                }

                if (notes) {
                    confHtml += '<div class="bw-conf-row"><span>Note</span><strong>' + escapeHtml(notes) + '</strong></div>';
                }

                confHtml += '</div>';

                var resId = (data.data && data.data.reservation_id) ? data.data.reservation_id : '';
                var depType = (data.data && data.data.deposit_type) ? data.data.deposit_type : '';
                var isPendingDeposit = data.data && data.data.deposit_required;
                var isPendingManual = data.data && data.data.status === 'pending' && !data.data.deposit_required;

                if (isPendingDeposit && depType === 'stripe') {
                    confHtml += '<p style="color:#E65100;"><i class="bi bi-credit-card"></i> Prenotazione <strong>n. ' + resId + '</strong> in attesa di pagamento. Completa il pagamento della caparra per confermare.</p>';
                } else if (isPendingDeposit) {
                    confHtml += '<p style="color:#E65100;"><i class="bi bi-hourglass-split"></i> Prenotazione <strong>n. ' + resId + '</strong> in attesa di pagamento caparra. Riceverai un\'email di conferma dopo la verifica.</p>';
                } else if (isPendingManual) {
                    confHtml += '<p style="color:#E65100;"><i class="bi bi-hourglass-split"></i> Prenotazione <strong>n. ' + resId + '</strong> in attesa di conferma dal ristorante. Riceverai un\'email quando sarà confermata.</p>';
                } else {
                    confHtml += '<p>Prenotazione <strong>n. ' + resId + '</strong> &mdash; riceverai una conferma via email a <strong>' + escapeHtml(email) + '</strong></p>';
                }

                details.innerHTML = confHtml;

                // Update heading/icon
                var confirmStep = steps.confirm;
                var headingEl = confirmStep.querySelector('h3');
                var iconEl = confirmStep.querySelector('.bw-confirm-icon i');
                if (isPendingDeposit || isPendingManual) {
                    if (headingEl) headingEl.textContent = isPendingDeposit ? (depType === 'stripe' ? 'Pagamento Caparra' : 'Prenotazione in Attesa') : 'Prenotazione Ricevuta!';
                    if (iconEl) { iconEl.className = 'bi bi-hourglass-split'; iconEl.style.color = '#E65100'; }
                } else {
                    if (headingEl) headingEl.textContent = 'Prenotazione Confermata!';
                    if (iconEl) { iconEl.className = 'bi bi-check-circle-fill'; iconEl.style.color = ''; }
                }

                if (data.data && data.data.deposit_required) {
                    var depAmt = data.data.deposit_amount ? parseFloat(data.data.deposit_amount).toFixed(2) : '';
                    var depType = data.data.deposit_type || 'info';

                    if (depType === 'stripe' && data.data.stripe_checkout_url) {
                        details.innerHTML += '<div class="bw-deposit-info" style="margin-top:12px;">' +
                            '<p style="margin:0 0 10px;font-size:.88rem;">Caparra richiesta: <strong>&euro;' + depAmt + '</strong></p>' +
                            '<a href="' + data.data.stripe_checkout_url.replace(/"/g, '&quot;') + '" id="bw-stripe-pay-btn" ' +
                            'style="display:inline-block;background:var(--bw-brand,#2E7D32);color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-size:.9rem;font-weight:600;">' +
                            '<i class="bi bi-credit-card" style="margin-right:6px;"></i>Paga la caparra</a>' +
                            '<p id="bw-stripe-countdown" style="margin:10px 0 0;font-size:.78rem;color:#6c757d;">Reindirizzamento automatico tra <strong>5</strong> secondi...</p>' +
                            '</div>';
                    } else if (depType === 'link' && data.data.deposit_payment_link) {
                        details.innerHTML += '<div class="bw-deposit-info" style="margin-top:12px;">' +
                            '<p style="margin:0 0 8px;">Caparra richiesta: <strong>&euro;' + depAmt + '</strong> (prenotazione n. ' + resId + ')</p>' +
                            '<a href="' + data.data.deposit_payment_link.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener" ' +
                            'style="display:inline-block;background:var(--bw-brand,#2E7D32);color:#fff;padding:8px 20px;border-radius:8px;text-decoration:none;font-size:.85rem;font-weight:600;">' +
                            '<i class="bi bi-link-45deg" style="margin-right:4px;"></i>Paga la caparra</a>' +
                            '<p style="margin:8px 0 0;font-size:.75rem;color:#6c757d;">Indica nella causale: <strong>Prenotazione n. ' + resId + '</strong>. Il ristorante confermer&agrave; dopo il pagamento.</p>' +
                            '</div>';
                    } else if (depType === 'info') {
                        var bankInfo = data.data.deposit_bank_info || '';
                        details.innerHTML += '<div class="bw-deposit-info" style="margin-top:12px;">' +
                            '<p style="margin:0 0 8px;">Caparra richiesta: <strong>&euro;' + depAmt + '</strong></p>' +
                            (bankInfo ? '<div style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:10px;font-size:.8rem;white-space:pre-line;">' + bankInfo.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>' : '') +
                            '<div style="background:#fff3e0;border:1px solid #ffe0b2;border-radius:8px;padding:10px;font-size:.8rem;margin-top:8px;">' +
                            '<strong>Causale:</strong> Caparra prenotazione n. ' + resId + '</div>' +
                            '<p style="margin:8px 0 0;font-size:.75rem;color:#6c757d;">Effettua il bonifico e il ristorante confermer&agrave; la prenotazione.</p>' +
                            '</div>';
                    }
                }

                // Hide progress, show confirmation
                getEl('step-progress').style.display = 'none';
                Object.keys(steps).forEach(function(key) {
                    if (steps[key]) steps[key].style.display = 'none';
                });
                steps.confirm.style.display = 'block';

                if (data.data && data.data.deposit_type === 'stripe' && data.data.stripe_checkout_url) {
                    var stripeUrl = data.data.stripe_checkout_url;
                    var countdown = 5;
                    var countdownEl = getEl('bw-stripe-countdown');
                    var countdownTimer = setInterval(function() {
                        countdown--;
                        if (countdownEl) {
                            var strong = countdownEl.querySelector('strong');
                            if (strong) strong.textContent = countdown;
                        }
                        if (countdown <= 0) {
                            clearInterval(countdownTimer);
                            window.location.href = stripeUrl;
                        }
                    }, 1000);
                }
            } else {
                var msg = data.error ? data.error.message : 'Errore nella prenotazione.';

                if (data.suggestions && data.suggestions.length > 0) {
                    goToStep(3);
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
    }

    getEl('btn-submit').addEventListener('click', function() {
        submitBooking(false);
    });

    // ===== DUPLICATE WARNING MODAL =====
    var duplicateModal = getEl('duplicate-modal');
    var duplicateModalMessage = getEl('duplicate-modal-message');

    function showDuplicateModal(message) {
        duplicateModalMessage.textContent = message;
        duplicateModal.style.display = 'flex';
    }

    function hideDuplicateModal() {
        duplicateModal.style.display = 'none';
    }

    getEl('duplicate-modal-cancel').addEventListener('click', hideDuplicateModal);
    getEl('duplicate-modal-confirm').addEventListener('click', function() {
        hideDuplicateModal();
        submitBooking(true);
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

    // ===== SUBSCRIPTION SUSPENDED =====
    function showSuspended() {
        widget.innerHTML =
            '<div style="text-align:center;padding:3rem 1.5rem;">' +
            '<i class="bi bi-calendar-x" style="font-size:2.5rem;color:#adb5bd;display:block;margin-bottom:.75rem;"></i>' +
            '<div style="font-size:1.1rem;font-weight:600;color:#495057;margin-bottom:.5rem;">Prenotazioni non disponibili</div>' +
            '<p style="font-size:.88rem;color:#6c757d;line-height:1.6;">Il servizio di prenotazione online non è al momento attivo.<br>Contatta direttamente il ristorante.</p>' +
            '</div>';
    }

    function checkApiResponse(r) {
        if (r.status === 403) {
            return r.json().then(function(data) {
                if (data.error && data.error.code === 'SUBSCRIPTION_EXPIRED') {
                    showSuspended();
                }
                return Promise.reject('subscription_expired');
            });
        }
        return r.json();
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
        .then(function(r) { return checkApiResponse(r); })
        .then(function(data) {
            if (data.success && data.data.today_bookings) {
                showSocialProof(data.data.today_bookings);
            }
        })
        .catch(function() {});
});
