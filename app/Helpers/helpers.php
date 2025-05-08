<?php

use Carbon\Carbon;

function sendErrorResponse(\Exception $e)
{
    $code = (int) $e->getCode();
    $status = ($code >= 100 && $code <= 599) ? $code : 500;
    return response()->json(['error' => $e->getMessage()], $status);
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

        if ($diffInSeconds <= 120) { // 2 minutes
            return 'a min ago';
        }

        if ($diffInSeconds <= 3600) { // up to 1 hour
            $minutes = (int) $date->diffInMinutes($now);
            return $minutes . ' mins ago';
        }

        if ($diffInSeconds <= 86400) { // up to 24 hours
            $hours = (int) $date->diffInHours($now);
            return $hours === 1 ? 'an hour ago' : $hours . ' hours ago';
        }

        if ($diffInSeconds <= 2 * 86400) { // 24–48 hours
            return 'yesterday';
        }

        if ($diffInSeconds <= 7 * 86400) { // within this week
            return 'last ' . strtolower($date->format('l')); // e.g. "Monday"
        }

        return $date->format('F d, Y'); // e.g. "April 29, 2025"
    } catch (\Exception $e) {
        return null;
    }
}

function extractImage($file): string
{
    try {
        $fileContent = base64_encode(file_get_contents($file));
        $mimeType = $file->getMimeType();
        return "data:$mimeType;base64,$fileContent";
    } catch (\Exception $e) {
        throw $e;
    }
}
