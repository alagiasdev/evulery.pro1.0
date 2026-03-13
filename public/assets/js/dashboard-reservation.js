/**
 * Evulery.Pro - Dashboard Prenotazione Rapida (Touch-Friendly)
 * FASE 16: 5 tocchi in 5 secondi per una prenotazione telefonica.
 * Supporta create mode e edit mode (via DR_CONFIG.editMode).
 */
document.addEventListener('DOMContentLoaded', function() {

    var config = window.DR_CONFIG || {};
    if (!config.apiUrl || !config.tenantSlug) return;

    var isEdit = !!config.editMode;
    var preselected = config.preselected || {};

    // ===== STATE =====
    var state = {
        date: null,
        time: null,
        partySize: 2,
        source: 'phone',
        searchTimer: null,
        initialLoad: isEdit
    };

    // ===== LOCALE ITALIANO =====
    var DAYS_SHORT = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
    var MONTHS_SHORT = ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
    var MONTHS_FULL = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

    // ===== DOM REFS =====
    function $(id) { return document.getElementById(id); }
    var dateValue      = $('dr-date-value');
    var partyValue     = $('dr-party-value');
    var timeValue      = $('dr-time-value');
    var sourceValue    = $('dr-source-value');
    var slotsContainer = $('dr-slots-container');
    var customerQ      = $('dr-customer-q');
    var customerResults = $('dr-customer-results');
    var customerBadge  = $('dr-customer-badge');
    var summaryDate    = $('dr-sum-date');
    var summaryTime    = $('dr-sum-time');
    var summaryParty   = $('dr-sum-party');
    var summaryCustomer = $('dr-sum-customer');

    // ===== UTILITY =====
    function formatDateISO(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function formatDateLabel(dateStr) {
        var d = new Date(dateStr + 'T00:00:00');
        return DAYS_SHORT[d.getDay()] + ' ' + d.getDate() + ' ' + MONTHS_SHORT[d.getMonth()];
    }

    function formatDateShort(d) {
        return DAYS_SHORT[d.getDay()] + ' ' + d.getDate() + '/' + (d.getMonth() + 1);
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function updateSummary() {
        summaryDate.textContent = state.date ? formatDateLabel(state.date) : '--';
        summaryDate.classList.toggle('dr-filled', !!state.date);

        if (summaryParty) {
            summaryParty.textContent = state.partySize ? state.partySize + ' pers.' : '--';
            summaryParty.classList.toggle('dr-filled', !!state.partySize);
        }

        summaryTime.textContent = state.time || '--';
        summaryTime.classList.toggle('dr-filled', !!state.time);

        // In edit mode, customer summary is static
        if (!isEdit) {
            var fn = $('dr-first-name');
            var ln = $('dr-last-name');
            var name = ((fn ? fn.value : '') + ' ' + (ln ? ln.value : '')).trim();
            summaryCustomer.textContent = name || '--';
            summaryCustomer.classList.toggle('dr-filled', !!name);
        }
    }

    // ===== 1. DATE SELECTION =====
    function initDates() {
        var today = new Date();
        var quickDates = [];
        var ids = ['dr-date-oggi', 'dr-date-domani', 'dr-date-dopodomani'];
        for (var i = 0; i <= 2; i++) {
            var d = new Date(today);
            d.setDate(d.getDate() + i);
            var el = $(ids[i]);
            if (el) el.textContent = formatDateShort(d);
            quickDates.push(formatDateISO(d));
        }

        if (isEdit && preselected.date) {
            // Find if preselected date matches a quick button
            var matchIndex = quickDates.indexOf(preselected.date);
            state.time = preselected.time || null;
            if (timeValue) timeValue.value = state.time || '';
            selectDate(preselected.date, matchIndex >= 0 ? matchIndex : -1);
        } else {
            selectDate(formatDateISO(today), 0);
        }
    }

    function selectDate(dateStr, btnIndex) {
        state.date = dateStr;

        // In edit mode on initial load, preserve the preselected time
        if (!state.initialLoad) {
            state.time = null;
            timeValue.value = '';
        }

        dateValue.value = dateStr;

        // Update button states
        var quickBtns = document.querySelectorAll('.dr-date-quick');
        quickBtns.forEach(function(btn) { btn.classList.remove('active'); });

        var otherBtn = $('dr-date-other');
        if (btnIndex !== undefined && btnIndex >= 0 && btnIndex <= 2) {
            quickBtns[btnIndex].classList.add('active');
            if (otherBtn) {
                otherBtn.classList.remove('active');
                var otherSub = otherBtn.querySelector('.dr-date-sub');
                if (otherSub) otherSub.style.display = 'none';
            }
            // Close calendar if open
            closeCalendar();
        } else {
            if (otherBtn) {
                otherBtn.classList.add('active');
                var otherSub = otherBtn.querySelector('.dr-date-sub');
                if (otherSub) {
                    otherSub.textContent = formatDateLabel(dateStr);
                    otherSub.style.display = 'block';
                }
            }
        }

        updateSummary();
        loadSlots();
    }

    // Quick date buttons
    document.querySelectorAll('.dr-date-quick').forEach(function(btn) {
        btn.addEventListener('click', function() {
            state.initialLoad = false;
            var offset = parseInt(this.dataset.offset);
            var d = new Date();
            d.setDate(d.getDate() + offset);
            selectDate(formatDateISO(d), offset);
        });
    });

    // "Altra data" button + mini calendar
    var calendarWrap = $('dr-calendar-wrap');
    var calendarGrid = $('dr-cal-grid');
    var calMonthLabel = $('dr-cal-month-label');
    var calPrev = $('dr-cal-prev');
    var calNext = $('dr-cal-next');
    var otherDateBtn = $('dr-date-other');

    var calState = {
        month: new Date().getMonth(),
        year: new Date().getFullYear()
    };

    function isCalendarOpen() {
        return calendarWrap && calendarWrap.style.display !== 'none';
    }

    function openCalendar() {
        if (!calendarWrap) return;
        // Navigate calendar to currently selected date's month
        if (state.date) {
            var d = new Date(state.date + 'T00:00:00');
            calState.month = d.getMonth();
            calState.year = d.getFullYear();
        }
        renderCalendar();
        calendarWrap.style.display = 'block';
    }

    function closeCalendar() {
        if (calendarWrap) calendarWrap.style.display = 'none';
    }

    if (otherDateBtn) {
        otherDateBtn.addEventListener('click', function() {
            if (isCalendarOpen()) {
                closeCalendar();
            } else {
                openCalendar();
            }
        });
    }

    if (calPrev) {
        calPrev.addEventListener('click', function() {
            calState.month--;
            if (calState.month < 0) { calState.month = 11; calState.year--; }
            renderCalendar();
        });
    }

    if (calNext) {
        calNext.addEventListener('click', function() {
            calState.month++;
            if (calState.month > 11) { calState.month = 0; calState.year++; }
            renderCalendar();
        });
    }

    function renderCalendar() {
        if (!calendarGrid) return;

        var year = calState.year;
        var month = calState.month;

        calMonthLabel.textContent = MONTHS_FULL[month] + ' ' + year;

        // First day of month (adjusted: 0=Mon)
        var firstDay = new Date(year, month, 1);
        var startDow = firstDay.getDay() - 1;
        if (startDow < 0) startDow = 6;

        var daysInMonth = new Date(year, month + 1, 0).getDate();

        var today = new Date();
        today.setHours(0,0,0,0);
        var maxDate = new Date(today);
        maxDate.setDate(maxDate.getDate() + 90);

        var html = '';

        // Empty cells before first day
        for (var i = 0; i < startDow; i++) {
            html += '<div class="dr-cal-cell dr-cal-empty"></div>';
        }

        // Day cells
        for (var day = 1; day <= daysInMonth; day++) {
            var d = new Date(year, month, day);
            d.setHours(0,0,0,0);
            var dateStr = formatDateISO(d);

            var classes = 'dr-cal-cell';

            if (d.getTime() === today.getTime()) {
                classes += ' dr-cal-today';
            }

            if (d < today || d > maxDate) {
                classes += ' dr-cal-disabled';
            }

            if (state.date === dateStr) {
                classes += ' dr-cal-selected';
            }

            html += '<div class="' + classes + '" data-date="' + dateStr + '">' + day + '</div>';
        }

        calendarGrid.innerHTML = html;

        // Bind clicks
        calendarGrid.querySelectorAll('.dr-cal-cell:not(.dr-cal-disabled):not(.dr-cal-empty)').forEach(function(cell) {
            cell.addEventListener('click', function() {
                state.initialLoad = false;
                var selectedDate = this.dataset.date;
                selectDate(selectedDate, -1);
                closeCalendar();
            });
        });

        // Nav buttons state
        var nowMonth = today.getMonth();
        var nowYear = today.getFullYear();
        calPrev.disabled = (year === nowYear && month <= nowMonth);

        var nextMonthFirst = new Date(year, month + 1, 1);
        calNext.disabled = (nextMonthFirst > maxDate);
    }

    // ===== 2. PARTY SIZE =====
    function selectPartySize(size, skipReload) {
        state.partySize = size;
        partyValue.value = size;

        document.querySelectorAll('.dr-party-btn').forEach(function(btn) {
            btn.classList.toggle('active', parseInt(btn.dataset.size) === size);
        });

        // If size > 12, auto-expand extended grid
        var extGrid = $('dr-party-extended');
        var moreLink = $('dr-party-more-link');
        if (size > 12 && extGrid && extGrid.style.display === 'none') {
            extGrid.style.display = 'grid';
            if (moreLink) moreLink.innerHTML = 'Nascondi opzioni extra <i class="bi bi-dash"></i>';
        }

        updateSummary();

        // Reload slots (availability depends on party size)
        if (!skipReload && state.date) {
            if (!state.initialLoad) {
                state.time = null;
                timeValue.value = '';
            }
            loadSlots();
        }
    }

    document.querySelectorAll('.dr-party-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            state.initialLoad = false;
            selectPartySize(parseInt(this.dataset.size));
        });
    });

    // "Opzioni per più persone" toggle
    var partyMoreLink = $('dr-party-more-link');
    var partyExtended = $('dr-party-extended');
    if (partyMoreLink && partyExtended) {
        partyMoreLink.addEventListener('click', function(e) {
            e.preventDefault();
            var isHidden = partyExtended.style.display === 'none';
            partyExtended.style.display = isHidden ? 'grid' : 'none';
            this.innerHTML = isHidden
                ? 'Nascondi opzioni extra <i class="bi bi-dash"></i>'
                : 'Opzioni per pi\u00f9 persone <i class="bi bi-plus"></i>';
        });
    }

    // ===== 3. TIME SLOTS (AJAX) =====
    function loadSlots() {
        if (!state.date || !state.partySize) {
            slotsContainer.innerHTML = '<p class="text-muted small">Seleziona data e coperti per vedere gli orari disponibili.</p>';
            return;
        }

        slotsContainer.innerHTML = '<div class="dr-slots-loading"><div class="spinner-border spinner-border-sm me-2"></div>Caricamento orari...</div>';

        var url = config.apiUrl + '/tenants/' + config.tenantSlug + '/availability'
            + '?date=' + state.date
            + '&party_size=' + state.partySize
            + '&grouped=1';

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.data) {
                    slotsContainer.innerHTML = '<div class="dr-slots-empty"><i class="bi bi-exclamation-triangle me-1"></i>Errore nel caricamento.</div>';
                    return;
                }
                var groups = data.data.grouped_slots || [];
                if (!groups.length) {
                    slotsContainer.innerHTML = '<div class="dr-slots-empty"><i class="bi bi-calendar-x me-1"></i>Nessun orario disponibile per questa data.</div>';
                    return;
                }
                renderSlots(groups);

                // After initial load in edit mode, clear the flag
                if (state.initialLoad) {
                    state.initialLoad = false;
                }
            })
            .catch(function() {
                slotsContainer.innerHTML = '<div class="dr-slots-empty"><i class="bi bi-wifi-off me-1"></i>Errore di connessione.</div>';
            });
    }

    function renderSlots(groups) {
        var html = '';
        groups.forEach(function(group) {
            html += '<div class="dr-slot-group">';
            html += '<div class="dr-slot-group-label">' + escapeHtml(group.display_name) + '</div>';
            html += '<div class="dr-slot-grid">';

            group.slots.forEach(function(slot) {
                var available = slot.available_covers;
                var max = slot.max_covers;
                var ratio = max > 0 ? available / max : 0;

                var colorClass = 'dr-slot-green';
                if (!slot.is_available || available <= 0) {
                    colorClass = 'dr-slot-red';
                } else if (ratio <= 0.25) {
                    colorClass = 'dr-slot-yellow';
                }

                var slotTimeShort = slot.time.substring(0, 5);
                var activeClass = (state.time === slotTimeShort || state.time === slot.time) ? ' active' : '';
                var disabled = (!slot.is_available) ? ' disabled' : '';

                html += '<button type="button" class="dr-slot-btn ' + colorClass + activeClass + '"'
                    + ' data-time="' + slot.time + '"'
                    + disabled + '>'
                    + '<span class="dr-slot-indicator"></span>'
                    + '<span>' + slotTimeShort + '</span>'
                    + '<span class="dr-slot-covers">' + available + ' liberi</span>'
                    + '</button>';
            });

            html += '</div></div>';
        });

        slotsContainer.innerHTML = html;

        // Bind slot clicks
        slotsContainer.querySelectorAll('.dr-slot-btn:not([disabled])').forEach(function(btn) {
            btn.addEventListener('click', function() {
                slotsContainer.querySelectorAll('.dr-slot-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                state.time = this.dataset.time;
                timeValue.value = state.time;
                updateSummary();
            });
        });

        // If we have a preselected time, ensure it's set in state and hidden field
        if (state.time) {
            timeValue.value = state.time;
            updateSummary();
        }
    }

    // ===== 4. CUSTOMER SEARCH =====
    if (customerQ) {
        customerQ.addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(state.searchTimer);

            if (q.length < 2) {
                customerResults.style.display = 'none';
                return;
            }

            state.searchTimer = setTimeout(function() {
                searchCustomers(q);
            }, 300);
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dr-customer-search')) {
                customerResults.style.display = 'none';
            }
        });
    }

    function searchCustomers(q) {
        fetch(config.customerSearchUrl + '?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.data.length) {
                    customerResults.innerHTML = '<div class="dr-customer-item" style="cursor:default;color:#6c757d;">Nessun cliente trovato. Compila manualmente.</div>';
                    customerResults.style.display = 'block';
                    return;
                }

                var html = '';
                data.data.forEach(function(c) {
                    var badge = getSegmentBadge(c.segment);

                    html += '<div class="dr-customer-item"'
                        + ' data-fn="' + escapeHtml(c.first_name) + '"'
                        + ' data-ln="' + escapeHtml(c.last_name) + '"'
                        + ' data-email="' + escapeHtml(c.email) + '"'
                        + ' data-phone="' + escapeHtml(c.phone) + '"'
                        + ' data-segment="' + c.segment + '"'
                        + ' data-bookings="' + c.total_bookings + '"'
                        + ' data-noshow="' + c.total_noshow + '">'
                        + '<div>'
                        + '<span class="dr-customer-name">' + escapeHtml(c.first_name + ' ' + c.last_name) + '</span>'
                        + '<br><span class="dr-customer-detail">'
                        + '<i class="bi bi-telephone me-1"></i>' + escapeHtml(c.phone)
                        + '</span>'
                        + '</div>'
                        + '<div>' + badge + '</div>'
                        + '</div>';
                });

                customerResults.innerHTML = html;
                customerResults.style.display = 'block';

                // Bind selection
                customerResults.querySelectorAll('.dr-customer-item[data-fn]').forEach(function(item) {
                    item.addEventListener('click', function() {
                        selectCustomer(this);
                    });
                });
            })
            .catch(function() {
                customerResults.style.display = 'none';
            });
    }

    function selectCustomer(el) {
        $('dr-first-name').value = el.dataset.fn;
        $('dr-last-name').value = el.dataset.ln;
        $('dr-email').value = el.dataset.email;
        $('dr-phone').value = el.dataset.phone;

        customerResults.style.display = 'none';
        customerQ.value = el.dataset.fn + ' ' + el.dataset.ln;

        // Show segment badge
        var segment = el.dataset.segment;
        var bookings = parseInt(el.dataset.bookings);
        var noshow = parseInt(el.dataset.noshow);

        var badgeHtml = getSegmentBadge(segment);
        badgeHtml += ' <small class="text-muted">' + bookings + ' prenotazioni</small>';

        if (noshow > 0) {
            badgeHtml += '<div class="dr-noshow-warning">'
                + '<i class="bi bi-exclamation-triangle me-1"></i>'
                + noshow + ' no-show</div>';
        }

        customerBadge.innerHTML = badgeHtml;
        customerBadge.style.display = 'block';

        updateSummary();
    }

    function getSegmentBadge(segment) {
        var labels = {
            'nuovo': 'Nuovo',
            'occasionale': 'Occasionale',
            'abituale': 'Abituale',
            'vip': 'VIP'
        };
        var label = labels[segment] || 'Nuovo';
        return '<span class="dr-badge dr-badge-' + segment + '">' + label + '</span>';
    }

    // Update summary when customer fields change manually
    if (!isEdit) {
        ['dr-first-name', 'dr-last-name'].forEach(function(id) {
            var el = $(id);
            if (el) el.addEventListener('input', function() { updateSummary(); });
        });
    }

    // ===== 5. SOURCE SELECTOR =====
    if (!isEdit) {
        document.querySelectorAll('.dr-source-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.dr-source-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                state.source = this.dataset.source;
                if (sourceValue) sourceValue.value = state.source;
            });
        });
    }

    // ===== 6. FORM VALIDATION =====
    var form = $('dr-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!timeValue.value) {
                e.preventDefault();
                alert('Seleziona un orario disponibile.');
                return;
            }
            if (!dateValue.value) {
                e.preventDefault();
                alert('Seleziona una data.');
                return;
            }
        });
    }

    // ===== PREFILL CUSTOMER (from customer page link) =====
    if (!isEdit && config.prefillCustomer) {
        var pc = config.prefillCustomer;
        if ($('dr-first-name')) $('dr-first-name').value = pc.firstName || '';
        if ($('dr-last-name')) $('dr-last-name').value = pc.lastName || '';
        if ($('dr-email')) $('dr-email').value = pc.email || '';
        if ($('dr-phone')) $('dr-phone').value = pc.phone || '';
        if (customerQ) customerQ.value = (pc.firstName || '') + ' ' + (pc.lastName || '');
        if (pc.notes && $('dr-notes')) $('dr-notes').value = pc.notes;
    }

    // ===== INIT =====
    var initPartySize = (isEdit && preselected.partySize) ? preselected.partySize : 2;
    initDates();
    selectPartySize(initPartySize, true); // skipReload: slots already loaded by initDates
    updateSummary();
});
