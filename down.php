<?php
$id = intval(@$_GET['id']);
$file = 'tmp' . DIRECTORY_SEPARATOR . $id . ".kgl";
header("Content-type: application/octet-stream");
header('Content-Disposition: attachment; filename="' . $id . '.kgl"');
readfile($file);
?>