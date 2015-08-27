<?php

/**
 * @package     omeka
 * @subpackage  solr-search
 * @copyright   2012 Rector and Board of Visitors, University of Virginia
 * @license     http://www.apache.org/licenses/LICENSE-2.0.html
 */


class SolrSearch_Helpers_Index
{


    /**
     * Connect to Solr.
     *
     * @param array $options An array of connection parameters.
     *
     * @return Apache_Solr_Service
     * @author David McClure <david.mcclure@virginia.edu>
     **/
    public static function connect($options=array())
    {

        $server = array_key_exists('solr_search_host', $options)
            ? $options['solr_search_host']
            : get_option('solr_search_host');

        $port = array_key_exists('solr_search_port', $options)
            ? $options['solr_search_port']
            : get_option('solr_search_port');

        $core = array_key_exists('solr_search_core', $options)
            ? $options['solr_search_core']
            : get_option('solr_search_core');

        return new Apache_Solr_Service($server, $port, $core);

    }

    /**
     * This indexes something that implements Mixin_ElementText into a Solr Document.
     *
     * @param array                $fields The fields to index.
     * @param Mixin_ElementText    $item   The item containing the element texts.
     * @param Apache_Solr_Document $doc    The document to index everything into.
     * @return void
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public static function indexItem($fields, $item, $doc)
    {
        foreach ($item->getAllElementTexts() as $text) {
            $field = $fields->findByText($text);

            // Set text field.
            if ($field->is_indexed) {
                $doc->setMultiValue($field->indexKey(), $text->text);
            }

            // Set string field.
            if ($field->is_facet) {
                $doc->setMultiValue($field->facetKey(), $text->text);
            }
        }
    }

    public static function date_supplement($date){
        if ($date == "99-99-99") return false;
        $date .= "T12:00:00Z";
        return $date;
    }

    public static function validate($date){
        if (preg_match('/^\d{4}.\\d{2}.\\d{2}\s\d{4}.\\d{2}.\\d{2}$/', $date)){
            $date_span = explode(' ', $date, 2);
            list($yy,$mm,$dd) = explode("-",$date_span[0]);
            list($yy2,$mm2,$dd2) = explode("-",$date_span[1]);
            if(checkdate($mm,$dd,$yy) AND checkdate($mm2,$dd2,$yy2)){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }


    /**
     * This takes an Omeka_Record instance and returns a populated
     * Apache_Solr_Document.
     *
     * @param Omeka_Record $item The record to index.
     *
     * @return Apache_Solr_Document
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public static function itemToDocument($item)
    {

        $fields = get_db()->getTable('SolrSearchField');

        $doc = new Apache_Solr_Document();
        $doc->setField('id', "Item_{$item->id}");
        $doc->setField('resulttype', 'Item');
        $doc->setField('model', 'Item');
        $doc->setField('modelid', $item->id);

        // extend $doc to to include and items public / private status
        $doc->setField('public', $item->public);

        // Title:
        $title = metadata($item, array('Dublin Core', 'Title'));
        $doc->setField('title', $title);

        // Elements:
        self::indexItem($fields, $item, $doc);

        // Tags:
        foreach ($item->getTags() as $tag) {
            $doc->setMultiValue('tag', $tag->name);
        }   

        // Collection:
        if ($collection = $item->getCollection()) {
            $doc->collection = metadata(
                $collection, array('Dublin Core', 'Title')
            );
        }

        // Item type:
        if ($itemType = $item->getItemType()) {
            $doc->itemtype = $itemType->name;
        }

        $doc->featured = (bool) $item->featured;

        // File metadata (this is weird)
//        foreach ($item->getFiles() as $file) {
//            self::indexItem($fields, $file, $doc);
//        }

###############################################################################
        //ADDITION FOR VISUALIZATIONS
        if ($itemType = $item->getItemType()) {
            $doc->setField('itemtype_id', $item->item_type_id);
        }
        if ($collection = $item->getCollection()) {
            $doc->setField('collection_id', $item->collection_id);
        }

        // Start and end date(s):
        $date = metadata($item, array('Dublin Core', 'Date'));
        if (self::validate($date)){
            $date_span = explode(' ', $date, 2);
            if (count($date_span) == 2){
                $doc->setField('date_start', self::date_supplement($date_span[0]));
                $doc->setField('date_end', self::date_supplement($date_span[1]));
            }
            elseif (count($date_span) == 1){
                $doc->setField('date_start', self::date_supplement($date_span[0]));
                $doc->setField('date_end', self::date_supplement($date_span[0]));
            }
        }

        //Locations
        $db     = get_db();
        $location = $db->getTable('Location')->findLocationByItem($item, true);

        if ($location){
            $doc->setField("latitude", $location->latitude);
            $doc->setField("longitude", $location->longitude);
            $doc->setField("zoom_level", $location->zoom_level);
            $doc->setField("map_type", $location->map_type);
            $doc->setField("address", $location->address);
            $doc->setField("route", $location->route);
            $doc->setField("street_number", $location->street_number);
            $doc->setField("postal_code", $location->postal_code);
            $doc->setField("postal_code_prefix", $location->postal_code_prefix);
            $doc->setField("sublocality", $location->sublocality);
            $doc->setField("locality", $location->locality);
            $doc->setField("natural_feature", $location->natural_feature);
            $doc->setField("establishment", $location->establishment);
            $doc->setField("point_of_interest", $location->point_of_interest);
            $doc->setField("administrative_area_level_3", $location->administrative_area_level_3);
            $doc->setField("administrative_area_level_2", $location->administrative_area_level_2);
            $doc->setField("administrative_area_level_1", $location->administrative_area_level_1);
            $doc->setField("country", $location->country);
            $doc->setField("continent", $location->continent);
            $doc->setField("planetary_body", $location->planetary_body);
        }
###############################################################################

        return $doc;

    }


    /**
     * This returns the URI for an Omeka_Record.
     *
     * @param Omeka_Record $record The record to return the URI for.
     *
     * @return string $uri The URI to access the record with.
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public static function getUri($record)
    {
        $uri    = '';
        $action = 'show';
        $rc     = get_class($record);

        if ($rc === 'SimplePagesPage') {
            $uri = url($record->slug);
        }

        else if ($rc === 'ExhibitPage') {

            $exhibit = $record->getExhibit();
            $exUri   = self::getSlugUri($exhibit, $action);
            $uri     = "$exUri/$record->slug";

        } else if (property_exists($record, 'slug')) {
            $uri = self::getSlugUri($record, $action);
        } else {
            $uri = record_url($record, $action);
        }

        // Always index public URLs.
        $uri = preg_replace('|/admin/|', '/', $uri, 1);

        return $uri;
    }


    /**
     * This returns the URL for an Omeka_Record with a 'slug' property.
     *
     * @param Omeka_Record $record The sluggable record to create the URL for.
     * @param string       $action The action to access the record with.
     *
     * @return string $uri The URI for the record.
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public static function getSlugURI($record, $action)
    {
        // Copied from omeka/applications/helpers/UrlFunctions.php, record_uri.
        // Yuck.
        $recordClass = get_class($record);
        $inflector   = new Zend_Filter_Word_CamelCaseToDash();
        $controller  = strtolower($inflector->filter($recordClass));
        $controller  = Inflector::pluralize($controller);
        $options     = array(
            'controller' => $controller,
            'action'     => $action,
            'id'         => $record->slug
        );
        $uri = url($options, 'id');

        return $uri;
    }


    /**
     * This pings the Solr server with the given options and returns true if
     * it's currently up.
     *
     * @param array $options The configuration to test. Missing values will be
     * pulled from the current set of options.
     *
     * @return bool
     * @author Eric Rochester <erochest@virginia.edu>
     */
    public static function pingSolrServer($options=array())
    {
        try {
            return self::connect($options)->ping();
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * This re-indexes everything in the Omeka DB.
     *
     * @return void
     * @author Eric Rochester
     **/
    public static function indexAll($options=array())
    {

        $solr = self::connect($options);

        $db     = get_db();
        $table  = $db->getTable('Item');
        $select = $table->getSelect();

        // Removed in order to index both public and private items
        // $table->filterByPublic($select, true);
        $table->applySorting($select, 'id', 'ASC');

        $excTable = $db->getTable('SolrSearchExclude');
        $excludes = array();
        foreach ($excTable->findAll() as $e) {
            $excludes[] = $e->collection_id;
        }
        if (!empty($excludes)) {
            $select->where(
                'collection_id IS NULL OR collection_id NOT IN (?)',
                $excludes);
        }

        // First get the items.
        $pager = new SolrSearch_DbPager($db, $table, $select);
        while ($items = $pager->next()) {
            foreach ($items as $item) {
                $docs = array();
                $doc = self::itemToDocument($item);
                $docs[] = $doc;
                $solr->addDocuments($docs);
            }
            $solr->commit();
        }

        // Now the other addon stuff.
        $mgr  = new SolrSearch_Addon_Manager($db);
        $docs = $mgr->reindexAddons();
        $solr->addDocuments($docs);
        $solr->commit();

        $solr->optimize();

    }


    /**
     * This deletes everything in the Solr index.
     *
     * @param array $options The configuration to test. Missing values will be
     * pulled from the current set of options.
     *
     * @return void
     * @author Eric Rochester
     **/
    public static function deleteAll($options=array())
    {

        $solr = self::connect($options);

        $solr->deleteByQuery('*:*');
        $solr->commit();
        $solr->optimize();

    }


}
