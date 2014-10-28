<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Tokenizer;

use Symfony\Component\Finder\Finder;

/**
 * Collection of Transformer classes.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class Transformers
{
    /**
     * Array of registered Transformer classes.
     *
     * @var Transformer[]
     */
    private $items = array();

    /**
     * Array mapping custom token value => custom token name.
     *
     * @var array
     */
    private $customTokens = array();

    /**
     * Constructor. Register built in Transformers.
     */
    private function __construct()
    {
        $this->registerBuiltInTransformers();
    }

    /**
     * Create Transformers instance.
     *
     * @return Transformers
     */
    public static function create()
    {
        static $instance = null;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    public static function cmpInt($a, $b)
    {
        if ($a === $b) {
            return 0;
        }

        return $a < $b ? -1 : 1;
    }

    /**
     * Get name for registered custom token.
     *
     * @param int $value custom token value
     *
     * @return string
     */
    public function getCustomToken($value)
    {
        if (!$this->hasCustomToken($value)) {
            throw new \InvalidArgumentException("No custom token was found for $value");
        }

        return $this->customTokens[$value];
    }

    public function getTransformers()
    {
        $this->sortTransformers();

        return $this->items;
    }

    /**
     * Check if given custom token was added to collection.
     *
     * @param int $value custom token value
     *
     * @return bool
     */
    public function hasCustomToken($value)
    {
        return isset($this->customTokens[$value]);
    }

    /**
     * Register Transformer.
     *
     * @param TransformerInterface $transformer Transformer
     */
    public function registerTransformer(TransformerInterface $transformer)
    {
        $this->items[] = $transformer;

        $transformer->registerCustomTokens();

        foreach ($transformer->getCustomTokenNames() as $name) {
            $this->addCustomToken(constant($name), $name);
        }
    }

    /**
     * Transform given Tokens collection thru all Transformer classes.
     *
     * @param Tokens $tokens Tokens collection
     */
    public function transform(Tokens $tokens)
    {
        foreach ($this->getTransformers() as $transformer) {
            $transformer->process($tokens);
        }
    }

    /**
     * Add custom token.
     *
     * @param int    $value custom token value
     * @param string $name  custom token name
     */
    private function addCustomToken($value, $name)
    {
        if ($this->hasCustomToken($value)) {
            throw new \LogicException("Trying to register token $name ($value), token with this value was already defined: ".$this->getCustomToken($value));
        }

        $this->customTokens[$value] = $name;
    }

    /**
     * Register all built in Transformatrs.
     */
    private function registerBuiltInTransformers()
    {
        static $registered = false;

        if ($registered) {
            return;
        }

        $registered = true;

        foreach (Finder::create()->files()->in(__DIR__.'/Transformer') as $file) {
            $relativeNamespace = $file->getRelativePath();
            $class = __NAMESPACE__.'\\Transformer\\'.($relativeNamespace ? $relativeNamespace.'\\' : '').$file->getBasename('.php');
            $this->registerTransformer(new $class());
        }
    }

    /**
     * Sort registered Transformers.
     */
    private function sortTransformers()
    {
        $selfName = __CLASS__;

        usort($this->items, function (TransformerInterface $a, TransformerInterface $b) use ($selfName) {
            return $selfName::cmpInt($b->getPriority(), $a->getPriority());
        });
    }
}
