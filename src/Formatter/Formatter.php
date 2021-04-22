<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use __PHP_Incomplete_Class;
use ArrayObject;
use Flarum\Frontend\Compiler\RevisionCompiler;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Unparser;

class Formatter extends RevisionCompiler
{
    protected $configurationCallbacks = [];

    protected $parsingCallbacks = [];

    protected $unparsingCallbacks = [];

    protected $renderingCallbacks = [];

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var array|null
     */
    protected static $formatter;

    public function __construct(Repository $cache, string $cacheDir, Filesystem $assetsDir)
    {
        $this->cache = $cache;
        $this->cacheDir = $cacheDir;
        $this->assetsDir = $assetsDir;

        $this->filename = 'formatter';
    }

    public function addConfigurationCallback($callback)
    {
        $this->configurationCallbacks[] = $callback;
    }

    public function addParsingCallback($callback)
    {
        $this->parsingCallbacks[] = $callback;
    }

    public function addUnparsingCallback($callback)
    {
        $this->unparsingCallbacks[] = $callback;
    }

    public function addRenderingCallback($callback)
    {
        $this->renderingCallbacks[] = $callback;
    }

    /**
     * Parse text.
     *
     * @param string $text
     * @param mixed $context
     * @return string
     */
    public function parse($text, $context = null)
    {
        $parser = $this->getParser($context);

        foreach ($this->parsingCallbacks as $callback) {
            $text = $callback($parser, $context, $text);
        }

        return $parser->parse($text);
    }

    /**
     * Render parsed XML.
     *
     * @param string $xml
     * @param mixed $context
     * @param ServerRequestInterface|null $request
     * @return string
     */
    public function render($xml, $context = null, ServerRequestInterface $request = null)
    {
        $renderer = $this->getRenderer();

        foreach ($this->renderingCallbacks as $callback) {
            $xml = $callback($renderer, $context, $xml, $request);
        }

        return $renderer->render($xml);
    }

    /**
     * Unparse XML.
     *
     * @param string $xml
     * @param mixed $context
     * @return string
     */
    public function unparse($xml, $context = null)
    {
        foreach ($this->unparsingCallbacks as $callback) {
            $xml = $callback($context, $xml);
        }

        return Unparser::unparse($xml);
    }

    /**
     * Flush the cache so that the formatter components are regenerated.
     */
    public function flush()
    {
        $this->cache->forget('flarum.formatter');
    }

    /**
     * @return Configurator
     */
    protected function getConfigurator()
    {
        $configurator = new Configurator;

        $configurator->rootRules->enableAutoLineBreaks();

        $configurator->rendering->engine = 'PHP';
        $configurator->rendering->engine->cacheDir = $this->cacheDir;

        $configurator->enableJavaScript();
        $configurator->javascript->exports = ['preview'];

        $configurator->javascript->setMinifier('MatthiasMullieMinify')
            ->keepGoing = true;

        $configurator->Escaper;
        $configurator->Autoemail;
        $configurator->Autolink;
        $configurator->tags->onDuplicate('replace');

        foreach ($this->configurationCallbacks as $callback) {
            $callback($configurator);
        }

        $this->configureExternalLinks($configurator);

        return $configurator;
    }

    /**
     * @param Configurator $configurator
     */
    protected function configureExternalLinks(Configurator $configurator)
    {
        $dom = $configurator->tags['URL']->template->asDOM();

        foreach ($dom->getElementsByTagName('a') as $a) {
            $rel = $a->getAttribute('rel');
            $a->setAttribute('rel', "$rel nofollow ugc");
        }

        $dom->saveChanges();
    }

    /**
     * Get a TextFormatter component.
     *
     * @param string $name "renderer" or "parser" or "js"
     * @return mixed
     */
    protected function getComponent(string $name)
    {
        if (! static::$formatter) {
            static::$formatter = $this->cache->rememberForever('flarum.formatter', function () {
                return $this->finalize();
            });
        }

        // We will now execute a check on disk, to see whether the requested renderer
        // is written to disk. In case cache is not a local file-based driver the below
        // `getRenderer()` method won't be able to autoload the file.
        if ($name === 'renderer') {
            $this->ensureRendererExists();
        }

        // We will now check revisions and do a sanity check.
        if ($this->requiresRefresh()) {
            $this->finalize();
        }

        return Arr::get(static::$formatter, $name);
    }

    /**
     * Get the parser.
     *
     * @param mixed $context
     * @return \s9e\TextFormatter\Parser
     */
    protected function getParser($context = null)
    {
        $parser = $this->getComponent('parser');

        $parser->registeredVars['context'] = $context;

        return $parser;
    }

    /**
     * Get the renderer.
     *
     * @return \s9e\TextFormatter\Renderer
     */
    protected function getRenderer()
    {
        spl_autoload_register(function ($class) {
            if (file_exists($file = $this->cacheDir.'/'.$class.'.php')) {
                include $file;
            }
        });

        return $this->getComponent('renderer');
    }

    /**
     * Get the formatter JavaScript.
     *
     * @return string
     */
    public function getJs()
    {
        return $this->getComponent('js');
    }

    protected function ensureRendererExists()
    {
        if (! static::$formatter) {
            return;
        }

        $revision = $this->getRevision();

        if (file_exists($this->cacheDir."/Renderer_$revision.php")) {
            return;
        }

        $renderer = Arr::get(static::$formatter, 'renderer');

        if (! empty($renderer)) {
            file_put_contents($this->cacheDir."/Renderer_$revision.php", $renderer);
        } else {
            $this->finalize();
        }

        // Reload because finalizing might have generated a new one.
        $renderer = Arr::get(static::$formatter, 'renderer');

        if ($renderer && static::$formatter['renderer'] instanceof __PHP_Incomplete_Class) {
            // Autoload the file from disk using a simple include, while suppressing errors.
            @include $this->cacheDir."/Renderer_$revision.php";

            // Reload the formatter again from cache to resolve the __PHP_Incomplete_Class
            static::$formatter = $this->cache->get('flarum.formatter');
        }
    }

    protected function finalize()
    {
        $formatter = $this->getConfigurator()->finalize();

        preg_match('~^Renderer\_(?<revision>[^\.]+)$~', get_class($formatter['renderer']), $m);
        $revision = $m['revision'];

        $this->putRevision($revision);

        return $formatter;
    }

    protected function requiresRefresh(): bool
    {
        if (! $this->getRevision()) {
            return true;
        }

        if (! Arr::get(static::$formatter, 'renderer')) {
            return true;
        }

        $renderer = static::$formatter['renderer'] instanceof __PHP_Incomplete_Class
            ? (new ArrayObject(static::$formatter['renderer']))['__PHP_Incomplete_Class_Name']
            : get_class(static::$formatter['renderer']);

        if (preg_match('~^Renderer_(?<revision>[^\.]+)$~', $renderer, $m)) {
            return $this->getRevision() !== $m['revision'];
        }

        return true;
    }
}
