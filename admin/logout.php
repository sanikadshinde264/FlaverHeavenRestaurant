<?php
session_start();
session_destroy();
header('Location: ../access.html');
exit;