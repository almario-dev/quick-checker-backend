<?php

function sendErrorResponse(\Exception $e)
{
    return response()->json(['error' => $e->getMessage()], $e->getCode() ?? 500);
}
