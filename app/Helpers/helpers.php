<?php

use Carbon\Carbon;

function sendErrorResponse(\Exception $e)
{
    return response()->json(['error' => $e->getMessage()], $e->getCode() ?? 500);
}

function timeDiffInHumanReadableFormat($datetime)
{
    if (is_null($datetime)) {
        return null;
    }

    try {
        $date = Carbon::parse($datetime);
        $now = Carbon::now();
        $diffInSeconds = $date->diffInSeconds($now);

        if ($diffInSeconds <= 60) {
            return 'just now';
        }

        if ($diffInSeconds <= 120) { // 2 minutes = 120 seconds
            return 'a min ago';
        }

        if ($diffInSeconds <= 3600) { // 60 minutes = 3600 seconds
            $minutes = (int) $date->diffInMinutes($now);
            return $minutes . ' mins ago';
        }

        if ($diffInSeconds <= 86400) { // 24 hours = 86400 seconds
            $hours = (int) $date->diffInHours($now);
            return $hours === 1 ? 'an hour ago' : $hours . ' hours ago';
        }

        if ($diffInSeconds <= 7 * 86400) { // 7 days
            return $date->format('l'); // Day name
        }

        return $date->format('F d, Y'); // Full date
    } catch (\Exception $e) {
        return null;
    }
}