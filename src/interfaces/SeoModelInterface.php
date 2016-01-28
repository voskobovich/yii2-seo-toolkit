<?php

namespace voskobovich\seo\interfaces;

/**
 * Interface SeoModelInterface
 * @package voskobovich\seo\interfaces
 */
interface SeoModelInterface
{
    /**
     * Build Seo Path
     * @return string|null;
     */
    public function getSeoPath();
}