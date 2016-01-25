<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Illuminate\Support\Arr;

/**
 * @property string $name
 * @property string $description
 * @property string $type
 * @property array  $keywords
 * @property string $homepage
 * @property string $time
 * @property string $license
 * @property array  $authors
 * @property array  $support
 * @property array  $require
 * @property array  $requireDev
 * @property array  $autoload
 * @property array  $autoloadDev
 * @property array  $conflict
 * @property array  $replace
 * @property array  $provide
 * @property array  $suggest
 * @property array  $extra
 */
class Extension
{

    /**
     * Unique Id of the extension.
     *
     * @info Identical to the directory in the extensions directory.
     * @example flarum_suspend
     *
     * @var string
     */
    protected $id;
    /**
     * The directory of this extension.
     *
     * @var string
     */
    protected $path;

    /**
     * Composer json of the package.
     *
     * @var array
     */
    protected $composerJson;

    /**
     * Whether the extension is installed.
     *
     * @var bool
     */
    protected $installed = false;

    /**
     * The installed version of the extension.
     *
     * @var string
     */
    protected $version;

    /**
     * Whether the extension is enabled.
     *
     * @var bool
     */
    protected $enabled;

    /**
     * @param       $path
     * @param array $composerJson
     */
    public function __construct($path, $composerJson)
    {
        $this->id           = end(explode('/', $path));
        $this->path         = $path;
        $this->composerJson = $composerJson;
    }

    /**
     * Fallthrough for getting an attribute out of the composer.json.
     *
     * @param $name
     * @return mixed|null
     */
    function __get($name)
    {
        return $this->composerJsonAttribute(camel_case($name));
    }

    /**
     * Dot notation getter for composer.json attributes.
     *
     * @see https://laravel.com/docs/5.1/helpers#arrays
     *
     * @param $name
     * @return mixed
     */
    public function composerJsonAttribute($name)
    {
        return Arr::get($this->composerJson, $name);
    }

    /**
     * @param boolean $installed
     * @return Extension
     */
    public function setInstalled($installed)
    {
        $this->installed = $installed;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isInstalled()
    {
        return $this->installed;
    }

    /**
     * @param string $version
     * @return Extension
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Loads the icon information from the composer.json.
     *
     * @return array|null
     */
    public function getIcon()
    {
        if (($icon = $this->composerJsonAttribute('extra.flarum-extension.icon'))) {
            if ($file = Arr::get($icon, 'image')) {
                $file = $this->path . '/' . $file;

                if (file_exists($file)) {
                    $mimetype = pathinfo($file, PATHINFO_EXTENSION) === 'svg'
                        ? 'image/svg+xml'
                        : finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
                    $data     = file_get_contents($file);

                    $icon['backgroundImage'] = 'url(\'data:' . $mimetype . ';base64,' . base64_encode($data) . '\')';
                }
            }

            return $icon;
        }
    }

    /**
     * @param boolean $enabled
     * @return Extension
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * The raw path of the directory under extensions.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Tests whether the extension has assets.
     *
     * @return bool
     */
    public function hasAssets()
    {
        return realpath($this->path . '/assets/') !== false;
    }

    /**
     * Tests whether the extension has migrations.
     *
     * @return bool
     */
    public function hasMigrations()
    {
        return realpath($this->path . '/migrations/') !== false;
    }
}