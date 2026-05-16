<?php
// Barangay Connect – Facility Schedule
// resident/facility_schedule.php
//
// FIXED Privacy Bug: The previous version exposed the full name of the
//   reserving resident and their event name/purpose to all logged-in residents.
//   BR-01 says residents may "view the facility reservation schedule" — meaning
//   availability, not personal booking details.
//   Fix: removed ResidentName, EventName, and EventPurpose from the query and
//   display. Residents now see only Facility, Date, Time Slot, and "Reserved"
//   status — enough to know a slot is taken without revealing who booked it or
//   why. The JSON passed to calendar.js is also stripped of the 'by' field.

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$pdo = get_db();

// --- Resolve the logged-in resident's ResidentID ---
// $_SESSION['user_id'] is the UserAccountID; we look up the linked ResidentID
// so we can flag the resident's own reservations in the query below.
$myResidentID = null;
$rStmt = $pdo->prepare(
    "SELECT ResidentID FROM UserAccount WHERE UserAccountID = ? AND Role = 'resident' LIMIT 1"
);
$rStmt->execute([$_SESSION['user_id']]);
$row = $rStmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $myResidentID = (int) $row['ResidentID'];
}

// --- Load all active facilities for the filter dropdown ---
$facilities = $pdo->query(
    "SELECT FacilityID, FacilityName
     FROM Facility
     WHERE Status = 'Active'
     ORDER BY FacilityName ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// --- Load ALL approved upcoming facility reservations ---
// Only Approved / Prepared / Released are shown — not Pending/Rejected/Cancelled.
// Privacy rule: other residents see only Facility, Date, Time Slot, and "Reserved".
// The logged-in resident sees their OWN name + event purpose on their own rows.
$stmt = $pdo->prepare(
    "SELECT
        fr.ReservationDate,
        fr.TimeSlot,
        f.FacilityName,
        sr.ResidentID,
        CASE
            WHEN sr.ResidentID = ? THEN CONCAT(r.FirstName, ' ', r.LastName)
            ELSE NULL
        END AS BookedByName,
        CASE
            WHEN sr.ResidentID = ? THEN fr.EventName
            ELSE NULL
        END AS EventName
     FROM FacilityReservation fr
     JOIN ServiceRequest sr  ON fr.RequestID  = sr.RequestID
     JOIN Facility f         ON fr.FacilityID = f.FacilityID
     JOIN Resident r         ON sr.ResidentID  = r.ResidentID
     WHERE sr.Status IN ('Approved', 'Prepared', 'Released')
       AND fr.ReservationDate >= CURDATE()
     ORDER BY fr.ReservationDate ASC, fr.TimeSlot ASC"
);
$stmt->execute([$myResidentID, $myResidentID]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Build JSON array for calendar.js ---
// 'mine' = true only for the logged-in resident's own reservations.
// Other residents never receive BookedByName — it's NULL in the query result.
$calendarData = [];
foreach ($reservations as $res) {
    $isMine = ($myResidentID !== null && (int)$res['ResidentID'] === $myResidentID);
    $calendarData[] = [
        'date'      => $res['ReservationDate'],
        'facility'  => $res['FacilityName'],
        'timeslot'  => $res['TimeSlot'],
        'event'     => 'Reserved',
        'mine'      => $isMine,
        'label'     => $isMine && $res['EventName'] ? htmlspecialchars($res['EventName']) : null,
    ];
}

$page_title = 'Facility Schedule';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>

        <div class="page-body">

            <!-- Calendar Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Reservation Calendar</h3>
                    <div class="card-actions">
                        <select class="filter-select" id="facility-filter">
                            <option value="">All Facilities</option>
                            <?php foreach ($facilities as $f): ?>
                                <option value="<?= htmlspecialchars($f['FacilityName']) ?>">
                                    <?= htmlspecialchars($f['FacilityName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div id="facility-calendar"></div>
            </div>

            <!-- Upcoming Reservations Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Upcoming Reservations</h3>
                </div>
                <table class="data-table" id="reservations-table">
                    <thead>
                        <tr>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Booked By</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">No upcoming reservations.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $res):
                                $isMine = ($myResidentID !== null && (int)$res['ResidentID'] === $myResidentID);
                            ?>
                                <tr data-facility="<?= htmlspecialchars($res['FacilityName']) ?>"
                                    <?= $isMine ? 'class="my-booking"' : '' ?>>
                                    <td><?= htmlspecialchars($res['FacilityName']) ?></td>
                                    <td><?= date('M d, Y', strtotime($res['ReservationDate'])) ?></td>
                                    <td><?= htmlspecialchars($res['TimeSlot'] ?: '—') ?></td>
                                    <td>
                                        <?php if ($isMine): ?>
                                            <span class="my-booking-label">
                                                <?= htmlspecialchars($res['BookedByName']) ?>
                                                <?php if ($res['EventName']): ?>
                                                    <small class="text-muted">(<?= htmlspecialchars($res['EventName']) ?>)</small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $isMine ? 'status-approved my-booking-badge' : 'status-approved' ?>">
                                            <?= $isMine ? 'My Booking' : 'Reserved' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<style>
    /* Highlight the logged-in resident's own bookings */
    tr.my-booking {
        background-color: rgba(var(--color-primary-rgb, 59, 130, 246), 0.07);
    }

    .my-booking-label {
        font-weight: 600;
        color: var(--color-primary, #3b82f6);
    }

    .my-booking-label small {
        font-weight: 400;
        margin-left: 4px;
    }

    .my-booking-badge {
        background-color: var(--color-primary, #3b82f6) !important;
        color: #fff !important;
    }
</style>

<script>
    // Inject real DB data into the calendar script.
    // 'mine: true' entries are the logged-in resident's own bookings.
    const allReservations = <?= json_encode($calendarData) ?>;
    window.calendarReservations = allReservations;

    // Filter both calendar and table when dropdown changes
    document.getElementById('facility-filter').addEventListener('change', function() {
        const selected = this.value;

        // Update calendar data and re-render
        window.calendarReservations = selected === '' ?
            allReservations :
            allReservations.filter(r => r.facility === selected);

        if (typeof renderCalendar === 'function') {
            renderCalendar(currentMonth, currentYear);
        }

        // Filter table rows
        document.querySelectorAll('#reservations-table tbody tr[data-facility]').forEach(row => {
            row.style.display =
                (selected === '' || row.getAttribute('data-facility') === selected) ? '' : 'none';
        });
    });
</script>

<script src="/BARANGAY_CONNECT/assets/js/calendar.js"></script>
<?php include '../includes/footer.php'; ?>