<?php

function http_get_json(string $url, int $timeout = 12) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'JadwalSholatApp/1.0 (+https://aladhan.com)'
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false) {
            throw new Exception('HTTP error: ' . $err);
        }
        if ($status < 200 || $status >= 300) {
            throw new Exception('HTTP status ' . $status);
        }
        $json = json_decode($resp, true);
        if (!is_array($json)) throw new Exception('Invalid JSON');
        return $json;
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'header' => "User-Agent: JadwalSholatApp/1.0\r\n"
        ]
    ]);
    $resp = @file_get_contents($url, false, $context);
    if ($resp === false) throw new Exception('HTTP request failed');
    $json = json_decode($resp, true);
    if (!is_array($json)) throw new Exception('Invalid JSON');
    return $json;
}

function fetch_prayer_times(string $city, string $country, int $method, string $dateDMY = ''): array {
    $city = trim($city);
    $country = trim($country);
    if ($city === '' || $country === '') {
        throw new Exception('City and country are required');
    }
    if ($dateDMY === '') {
        $dateDMY = date('d-m-Y');
    }
    $query = http_build_query([
        'address' => $city . ', ' . $country,
        'method' => $method,
        'date' => $dateDMY,
    ]);
    $url = 'https://api.aladhan.com/v1/timingsByAddress?' . $query;

    $json = http_get_json($url);
    if (!isset($json['code']) || (int)$json['code'] !== 200) {
        $status = $json['code'] ?? 'unknown';
        $msg = $json['data'] ?? ($json['status'] ?? 'API error');
        throw new Exception('API failed: ' . $status . ' - ' . (is_string($msg) ? $msg : ''));
    }
    $data = $json['data'] ?? [];
    $timings = $data['timings'] ?? [];
    $meta = $data['meta'] ?? [];
    $date = $data['date'] ?? [];

    return [
        'timings' => $timings,
        'meta' => $meta,
        'date' => $date,
        'raw' => $data,
    ];
}

function aladhan_methods(): array {
    // Common calculation methods
    return [
        2 => 'University of Islamic Sciences, Karachi',
        3 => 'Islamic Society of North America',
        4 => 'Muslim World League',
        5 => 'Umm Al-Qura University, Makkah',
        7 => 'Egyptian General Authority of Survey',
        8 => 'Institute of Geophysics, University of Tehran',
        9 => 'Gulf Region',
        10 => 'Kuwait',
        11 => 'Qatar',
        12 => 'Majlis Ugama Islam Singapura, Singapore',
        13 => 'Union Organization islamic de France',
        14 => 'Diyanet İşleri Başkanlığı, Turkey',
        15 => 'Spiritual Administration of Muslims of Russia',
        16 => 'Moonsighting Committee Worldwide',
        99 => 'Custom / Other'
    ];
}
