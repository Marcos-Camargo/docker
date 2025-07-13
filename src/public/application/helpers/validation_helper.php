<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('isValidUrl')) {
    function isValidUrl(string $url): bool
    {
        $url_validation_regex = "/^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$/";
        return preg_match($url_validation_regex, $url) == 1;
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permissions, $user_permission) {
        foreach ($permissions as $permission) {
            if (in_array($permission, $user_permission)) {
                return true;
            }
        }
        return false;
    }

}
