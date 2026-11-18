<?php
function h($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function photo_url($filename) {
    if (!is_string($filename) || $filename !== basename($filename) ||
        preg_match('/[\\\\\x00-\x1f]/', $filename) ||
        !preg_match('/\.(?:jpe?g|png|gif|webp)$/i', $filename)) {
        return '../images/profile.jpg';
    }
    return '../images/'.rawurlencode($filename);
}
