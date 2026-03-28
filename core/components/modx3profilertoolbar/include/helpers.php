<?php
/**
 * Helper functions for Modx3ProfilerToolbar.
 *
 * @package modx3profilertoolbar
 */

if (!function_exists('modx3profilertoolbar_tagDisplayName')) {
    /**
     * Extract clean component name from MODX tag.
     * E.g. "[[!getProducts]]" -> "getProducts", "[[$header]]" -> "header"
     *
     * @param string $tag Raw MODX tag
     * @return string Clean component name (max 255 chars)
     */
    function modx3profilertoolbar_tagDisplayName(string $tag): string
    {
        $t = trim($tag);
        // Remove [[ and ]] and ! prefix
        $t = preg_replace('/^\[\[!?/', '', $t);
        $t = preg_replace('/\]\]$/', '', $t);
        $t = trim($t);
        
        if ($t === '') {
            return 'tag';
        }
        
        return mb_substr($t, 0, 255);
    }
}

if (!function_exists('modx3profilertoolbar_ipMatches')) {
    /**
     * Check if client IP matches allowed IP/CIDR.
     * Supports exact match, IPv4 CIDR, and IPv6 CIDR.
     *
     * @param string $clientIP Client IP address
     * @param string $allowed Allowed IP or CIDR (e.g. "192.168.0.0/16")
     * @return bool
     */
    function modx3profilertoolbar_ipMatches(string $clientIP, string $allowed): bool
    {
        // Точное совпадение
        if ($clientIP === $allowed) {
            return true;
        }
        
        // CIDR notation (e.g. 192.168.0.0/16)
        if (strpos($allowed, '/') !== false) {
            [$subnet, $bits] = explode('/', $allowed, 2);
            $bits = (int) $bits;
            
            // IPv4 CIDR
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $clientLong = ip2long($clientIP);
                $subnetLong = ip2long($subnet);
                if ($clientLong === false || $subnetLong === false) {
                    return false;
                }
                $mask = -1 << (32 - $bits);
                return ($clientLong & $mask) === ($subnetLong & $mask);
            }
            
            // IPv6 CIDR (упрощенная проверка по байтам)
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $clientBin = inet_pton($clientIP);
                $subnetBin = inet_pton($subnet);
                if ($clientBin === false || $subnetBin === false) {
                    return false;
                }
                $byteCount = (int) ceil($bits / 8);
                return substr($clientBin, 0, $byteCount) === substr($subnetBin, 0, $byteCount);
            }
        }
        
        return false;
    }
}
