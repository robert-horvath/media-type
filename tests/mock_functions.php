<?php
declare(strict_types = 1);
namespace RHo\MediaType;

function preg_match($pattern, $subject, &$matches = NULL)
{
    $mock = $GLOBALS['mock_preg_match'] ?? FALSE;
    if ($mock)
        return FALSE;
    return \preg_match($pattern, $subject, $matches);
}

function preg_last_error()
{
    return $GLOBALS['preg_last_error'] ?? \preg_last_error();
}