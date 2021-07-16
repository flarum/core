<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;

class UploadFaviconController extends UploadImageController
{
    protected $filePathSettingKey = 'favicon_path';

    protected $filenamePrefix = 'favicon';

    /**
     * {@inheritdoc}
     */
    protected function makeImage(UploadedFileInterface $file)
    {
        $this->fileExtension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if ($this->fileExtension === 'ico') {
            $encodedImage = $file->getStream();
            error_log(get_class($encodedImage));
        } else {
            $manager = new ImageManager();

            $encodedImage = $manager->make($file->getStream())->resize(64, 64, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode('png');

            $this->fileExtension = 'png';
        }

        return $encodedImage;
    }
}
