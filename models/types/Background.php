<?php

namespace app\models\types;

use app\models\Content;

/**
 * This is the model class for content uploads.
 */
class Background extends Image
{
    const TYPE = 'background';
    const TYPE_PATH = 'background/';

    public static function getAllWithPath()
    {
        $files = FileHelper::findFiles(self::BASE_PATH.self::TYPE_PATH);

        $contents = [];
        foreach ($files as $f) {
            $contents[substr($f, strrpos($f, '/') + 1)] = static::BASE_URI.$f;
        }

        return $contents;
    }
}