<?php

namespace app\models\types;

use Yii;
use app\models\Content;
use app\models\TempFile;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use YoutubeDl\YoutubeDl;

/**
 * This is the model class for HostedVideo content type.
 */
class HostedVideo extends Video
{
    public static $typeName = 'Hosted video';
    public static $typeDescription = 'Upload a video to servers.';
    public static $html = '<iframe src="%data%" />';
    public static $css = '%field% > * { height: 100%; width: 100%; }';
    public static $appendParams = '_win=%x1%,%y1%,%x2%,%y2%;_aspect-mode=letterbox';
    public static $input = 'file';
    public static $output = 'url';
    public static $usable = true;

    /**
     * Use mediainfo to parse video duration.
     *
     * @return int video duration
     */
    public function getDuration()
    {
        $mediainfo = $this->getMediaInfo();
        if ($mediainfo) {
            $general = $mediainfo->getGeneral();
            if ($general) {
                return ceil($general->get('duration')->getMilliseconds() / 1000);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sideload($url)
    {
        if (!self::validateUrl($url)) {
            $this->addError(static::TYPE, Yii::t('app', 'Empty or incorrect URL'));

            return false;
        }

        $dl = new YoutubeDl([
            'proxy' => Yii::$app->params['proxy'],
            'format' => 'best[ext=mp4]/best[ext=flv]',
        ]);
        $dl->setDownloadPath(self::getRealPath());

        try {
            $video = $dl->download($url);
            if (!$video) {
                $this->addError(static::TYPE, Yii::t('app', 'Downloading failed'));

                return false;
            }
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            $this->addError(static::TYPE, Yii::t('app', 'Downloading exception'));

            return false;
        }

        // $this->filename = $video->getFilename();
        // $this->path = self::getPath().$this->filename;
        // $this->size = $video->getFile()->getSize();
        // $this->duration = $video->getDuration();

        $type = static::TYPE;
        $this->tmp = new TempFile();
        $this->tmp->name = $video->getFilename();
        $this->tmp->file = self::getWebPath().$this->tmp->name;

        $fileInstance = new UploadedFile();
        $fileInstance->name = $this->tmp->name;
        $fileInstance->tempName = self::getRealPath().$fileInstance->name;
        $fileInstance->type = FileHelper::getMimeType($fileInstance->tempName);
        $fileInstance->size = $this->tmp->size;
        $this->tmp->$type = $fileInstance;

        if ($this->tmp->validate() && $this->tmp->save()) {
            if ($this->tmp->validateFile($fileInstance->tempName)) {
                return true;
            }
            $this->addError(static::TYPE, Yii::t('app', 'Invalid file'));
            $this->tmp->delete();
        } else {
            $this->addError(static::TYPE, Yii::t('app', 'Cannot save file'));
        }
        unlink($fileInstance->tempName);

        return false;
    }
}