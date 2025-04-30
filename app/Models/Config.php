<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mutator for the 'value' attribute (set)
    public function setValueAttribute($value)
    {
        // If the value is an array, encode it as JSON
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            // Otherwise, store it as is
            $this->attributes['value'] = $value;
        }
    }

    // Accessor for the 'value' attribute (get)
    public function getValueAttribute($value)
    {
        // Try to decode the value as JSON if it's a string
        $decodedValue = json_decode($value, true);

        // If the decoding was successful and the result is an array, return it
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodedValue;
        }

        // Otherwise, check and convert to the appropriate type
        if (is_numeric($value)) {
            // If the value is numeric (int or float), convert it
            if (strpos($value, '.') !== false) {
                return (float) $value; // Convert to float if it has a decimal point
            } else {
                return (int) $value; // Convert to integer otherwise
            }
        } elseif (strtolower($value) === 'true' || strtolower($value) === 'false') {
            // If it's 'true' or 'false', convert it to a boolean
            return strtolower($value) === 'true';
        } elseif ($value === 'null') {
            // If it's the string 'null', return null
            return null;
        }

        // Default case: return the value as a string
        return (string) $value;
    }
}