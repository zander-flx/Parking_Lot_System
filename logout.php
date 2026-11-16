<?php
require 'helpers.php';        // starts the session; no database needed
$_SESSION = [];               // drop all sign-in keys
session_regenerate_id(true);
setFlash('ok', 'You have been signed out.');
header('Location: login.php'); exit;
