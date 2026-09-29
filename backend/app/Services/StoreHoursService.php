<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;

class StoreHoursService
{
    public const TIMEZONE = 'Asia/Manila';

    private const DAY_NAMES = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function isOpen(Store $store): bool
    {
        $info = $this->getScheduleInfo($store);
        return $info['isOpen'];
    }

    /**
     * The store's week as ['Monday' => ['opens' => 'H:i', 'closes' => 'H:i'] | null, ...], in Manila
     * time, from either the per-day format or the older flat one. A day marked open without times
     * counts as open all day. Checkout's pickup-time picker is built from this, and
     * isOpenAt() below reads the same thing, so the picker never offers a time checkout refuses.
     */
    public function weeklyHours(Store $store): array
    {
        $days = $store->operating_days;
        $week = array_fill_keys(self::DAY_NAMES, null);

        // Per-day format
        if (is_array($days) && count(array_filter(array_keys($days), 'is_string')) > 0) {
            foreach (self::DAY_NAMES as $name) {
                $day = $days[$name] ?? null;
                if (!$day || empty($day['is_open'])) {
                    continue;
                }

                $open = $day['opening_time'] ?? null;
                $close = $day['closing_time'] ?? null;
                $week[$name] = ($open && $close)
                    ? ['opens' => $this->toHm($open), 'closes' => $this->toHm($close)]
                    : ['opens' => '00:00', 'closes' => '23:59'];
            }

            return $week;
        }

        // Legacy flat format: a list of day names sharing the store's one opening and closing time.
        if (!$store->opening_time || !$store->closing_time || !is_array($days) || !is_string(reset($days))) {
            return $week;
        }

        $hours = ['opens' => $this->toHm($store->opening_time), 'closes' => $this->toHm($store->closing_time)];
        foreach (self::DAY_NAMES as $name) {
            $short = strtolower(substr($name, 0, 3));
            if (collect($days)->contains(fn ($day) => strtolower(substr($day, 0, 3)) === $short)) {
                $week[$name] = $hours;
            }
        }

        return $week;
    }

    /**
     * Whether the store is open at the given moment. Uses that day's hours the way
     * getScheduleInfo() does for "now", including hours that run past midnight
     * (open from the opening time to midnight, and from midnight to the closing time).
     */
    public function isOpenAt(Store $store, Carbon $moment): bool
    {
        $local = $moment->copy()->setTimezone(self::TIMEZONE);
        $hours = $this->weeklyHours($store)[$local->format('l')] ?? null;
        if (!$hours) {
            return false;
        }

        $time = $local->format('H:i');
        ['opens' => $opens, 'closes' => $closes] = $hours;

        return $opens <= $closes
            ? ($time >= $opens && $time <= $closes)
            : ($time >= $opens || $time <= $closes);
    }

    private function toHm(string $time): string
    {
        return Carbon::parse($time, self::TIMEZONE)->format('H:i');
    }

    public function getScheduleInfo(Store $store): array
    {
        $days = $store->operating_days;
        $timezone = 'Asia/Manila';
        $now = now()->setTimezone($timezone);
        $todayFull = $now->format('l'); // e.g. "Monday"
        $currentTime = $now->format('H:i');

        $isOpen = false;
        $statusText = 'Closed now';
        $closesAt = null;

        // New per-day schedule format
        if (is_array($days) && count(array_filter(array_keys($days), 'is_string')) > 0) {
            $todaySchedule = $days[$todayFull] ?? null;

            if ($todaySchedule && !empty($todaySchedule['is_open'])) {
                $openTime = $todaySchedule['opening_time'] ?? null;
                $closeTime = $todaySchedule['closing_time'] ?? null;

                if ($openTime && $closeTime) {
                    $opens = Carbon::parse($openTime, $timezone)->format('H:i');
                    $closes = Carbon::parse($closeTime, $timezone)->format('H:i');

                    if ($opens <= $closes) {
                        if ($currentTime >= $opens && $currentTime <= $closes) {
                            $isOpen = true;
                            $closesAt = Carbon::parse($closeTime, $timezone)->format('g:i A');
                            $statusText = 'Open until ' . $closesAt;
                        } elseif ($currentTime < $opens) {
                            $statusText = 'Closed till ' . Carbon::parse($openTime, $timezone)->format('g:i A');
                        }
                    } else {
                        // Overnight logic
                        if ($currentTime >= $opens || $currentTime <= $closes) {
                            $isOpen = true;
                            $closesAt = Carbon::parse($closeTime, $timezone)->format('g:i A');
                            $statusText = 'Open until ' . $closesAt;
                        } elseif ($currentTime > $closes && $currentTime < $opens) {
                            $statusText = 'Closed till ' . Carbon::parse($openTime, $timezone)->format('g:i A');
                        }
                    }
                } else {
                    $isOpen = true;
                    $statusText = 'Open today';
                }
            }

            // Find next opening time if currently closed and not already determined for later today
            if (!$isOpen && $statusText === 'Closed now') {
                for ($i = 1; $i <= 7; $i++) {
                    $nextDate = $now->copy()->addDays($i);
                    $nextDayName = $nextDate->format('l');
                    $nextSchedule = $days[$nextDayName] ?? null;

                    if ($nextSchedule && !empty($nextSchedule['is_open'])) {
                        $nextOpenTime = $nextSchedule['opening_time'] ?? null;
                        if ($nextOpenTime) {
                            $dayStr = ($i === 1) ? '' : $nextDayName . ' ';
                            $statusText = 'Closed till ' . $dayStr . Carbon::parse($nextOpenTime, $timezone)->format('g:i A');
                            break;
                        }
                    }
                }
            }
            
            return [
                'isOpen' => $isOpen,
                'statusText' => $statusText,
                'closesAt' => $closesAt
            ];
        }

        // Legacy flat array format fallback
        if (!$store->opening_time || !$store->closing_time) {
            return ['isOpen' => false, 'statusText' => 'Closed now', 'closesAt' => null];
        }

        $opens = Carbon::parse($store->opening_time, $timezone)->format('H:i');
        $closes = Carbon::parse($store->closing_time, $timezone)->format('H:i');
        $todayShort = strtolower(substr($now->format('D'), 0, 3));
        $opensToday = false;

        if (!empty($days) && is_array($days)) {
            $firstValue = reset($days);
            if (is_string($firstValue)) {
                $opensToday = collect($days)->contains(
                    fn ($day) => strtolower(substr($day, 0, 3)) === $todayShort
                );
            }
        }

        if ($opensToday) {
            if ($opens <= $closes) {
                if ($currentTime >= $opens && $currentTime <= $closes) {
                    $isOpen = true;
                    $closesAt = Carbon::parse($closes, $timezone)->format('g:i A');
                    $statusText = 'Open until ' . $closesAt;
                } elseif ($currentTime < $opens) {
                    $statusText = 'Closed till ' . Carbon::parse($opens, $timezone)->format('g:i A');
                }
            } else {
                if ($currentTime >= $opens || $currentTime <= $closes) {
                    $isOpen = true;
                    $closesAt = Carbon::parse($closes, $timezone)->format('g:i A');
                    $statusText = 'Open until ' . $closesAt;
                } elseif ($currentTime > $closes && $currentTime < $opens) {
                    $statusText = 'Closed till ' . Carbon::parse($opens, $timezone)->format('g:i A');
                }
            }
        }

        if (!$isOpen && $statusText === 'Closed now') {
            if (!empty($days) && is_array($days) && is_string(reset($days))) {
                for ($i = 1; $i <= 7; $i++) {
                    $nextDate = $now->copy()->addDays($i);
                    $nextDayShort = strtolower(substr($nextDate->format('D'), 0, 3));
                    $opensThatDay = collect($days)->contains(
                        fn ($day) => strtolower(substr($day, 0, 3)) === $nextDayShort
                    );

                    if ($opensThatDay) {
                        $dayStr = ($i === 1) ? '' : $nextDate->format('l') . ' ';
                        $statusText = 'Closed till ' . $dayStr . Carbon::parse($store->opening_time, $timezone)->format('g:i A');
                        break;
                    }
                }
            }
        }

        return [
            'isOpen' => $isOpen,
            'statusText' => $statusText,
            'closesAt' => $closesAt
        ];
    }
}
