<?php

use Carbon\Carbon;

function sendErrorResponse(\Exception $e)
{
    return response()->json(['error' => $e->getMessage()], $e->getCode() ?? 500);
}

function timeDiffInHumanReadableFormat($datetime)
{
    // Return null if the argument is null
    if (is_null($datetime)) {
        return null;
    }

    try {
        $date = Carbon::parse($datetime);
        $now = Carbon::now();
        $diffInSeconds = $date->diffInSeconds($now);

        // If the difference is less than or equal to 60 seconds
        if ($diffInSeconds <= 60) {
            return 'just now';
        }

        // If the difference is within 1 minute
        if ($diffInSeconds <= 60 * 2) {
            return 'a min ago';
        }

        // If the difference is less than 60 minutes (1 hour)
        if ($diffInSeconds <= 60 * 60) {
            $minutes = (int) $date->diffInMinutes($now);
            return $minutes . ' min ago';
        }

        // If the difference is less than 24 hours
        if ($diffInSeconds <= 24 * 60 * 60) {
            return 'an hour ago';
        }

        // If the difference is less than 7 days
        if ($diffInSeconds <= 7 * 24 * 60 * 60) {
            return $date->format('l'); // Day of the week
        }

        // For dates older than 7 days
        return $date->format('F d, Y'); // Month Day, Year (e.g., April 01, 2025)
    } catch (\Exception $e) {
        return null;
    }
}