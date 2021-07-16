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

class UploadLogoController extends UploadImageController
{
    protected $filePathSettingKey = 'logo_path';

    protected $filenamePrefix = 'logo';

    /**
     * {@inheritdoc}
     */
    protected function makeImage(UploadedFileInterface $file)
    {
        $manager = new ImageManager();

        $encodedImage = $manager->make($file->getStream())->heighten(60, function ($constraint) {
            $constraint->upsize();
        })->encode('png');

        return $encodedImage;
    }
}
