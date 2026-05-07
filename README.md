Default system accounts (change passwords after first login):
- Captain:   username `captain`,   password `captain123`
- Secretary: username `secretary`, password `secretary123`    - this is where you approve the account to be able to login your registered accounts.
- Staff:     username `staff`,     password `staff123`
- Sysadmin:  username `sysadmin`,  password `sysadmin123`
- Staff2:    username `Annabelle`,    password `annabelle`

Residents must register via the registration page; no default resident accounts.

current registered data:
username = jazelletamayo
password = pigmea23

username = EjhiePacquiao
password = pacquiao


1. 👤 Resident (Primary Client)
Self-register online (requires verification by Secretary)

Log in after account is approved

Submit service requests (Barangay Clearance, Certificate of Indigency, Facility Reservation, Complaint)

Track own requests using reference number

Cancel own pending requests

View own profile and request history

View facility schedule (calendar of approved reservations)

Change password and update own contact info

Claim documents in person (required for release)

2. 🧑‍💼 Barangay Staff (Data Encoder / Front Desk)
Encode new residents (manual registration – creates both ResidentProfile and UserAccount with Active status)

Update resident information (edit, mark inactive – no deletion)

Search and view all residents

Create service requests on behalf of residents (walk-in)

Accept requests and prepare documents

Update request status to ForApproval after document preparation

Record actions (all changes logged in audit log)

3. 📋 Barangay Secretary (Approver / Manager)
Verify pending resident registrations – approve (account becomes Active) or reject (add reason)

Validate and approve service requests (Clearance, Indigency, Facility Reservation)

Reject requests with reason

Assign complaint handling (set HandledBy staff)

Schedule mediation (date, venue, mediator)

Release documents after in‑person claim (sets status to Released)

Record payments upon document release (generate official receipt)

Manage facilities (add, edit, deactivate facilities)

Set priority level for facility reservations (1=barangay event, 2=community, 3=private)

Generate reports: Daily Transaction Log, Weekly Pending, Monthly Summary, Complaint Summary, Facility Utilization, Resident Demographics

View all residents, requests, complaints, payments

4. 👑 Barangay Captain (Highest Authority / Oversight)
View system‑wide dashboards (summary cards, pending overrides)

Override rejected requests or approvals (with documented reason)

Review staff performance reports

View complaint resolution overview

View facility utilization charts

Access all reports (same as Secretary plus staff performance)

Approve escalated matters (if workflow requires Captain final approval)

Monitor audit logs (with SysAdmin)

Can apply system overrides – all actions logged and prefixed with "CAPTAIN OVERRIDE"

5. 🖥️ System Administrator (Technical Maintenance)
Create, disable, or manage user accounts for personnel (Staff, Secretary, Captain, other SysAdmins)

No access to resident transaction data by default (any temporary access requires Captain approval and is logged)

Monitor audit logs (full access to UserActivityLog)

Perform database backups

Manage system settings (e.g., barangay name, contact info, logo – future feature)

Monitor system health (remote, not required on‑site)

Cannot create service requests, approve documents, record payments, or handle residents



📌 SLA – To Do Later
Make SLA values editable via System Settings (not hardcoded in constants.php).

Implement automatic “overdue” flags for requests exceeding SLA.

Build reports: Weekly Pending Requests (overdue by SLA) and Staff Performance (average processing time).




