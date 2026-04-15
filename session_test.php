<?php
session_start();
$_SESSION['teste'] = 'ok';
echo session_id() . ' | ' . ($_SESSION['teste'] ?? 'falhou');