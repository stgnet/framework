<?php
global $db;
$res = $db->getRow('SELECT * FROM modules limit 1');
if (!isset($res['signature'])) {
    $sql = "ALTER TABLE `modules` ADD COLUMN `signature` BLOB NULL AFTER `enabled`";
    $result = $db->query($sql);
}
