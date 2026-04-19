<?php
// Barangay Connect – Facility Schedule
// resident/facility_schedule.php

require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/constants.php';
require_role('resident');

$page_title = 'Facility Schedule';
include '../includes/header.php';
?>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/navbar.php'; ?>
        <div class="page-header">
            <h1>Facility Schedule</h1>
            <span class="page-subtitle">View approved facility reservations</span>
        </div>
        <div class="page-body">

            <!-- Filter -->
            <div class="card">
                <div class="card-header">
                    <h3>Reservation Calendar</h3>
                    <div class="card-actions">
                        <select class="filter-select" id="facility-filter">
                            <option value="">All Facilities</option>
                        </select>
                    </div>
                </div>
                <div id="facility-calendar"></div>
            </div>

            <!-- Upcoming Reservations -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Upcoming Reservations</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Reserved By</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="empty-row">
                                No upcoming reservations.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
<script>
    // Pass reservations data to calendar.js
    // TODO: Replace with real data from DB
    window.calendarReservations = [];
</script>
<script src="/BARANGAY_CONNECT/assets/js/calendar.js"></script>
<?php include '../includes/footer.php'; ?>