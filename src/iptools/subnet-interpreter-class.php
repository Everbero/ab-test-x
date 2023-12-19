<?php
/*
 * ip_in_range.php - Function to determine if an IP is located in a
 *                   specific range as specified via several alternative
 *                   formats.
 *
 * Network ranges can be specified as:
 * 1. Wildcard format:     1.2.3.*
 * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
 *
 * Return value BOOLEAN : ip_in_range($ip, $range);
 *
 * Copyright 2008: Paul Gregg <pgregg@pgregg.com>
 * 10 January 2008
 * Version: 1.2
 *
 * Source website: http://www.pgregg.com/projects/php/ip_in_range/
 * Version 1.2
 *
 * This software is Donationware - if you feel you have benefited from
 * the use of this tool then please consider a donation. The value of
 * which is entirely left up to your discretion.
 * http://www.pgregg.com/donate/
 *
 * Please do not remove this header, or source attibution from this file.
 */

/*
 * Modified by James Greene <james@cloudflare.com> to include IPV6 support
 * (original version only supported IPV4).
 * 21 May 2012
 */
class subnetInterpreter {

    // know ip database
    private static $fb_ips = [
        "31.13.24.0/21",
        "31.13.64.0/19",
        "31.13.64.0/24",
        "31.13.69.0/24",
        "31.13.70.0/24",
        "31.13.71.0/24",
        "31.13.72.0/24",
        "31.13.73.0/24",
        "31.13.75.0/24",
        "31.13.76.0/24",
        "31.13.77.0/24",
        "31.13.78.0/24",
        "31.13.79.0/24",
        "31.13.80.0/24",
        "66.220.144.0/20",
        "66.220.144.0/21",
        "66.220.149.11/16",
        "66.220.152.0/21",
        "66.220.158.11/16",
        "66.220.159.0/24",
        "69.63.176.0/21",
        "69.63.176.0/24",
        "69.63.184.0/21",
        "69.171.224.0/19",
        "69.171.224.0/20",
        "69.171.224.37/16",
        "69.171.229.11/16",
        "69.171.239.0/24",
        "69.171.240.0/20",
        "69.171.242.11/16",
        "69.171.255.0/24",
        "74.119.76.0/22",
        "173.252.64.0/19",
        "173.252.70.0/24",
        "173.252.96.0/19",
        "204.15.20.0/22",
        "66.220.157.16/29", "66.220.157.64/29", "66.220.157.72/30", "66.220.157.112/30", "69.171.232.56/29", "69.171.244.32/28", "69.171.244.88/30", "69.171.244.92/31",
        "66.220.144.128/27", "66.220.155.128/27", "66.220.157.76/30", "66.220.157.80/28", "66.220.157.96/28", "66.220.157.116/30", "66.220.157.120/29", "69.171.232.64/26", "69.171.232.128/28", "69.171.232.144/29", "69.171.244.64/28", "69.171.244.80/29", "69.171.244.94/31", "69.171.244.96/27",
        "66.220.144.160/31", "66.220.144.166/31", "66.220.144.170/31", "66.220.155.160/31", "66.220.155.166/31", "66.220.155.170/31", "66.220.155.172/31", "69.171.232.232/29",
        "66.220.144.162/31", "66.220.144.164/31", "66.220.144.168/31", "66.220.144.172/30", "66.220.155.162/31", "66.220.155.164/31", "66.220.155.168/31", "66.220.155.174/31", "69.171.232.240/29",
        "69.171.232.152/29", "69.171.232.160/29",
        "69.171.232.168/30",
        "69.171.232.172/31", "69.171.232.174/32",
        "69.171.232.175/32", "69.171.232.176/30",
        "66.220.144.178/32", "66.220.155.178/32", "69.171.232.181/32",
        "66.220.144.184/29",
        "66.220.144.192/29",
        "66.220.144.200/29", "66.220.144.208/29", "66.220.144.216/29", "66.220.144.224/29", "66.220.144.232/29", "66.220.144.240/29",
        "69.171.232.248/29",
        "66.220.144.176/31",
    ];
    private static $fb_ipv6 = [
        "2a03:2880::/29",
        "2a03:2880::/32",
    ];
    // cloudfare public ips
    private static $cloudfare_ips = [
        "173.245.48.0/20",
        "103.21.244.0/22",
        "103.22.200.0/22",
        "103.31.4.0/22",
        "141.101.64.0/18",
        "108.162.192.0/18",
        "190.93.240.0/20",
        "188.114.96.0/20",
        "197.234.240.0/22",
        "198.41.128.0/17",
        "162.158.0.0/15",
        "104.16.0.0/13",
        "104.24.0.0/14",
        "172.64.0.0/13",
        "131.0.72.0/22",
    ];
    private static $cloudfare_ipv6 = [
        "2400:cb00::/32",
        "2606:4700::/32",
        "2803:f800::/32",
        "2405:b500::/32",
        "2405:8100::/32",
        "2a06:98c0::/29",
        "2c0f:f248::/32",
    ];
    // google public ips
    private static $google_ips = [
        "8.8.4.0/24",
        "8.8.8.0/24",
        "8.34.208.0/20",
        "8.35.192.0/20",
        "23.236.48.0/20",
        "23.251.128.0/19",
        "34.0.0.0/15",
        "34.2.0.0/16",
        "34.3.0.0/23",
        "34.3.3.0/24",
        "34.3.4.0/24",
        "34.3.8.0/21",
        "34.3.16.0/20",
        "34.3.32.0/19",
        "34.3.64.0/18",
        "34.3.128.0/17",
        "34.4.0.0/14",
        "34.8.0.0/13",
        "34.16.0.0/12",
        "34.32.0.0/11",
        "34.64.0.0/10",
        "34.128.0.0/10",
        "35.184.0.0/13",
        "35.192.0.0/14",
        "35.196.0.0/15",
        "35.198.0.0/16",
        "35.199.0.0/17",
        "35.199.128.0/18",
        "35.200.0.0/13",
        "35.208.0.0/12",
        "35.224.0.0/12",
        "35.240.0.0/13",
        "64.15.112.0/20",
        "64.233.160.0/19",
        "66.22.228.0/23",
        "66.102.0.0/20",
        "66.249.64.0/19",
        "70.32.128.0/19",
        "72.14.192.0/18",
        "74.114.24.0/21",
        "74.125.0.0/16",
        "104.154.0.0/15",
        "104.196.0.0/14",
        "104.237.160.0/19",
        "107.167.160.0/19",
        "107.178.192.0/18",
        "108.59.80.0/20",
        "108.170.192.0/18",
        "108.177.0.0/17",
        "130.211.0.0/16",
        "136.112.0.0/12",
        "142.250.0.0/15",
        "146.148.0.0/17",
        "162.216.148.0/22",
        "162.222.176.0/21",
        "172.110.32.0/21",
        "172.217.0.0/16",
        "172.253.0.0/16",
        "173.194.0.0/16",
        "173.255.112.0/20",
        "192.158.28.0/22",
        "192.178.0.0/15",
        "193.186.4.0/24",
        "199.36.154.0/23",
        "199.36.156.0/24",
        "199.192.112.0/22",
        "199.223.232.0/21",
        "207.223.160.0/20",
        "208.65.152.0/22",
        "208.68.108.0/22",
        "208.81.188.0/22",
        "208.117.224.0/19",
        "209.85.128.0/17",
        "216.58.192.0/19",
        "216.73.80.0/20",
        "216.239.32.0/19",
    ];
    private static $google_ipv6 = [
        "2001:4860::/32",
        "2404:6800::/32",
        "2404:f340::/32",
        "2600:1900::/28",
        "2606:73c0::/32",
        "2607:f8b0::/32",
        "2620:11a:a000::/40",
        "2620:120:e000::/40",
        "2800:3f0::/32",
        "2a00:1450::/32",
        "2c0f:fb50::/32",
    ];

    // decbin32
    // In order to simplify working with IP addresses (in binary) and their
    // netmasks, it is easier to ensure that the binary strings are padded
    // with zeros out to 32 characters - IP addresses are 32 bit numbers
    private function decbin32($dec) {
        return str_pad(decbin($dec), 32, '0', STR_PAD_LEFT);
    }

    // ipv4_in_range
    // This function takes 2 arguments, an IP address and a "range" in several
    // different formats.
    // Network ranges can be specified as:
    // 1. Wildcard format:     1.2.3.*
    // 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
    // 3. Start-End IP format: 1.2.3.0-1.2.3.255
    // The function will return true if the supplied IP is within the range.
    // Note little validation is done on the range inputs - it expects you to
    // use one of the above 3 formats.
    private function ipv4_in_range($ip, $range) {
        if (strpos($range, '/') !== false) {
            // $range is in IP/NETMASK format
            list($range, $netmask) = explode('/', $range, 2);
            if (strpos($netmask, '.') !== false) {
                // $netmask is a 255.255.0.0 format
                $netmask = str_replace('*', '0', $netmask);
                $netmask_dec = ip2long($netmask);
                return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
            } else {
                // $netmask is a CIDR size block
                // fix the range argument
                $x = explode('.', $range);
                while (count($x) < 4) {
                    $x[] = '0';
                }

                list($a, $b, $c, $d) = $x;
                $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
                $range_dec = ip2long($range);
                $ip_dec = ip2long($ip);

                # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
                #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

                # Strategy 2 - Use math to create it
                $wildcard_dec = pow(2, (32 - $netmask)) - 1;
                $netmask_dec = ~$wildcard_dec;

                return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
            }
        } else {
            // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
            if (strpos($range, '*') !== false) { // a.b.*.* format
                // Just convert to A-B format by setting * to 0 for A and 255 for B
                $lower = str_replace('*', '0', $range);
                $upper = str_replace('*', '255', $range);
                $range = "$lower-$upper";
            }

            if (strpos($range, '-') !== false) { // A-B format
                list($lower, $upper) = explode('-', $range, 2);
                $lower_dec = (float) sprintf("%u", ip2long($lower));
                $upper_dec = (float) sprintf("%u", ip2long($upper));
                $ip_dec = (float) sprintf("%u", ip2long($ip));
                return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
            }
            return false;
        }
    }

    // converts inet_pton output to string with bits
    private function inet_to_bits($inet) {
        
        $splitted = str_split($inet);
        $binaryip = '';
        
        foreach ($splitted as $char) {
                    $binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        return $binaryip;
    }    
    private function ipv6_in_range($ip, $range){
        
        $ip         = inet_pton($ip);
        $binaryip   = self::inet_to_bits($ip);

        list($net,$maskbits) = explode('/',$range);
        $net        = inet_pton($net);
        $binarynet  = self::inet_to_bits($net);

        $ip_net_bits    = substr($binaryip,0,$maskbits);
        $net_bits       = substr($binarynet,0,$maskbits);

        if($ip_net_bits!==$net_bits) return false;
        else return true;
    }
       

    public function serial_ipv4_verification($ip_address) {
        foreach (self::$fb_ips as $ips) {
            if (self::ipv4_in_range($ip_address, $ips)) {
                return true;
            }
        }
        foreach (self::$google_ips as $ips) {
            if (self::ipv4_in_range($ip_address, $ips)) {
                return true;
            }
        }
        foreach (self::$cloudfare_ips as $ips) {
            if (self::ipv4_in_range($ip_address, $ips)) {
                return true;
            }
        }
        return false;
    }

    public function serial_ipv6_verification($ip_address) {
        foreach (self::$fb_ipv6 as $ips) {
            if (self::ipv6_in_range($ip_address, $ips)) {
                return true;
            }
        }
        foreach (self::$google_ipv6 as $ips) {
            if (self::ipv6_in_range($ip_address, $ips)) {
                return true;
            }
        }
        foreach (self::$cloudfare_ipv6 as $ips) {
            if (self::ipv6_in_range($ip_address, $ips)) {
                return true;
            }
        }
        return false;
    }
}