// Barangay Connect – Facility Schedule Calendar
// assets/js/calendar.js

document.addEventListener('DOMContentLoaded', function () {

    const calendarEl = document.getElementById('facility-calendar');
    if (!calendarEl) return;

    const reservations = window.calendarReservations || [];

    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    function renderCalendar(month, year) {
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        let html = `
            <div class="cal-nav">
                <button onclick="prevMonth()" class="btn btn-secondary btn-small">← Prev</button>
                <span class="cal-month-label">${monthNames[month]} ${year}</span>
                <button onclick="nextMonth()" class="btn btn-secondary btn-small">Next →</button>
            </div>
            <div class="schedule-calendar">
        `;

        // Day headers
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        days.forEach(d => {
            html += `<div class="cal-day-header">${d}</div>`;
        });

        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            html += `<div class="cal-day" style="background:transparent;border:none;"></div>`;
        }

        // Day cells
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const events = reservations.filter(r => r.date === dateStr);
            const hasEvent = events.length > 0;

            html += `<div class="cal-day ${hasEvent ? 'has-event' : ''}">
                <div class="cal-day-num">${d}</div>`;
            events.forEach(ev => {
                html += `<div class="cal-event" title="${ev.facility}">${ev.facility}</div>`;
            });
            html += `</div>`;
        }

        html += `</div>`;
        calendarEl.innerHTML = html;
    }

    window.prevMonth = function () {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        renderCalendar(currentMonth, currentYear);
    };

    window.nextMonth = function () {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar(currentMonth, currentYear);
    };

    renderCalendar(currentMonth, currentYear);

});