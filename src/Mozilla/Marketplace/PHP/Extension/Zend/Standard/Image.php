<?php
function getimagesizefromstring($data)
{
    $uri = 'data://application/octet-stream;base64,' . base64_encode($data);
    return getimagesize($uri);
}