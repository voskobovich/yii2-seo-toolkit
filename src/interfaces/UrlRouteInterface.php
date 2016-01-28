<?php

namespace voskobovich\seo\interfaces;

/**
 * Interface SeoModelInterface
 * @package voskobovich\seo\interfaces
 */
interface UrlRouteInterface
{
    /**
     * List objects
     * @return array;
     */
    public static function objectItems();

    /**
     * Route map for objects
     * @return array;
     */
    public static function routeMap();
}