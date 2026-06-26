<?php
function fa_digits(string $value): string
{
    return strtr($value, [
        '0' => '۰',
        '1' => '۱',
        '2' => '۲',
        '3' => '۳',
        '4' => '۴',
        '5' => '۵',
        '6' => '۶',
        '7' => '۷',
        '8' => '۸',
        '9' => '۹',
    ]);
}

function gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;

    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }

    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }

    return [$jy, $jm, $jd];
}

function jalali_parts(?string $datetime): ?array
{
    if (!$datetime) {
        return null;
    }

    try {
        $dt = new DateTime($datetime);
    } catch (Throwable $e) {
        return null;
    }

    [$jy, $jm, $jd] = gregorian_to_jalali(
        (int)$dt->format('Y'),
        (int)$dt->format('m'),
        (int)$dt->format('d')
    );

    return [
        'jy' => $jy,
        'jm' => $jm,
        'jd' => $jd,
        'hour' => (int)$dt->format('H'),
        'minute' => (int)$dt->format('i'),
        'second' => (int)$dt->format('s'),
        'date' => $dt,
    ];
}

function jdate(?string $datetime, bool $persianDigits = true): string
{
    $p = jalali_parts($datetime);
    if (!$p) return '';

    $out = sprintf('%04d/%02d/%02d', $p['jy'], $p['jm'], $p['jd']);
    return $persianDigits ? fa_digits($out) : $out;
}

function jtime(?string $datetime, bool $persianDigits = true): string
{
    $p = jalali_parts($datetime);
    if (!$p) return '';

    $out = sprintf('%02d:%02d', $p['hour'], $p['minute']);
    return $persianDigits ? fa_digits($out) : $out;
}

function jdatetime(?string $datetime, bool $persianDigits = true): string
{
    $date = jdate($datetime, $persianDigits);
    $time = jtime($datetime, $persianDigits);

    if ($date === '') return '';
    return $date . ' ساعت ' . $time;
}

function jdate_human(?string $datetime): string
{
    $p = jalali_parts($datetime);
    if (!$p) return '';

    $months = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];

    $today = new DateTime('today');
    $dateOnly = (clone $p['date'])->setTime(0, 0, 0);
    $diffDays = (int)$today->diff($dateOnly)->format('%r%a');

    if ($diffDays === 0) {
        return 'امروز • ' . jtime($datetime);
    }

    if ($diffDays === -1) {
        return 'دیروز • ' . jtime($datetime);
    }

    return fa_digits((string)$p['jd']) . ' ' . $months[$p['jm']] . ' ' . fa_digits((string)$p['jy']) . ' • ' . jtime($datetime);
}
