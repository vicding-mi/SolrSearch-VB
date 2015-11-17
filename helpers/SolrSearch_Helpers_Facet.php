<?php

/**
 * @package     omeka
 * @subpackage  solr-search
 * @copyright   2012 Rector and Board of Visitors, University of Virginia
 * @license     http://www.apache.org/licenses/LICENSE-2.0.html
 */


class SolrSearch_Helpers_Facet
{


    /**
     * Convert $_GET into an array with exploded facets.
     *
     * @return array The parsed parameters.
     */
    public static function parseAllSearches()
    {

        $facets = array();

        if (array_key_exists('facet', $_GET)) {
            // Extract the field/value facet pairs.
//            preg_match_all('/(?P<field>[\w]+):[\*"](?P<value>[^\*"]+)[\*"]/',
//            preg_match_all('/(?P<field>[\w]+):[\*](?P<value>[^\*"]+)[\*]/',
            preg_match_all('/(?P<field>[\w]+(_[ts])*):(?P<value>[\w"\*\+]+)/',
                $_GET['facet'], $matches
            );

            // Collapse into an array of pairs.
            foreach ($matches['field'] as $i => $field) {
                $facets[] = array($field, $matches['value'][$i]);
            }

        }

        return $facets;

    }

    /**
     * Convert $_GET into an array with exploded facets.
     *
     * @return array The parsed parameters.
     */
    public static function parseFreeSearch()
    {

        $facets = array();

        if (array_key_exists('free', $_GET)) {
            // Extract the field/value facet pairs.
            preg_match_all('/(?P<field>[\w]+(_t)*):\{(?P<value>[^\}]+)\}/',
                $_GET['free'], $matches
            );

            // Collapse into an array of pairs.
            foreach ($matches['field'] as $i => $field) {
                $facets[] = array($field, $matches['value'][$i]);
            }

        }

        return $facets;

    }

    /**
     * Convert $_GET into an array with exploded facets.
     *
     * @return array The parsed parameters.
     */
    public static function parseFacets()
    {

        $facets = array();

        if (array_key_exists('facet', $_GET)) {

            // Extract the field/value facet pairs.
            preg_match_all('/(?P<field>[\w]+[_s]*):"(?P<value>[^"]+)"/',
                $_GET['facet'], $matches
            );

            // Collapse into an array of pairs.
            foreach ($matches['field'] as $i => $field) {
                $facets[] = array($field, $matches['value'][$i]);
            }

        }

        return $facets;

    }


    /**
     * Rebuild the URL with a new array of facets.
     *
     * @param array $facets The parsed facets.
     * @return string The new URL.
     */
    public static function makeUrl($facets, $frees, $baseUrl = "solr-search")
    {

        // Collapse the facets to `:` delimited pairs.
        $fParam = array();
        foreach ($facets as $facet) {
            $fParam[] = "{$facet[0]}:\"{$facet[1]}\"";
        }
        $freeParam = array();
        foreach ($frees as $free) {
            $freeParam[] = "{$free[0]}:{{$free[1]}}";
        }

        // Implode on ` AND `.
        $fParam = urlencode(implode(' AND ', $fParam));
        $freeParam = urlencode(implode(' AND ', $freeParam));

        // Get the `q` parameter, reverting to ''.
        $qParam = array_key_exists('q', $_GET) ? $_GET['q'] : '';

        // Get the base results URL.
        
        $results = url($baseUrl);

        // String together the final route.
        return htmlspecialchars("$results?q=$qParam&facet=$fParam&free=$freeParam");

    }


    /**
     * Add a facet to the current URL.
     *
     * @param string $field The facet field.
     * @param string $value The facet value.
     * @return string The new URL.
     */
    public static function addFacet($field, $value, $baseUrl = "solr-search")
    {
        // Get the current free searches.
        $frees = self::parseFreeSearch();

        // Get the current facets.
        $facets = self::parseFacets();

        // Add the facet, if it's not already present.
        if (!in_array(array($field, $value), $facets)) {
            $facets[] = array($field, $value);
        }

        // Rebuild the route.
        return self::makeUrl($facets, $frees, $baseUrl);

    }


    /**
     * Remove a free search to the current URL.
     *
     * @param string $field The facet field.
     * @param string $value The facet value.
     * @return string The new URL.
     */
    public static function removeFreeSearch($field, $value, $baseUrl = "solr-search")
    {
        // Get the current free searches.
        $frees = self::parseFreeSearch();

        // Get the current facets.
        $facets = self::parseFacets();

        // Reject the field/value pair.
        $reduced = array();
        foreach ($frees as $free) {
            if ($free !== array($field, $value)) $reduced[] = $free;
        }

        // Rebuild the route.
        return self::makeUrl($facets, $reduced, $baseUrl);

    }


    /**
     * Remove a facet to the current URL.
     *
     * @param string $field The facet field.
     * @param string $value The facet value.
     * @return string The new URL.
     */
    public static function removeFacet($field, $value, $baseUrl = "solr-search")
    {

        // Get the current free searches.
        $frees = self::parseFreeSearch();
        
        // Get the current facets.
        $facets = self::parseFacets();

        // Reject the field/value pair.
        $reduced = array();
        foreach ($facets as $facet) {
            if ($facet !== array($field, $value)) $reduced[] = $facet;
        }

        // Rebuild the route.
        return self::makeUrl($reduced, $frees, $baseUrl);

    }


    /**
     * Get the human-readable label for a facet key.
     * 
     * If human readable is already present: return facetname
     *
     * @param string $key The facet key.
     * @return string The label.
     */
    public static function keyToLabel($key)
    {
        $fields = get_db()->getTable('SolrSearchField');
        if (strpos($key, '_s') !== false) {
            return $fields->findBySlug(rtrim($key, '_s'))->label;
        }
        elseif (strpos($key, '_t') !== false) {
            return __($fields->findBySlug(rtrim($key, '_t'))->label) . "";
        }else{
            return ucfirst(__($key));
        }
    }

}
