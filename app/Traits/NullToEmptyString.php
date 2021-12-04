<?php
namespace App\Traits;

/**
 * Convert null value from database to empty string
 * usually used for response mobile devices 
 */
trait NullToEmptyString 
{
    public static function bootNullToEmptyString()
    {
        static::retrieved(function($model) {
            foreach ((array) $model->fillable as $key) {
                if ($model->getAttribute($key) === null) {
                    $model->attributes[$key] = '';
                }
            }
        });
    }
}