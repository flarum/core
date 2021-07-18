<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\AbstractValidator;
use Flarum\Foundation\ValidationException;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Mime\MimeTypes;

class AvatarValidator extends AbstractValidator
{
    /**
     * @var \Illuminate\Validation\Validator
     */
    protected $laravelValidator;

    /**
     * Throw an exception if a model is not valid.
     *
     * @param array $attributes
     */
    public function assertValid(array $attributes)
    {
        $this->laravelValidator = $this->makeValidator($attributes);

        $this->assertFileRequired($attributes['avatar']);
        $this->assertFileMimes($attributes['avatar']);
        $this->assertFileSize($attributes['avatar']);
    }

    protected function assertFileRequired(UploadedFileInterface $file)
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $this->raise('required');
        }
    }

    protected function assertFileMimes(UploadedFileInterface $file)
    {
        $allowedTypes = $this->getAllowedTypes();

        // Block PHP files masquerading as images
        $phpExtensions = ['php', 'php3', 'php4', 'php5', 'phtml'];
        $fileExtension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if (in_array(trim(strtolower($fileExtension)), $phpExtensions)) {
            $this->raise('mimes', [':values' => implode(', ', $allowedTypes)]);
        }

        $guessedExtension = MimeTypes::getDefault()->getExtensions($file->getClientMediaType())[0] ?? null;

        if (! in_array($guessedExtension, $allowedTypes)) {
            $this->raise('mimes', [':values' => implode(', ', $allowedTypes)]);
        }
    }

    protected function assertFileSize(UploadedFileInterface $file)
    {
        $maxSize = $this->getMaxSize();

        if ($file->getSize() / 1024 > $maxSize) {
            $this->raise('max.file', [':max' => $maxSize], 'max');
        }
    }

    protected function raise($error, array $parameters = [], $rule = null)
    {
        $message = $this->laravelValidator->makeReplacements(
            $this->translator->trans("validation.$error"),
            'avatar',
            $rule ?? $error,
            array_values($parameters)
        );

        throw new ValidationException(['avatar' => $message]);
    }

    protected function getMaxSize()
    {
        return 2048;
    }

    protected function getAllowedTypes()
    {
        return ['jpg', 'png', 'bmp', 'gif'];
    }
}
