<?php
// Barangay Connect – App Constants
// config/constants.php

// App info
define('APP_NAME', 'Barangay Connect');
define('APP_BASE', '/BARANGAY_CONNECT');

// Request statuses
define('STATUS_PENDING',      'Pending');
define('STATUS_FOR_APPROVAL', 'ForApproval');
define('STATUS_APPROVED',     'Approved');
define('STATUS_REJECTED',     'Rejected');
define('STATUS_RELEASED',     'Released');
define('STATUS_CANCELLED',    'Cancelled');

// Account statuses
define('ACCT_ACTIVE',    'Active');
define('ACCT_PENDING',   'PendingVerification');
define('ACCT_REJECTED',  'Rejected');
define('ACCT_INACTIVE',  'Inactive');

// Request types
define('REQ_CLEARANCE',   'Clearance');
define('REQ_INDIGENCY',   'Indigency');
define('REQ_RESERVATION', 'FacilityReservation');
define('REQ_COMPLAINT',   'Complaint');

// SLA times in hours
define('SLA_CLEARANCE',   1);
define('SLA_INDIGENCY',   24);
define('SLA_RESERVATION', 72);   // 3 working days
define('SLA_COMPLAINT',   168);  // 7 calendar days

// Facility reservation minimum lead time in days
define('RESERVATION_LEAD_DAYS', 3);

// Color scheme (for reference in inline styles if needed)
define('COLOR_GREEN_DARK',  '#1a4731');
define('COLOR_GREEN_MAIN',  '#2d6a4f');
define('COLOR_GREEN_MID',   '#40916c');
define('COLOR_GREEN_LIGHT', '#74c69d');
define('COLOR_GOLD',        '#c9a84c');
