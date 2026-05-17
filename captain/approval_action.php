<?php
require_once '../config/session.php';
require_role('captain');
header('Location: final_approvals.php');
exit;