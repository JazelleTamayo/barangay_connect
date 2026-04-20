Default system accounts (change passwords after first login):
- Captain:   username `captain`,   password `captain123`
- Secretary: username `secretary`, password `secretary123`    - this is where you approve the account to be able to login your registered accounts.
- Staff:     username `staff`,     password `staff123`
- Sysadmin:  username `sysadmin`,  password `sysadmin123`

Residents must register via the registration page; no default resident accounts.

current registered data:
username = jazelletamayo
password = pigmea23

username = EjhiePacquiao
password = pacquiao





Thing that needs to be fixed:
---------------------------------------------------RESIDENT DASHBOARD-----------------------------------------------
NEW REQUEST TAB:
- REQUESTING BARANAGY CLEARANCE - once submitted it return error
        ERROR - Fatal error: Uncaught PDOException: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`barangay_connect`.`servicerequest`, CONSTRAINT `servicerequest_ibfk_1` FOREIGN KEY (`ResidentID`) REFERENCES `resident` (`ResidentID`)) in D:\xampp\htdocs\barangay_connect\classes\Database.php:54 Stack trace: #0 D:\xampp\htdocs\barangay_connect\classes\Database.php(54): PDOStatement->execute(Array) #1 D:\xampp\htdocs\barangay_connect\classes\ServiceRequest.php(105): Database->insert('INSERT INTO Ser...', Array) #2 D:\xampp\htdocs\barangay_connect\handlers\request_create_handler.php(47): ServiceRequest->create(Array) #3 {main} thrown in D:\xampp\htdocs\barangay_connect\classes\Database.php on line 54

- REQUESTING CERTIFICATE OF INDIGENCY - once submitted it return error
        ERROR - Fatal error: Uncaught PDOException: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`barangay_connect`.`servicerequest`, CONSTRAINT `servicerequest_ibfk_1` FOREIGN KEY (`ResidentID`) REFERENCES `resident` (`ResidentID`)) in D:\xampp\htdocs\barangay_connect\classes\Database.php:54 Stack trace: #0 D:\xampp\htdocs\barangay_connect\classes\Database.php(54): PDOStatement->execute(Array) #1 D:\xampp\htdocs\barangay_connect\classes\ServiceRequest.php(105): Database->insert('INSERT INTO Ser...', Array) #2 D:\xampp\htdocs\barangay_connect\handlers\request_create_handler.php(47): ServiceRequest->create(Array) #3 {main} thrown in D:\xampp\htdocs\barangay_connect\classes\Database.php on line 54

- REQUESTING FACILITY RESERVATION - once submitted it return error
        ERROR - Fatal error: Uncaught PDOException: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`barangay_connect`.`servicerequest`, CONSTRAINT `servicerequest_ibfk_1` FOREIGN KEY (`ResidentID`) REFERENCES `resident` (`ResidentID`)) in D:\xampp\htdocs\barangay_connect\classes\Database.php:54 Stack trace: #0 D:\xampp\htdocs\barangay_connect\classes\Database.php(54): PDOStatement->execute(Array) #1 D:\xampp\htdocs\barangay_connect\classes\ServiceRequest.php(105): Database->insert('INSERT INTO Ser...', Array) #2 D:\xampp\htdocs\barangay_connect\handlers\request_create_handler.php(47): ServiceRequest->create(Array) #3 {main} thrown in D:\xampp\htdocs\barangay_connect\classes\Database.php on line 54

- REQUESTING A COMPLAINT
        ERROR - Fatal error: Uncaught PDOException: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`barangay_connect`.`servicerequest`, CONSTRAINT `servicerequest_ibfk_1` FOREIGN KEY (`ResidentID`) REFERENCES `resident` (`ResidentID`)) in D:\xampp\htdocs\barangay_connect\classes\Database.php:54 Stack trace: #0 D:\xampp\htdocs\barangay_connect\classes\Database.php(54): PDOStatement->execute(Array) #1 D:\xampp\htdocs\barangay_connect\classes\ServiceRequest.php(105): Database->insert('INSERT INTO Ser...', Array) #2 D:\xampp\htdocs\barangay_connect\handlers\request_create_handler.php(47): ServiceRequest->create(Array) #3 {main} thrown in D:\xampp\htdocs\barangay_connect\classes\Database.php on line 54
        
---------------------------------------------------RESIDENT DASHBOARD-----------------------------------------------



