<?php

namespace app\seo\interfaces;

/**
 * Interface SeoUrlInterface
 * @package app\seo\interfaces
 */
interface SeoUrlInterface
{
    /**
     * Build Seo Path
     * @return string|null;
     */
    public function getSeoPath();
}