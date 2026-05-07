<?php
$hash = '$2y$10$ureFkQqOFiKnC8V6t.ROAOPOPOZdr3C8JdI7/LAH/6ER4mgWoljmq';

var_dump(password_verify('1234', $hash));
?>