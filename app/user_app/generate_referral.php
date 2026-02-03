<?php
function generateReferralCode($length = 8)
{
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, $length));
}
