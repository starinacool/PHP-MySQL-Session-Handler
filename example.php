<?php
require_once('MySqlSessionHandler.php');

$session = new MySqlSessionHandler('localhost', 'username', 'password', 'database');

session_set_save_handler(array($session, 'open'),
                         array($session, 'close'),
                         array($session, 'read'),
                         array($session, 'write'),
                         array($session, 'destroy'),
                         array($session, 'gc'));

// The following prevents unexpected effects when using objects as save handlers.
register_shutdown_function('session_write_close');

session_start();
