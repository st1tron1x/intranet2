<?php
require_once __DIR__ . '/includes/config.php';
Auth::logout();
flash('ok','Sesión cerrada');
redirect(url('index.php'));