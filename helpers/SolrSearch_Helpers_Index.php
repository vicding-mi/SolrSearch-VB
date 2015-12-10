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

            // MAKE SURE EVERYTHING IS INDEXED! AND THAT ALL DATA IS FACETED, BUT NOT SHOWN AS A FACET
            
            if ($field->element_id == '39') {
                if (self::get_elements_private_status_by_value($text->text, "Title", 4)){
//                    _log("ANONIMOUS VERTELLER: " . $text->text);
                    $doc->setMultiValue($field->indexKey(), "anoniem"); //anonymous for public
                    $doc->setMultiValue($field->facetKey(), "anoniem");
                    $doc->setMultiValue($field->indexKey() . "_admin", $text->text);
                    $doc->setMultiValue($field->facetKey() . "_admin", $text->text);
                }
                else{
                    $doc->setMultiValue($field->indexKey(), $text->text);
                    $doc->setMultiValue($field->facetKey(), $text->text);
                    $doc->setMultiValue($field->indexKey() . "_admin", $text->text);
                    $doc->setMultiValue($field->facetKey() . "_admin", $text->text);
                }
            }

            elseif ($field->element_id == '60') {
                if (self::get_elements_private_status_by_value($text->text, "Title", 9) || self::get_elements_private_status_by_value($text->text, "Title", 4)){
//                    _log("ANONIMOUS VERZAMELAAR: " . $text->text);
                    $doc->setMultiValue($field->indexKey(), "anoniem"); //anonymous for public
                    $doc->setMultiValue($field->facetKey(), "anoniem");
                    $doc->setMultiValue($field->indexKey() . "_admin", $text->text);
                    $doc->setMultiValue($field->facetKey() . "_admin", $text->text);
                }
                else{
                    $doc->setMultiValue($field->indexKey(), $text->text);
                    $doc->setMultiValue($field->facetKey(), $text->text);
                    $doc->setMultiValue($field->indexKey() . "_admin", $text->text);
                    $doc->setMultiValue($field->facetKey() . "_admin", $text->text);
                }
            }
            
            else{ //if there are no special privacy problems:
            
                // Set text field.
                if ($field->is_indexed) {
                    $doc->setMultiValue($field->indexKey(), $text->text);
                }

                // Set string field.
                // ADJUST: MAKE AN EXTRA SETTING FOR SHOWING FACETS IN SEQUENCE ->SET ALL TO IS_FACET
                if ($field->is_facet) {
                    $doc->setMultiValue($field->facetKey(), $text->text);
                }
            }
        }
    }

    public static function date_supplement($date){
        if ($date == "99-99-99") return false;
        $date .= "T12:00:00Z";
        return $date;
    }

    public static function date_decennium($start_date){
        if (preg_match('/^\d{4}.\\d{2}.\\d{2}$/', $start_date)){
            list($yy,$mm,$dd) = explode("-",$start_date);
            $decennium = substr_replace($yy,"0",3);
            return $decennium;
        }
        else{
            return false;
        }
    }

    public static function date_validate($date){
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

    public static function classify_length($length){
        if ($length < 25) return '<25';
        elseif ($length < 100) return '25-100';
        elseif ($length < 250) return '100-250';
        elseif ($length < 500) return '250-500';
        elseif ($length < 1000) return '500-1000';
        else return '>1000';
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

        $doc->setField('owner_id', $item->owner_id);

        // extend $doc to to include and items public / private status
        $doc->setField('public', $item->public);

        // Title:
        $title = metadata($item, array('Dublin Core', 'Title'));
        $doc->setField('title', $title);

        // Elements 
        self::indexItem($fields, $item, $doc);

        // Tags:
        foreach ($item->getTags() as $tag) {
            $doc->setMultiValue('tag_t', $tag->name);
            $doc->setMultiValue('tag_s', $tag->name);
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
        //ADDITION FOR VISUALIZATIONS, FACETS AND PRIVACY --> move to indexitem
        
        //PRIVACY for creators, collectors and contributor:
//        if ($text = metadata($item, array('Item Type Metadata', 'Collector'))) {
//            if (self::get_elements_private_status_by_value($search_string, "Title", 9)){
//                $doc->setField('hide_collector', true);
//            }
//        }

/*        if ($text = metadata($item, array('Dublin Core', 'Contributor'))) {
            if (get_elements_private_status_by_value($search_string, "Title", $collection_id = 4)){
                $doc->setField('hide_collector', true);
            }
        }
*/        
        //VISUALIZATION:
        if ($itemType = $item->getItemType()) {
            $doc->setField('itemtype_id', $item->item_type_id);
        }
        if ($collection = $item->getCollection()) {
            $doc->setField('collection_id', $item->collection_id);
        }

        // Start and end date(s):
        $date = metadata($item, array('Dublin Core', 'Date'));
        if (self::date_validate($date)){
            $date_span = explode(' ', $date, 2);
            if (count($date_span) == 2){
                $doc->setField('date_start', self::date_supplement($date_span[0]));
                $doc->setField('date_end', self::date_supplement($date_span[1]));
            }
            elseif (count($date_span) == 1){
                $doc->setField('date_start', self::date_supplement($date_span[0]));
                $doc->setField('date_end', self::date_supplement($date_span[0]));
            }
            $doc->setField('decennium_group', self::date_decennium($date_span[0]));
        }

        // FACETS
        //text size and text size group
        if ($text = metadata($item, array('Item Type Metadata', 'Text'))) {
            $main_word_count = substr_count($text, ' ');
//            $main_text_length = strlen($text); // WOORDEN, NIET LENGTE STRING!!!
            $doc->setField('94_t', $main_word_count);
            $doc->setField('95_t', self::classify_length($main_word_count));
            $doc->setField('94_s', $main_word_count);
            $doc->setField('95_s', self::classify_length($main_word_count));
        }
        
        //Locations 
        $db = get_db();
        $locations = $db->getTable('Location')->findLocationByItem($item, false);

        if ($locations){
            if (array_key_exists('narration_location', $locations)) {
                $location = $locations['narration_location'];
                $doc->setField("latitude", $location->latitude);
                $doc->setField("longitude", $location->longitude);
                $doc->setField("zoom_level", $location->zoom_level);
            
                $doc->setField("map_type_t", $location->map_type);
                $doc->setField("address_t", $location->address);
                $doc->setField("route_t", $location->route);
                $doc->setField("street_number_t", $location->street_number);
                $doc->setField("postal_code_t", $location->postal_code);
                $doc->setField("postal_code_prefix_t", $location->postal_code_prefix);
                $doc->setField("sublocality_t", $location->sublocality);
                $doc->setField("locality_t", $location->locality);
                $doc->setField("natural_feature_t", $location->natural_feature);
                $doc->setField("establishment_t", $location->establishment);
                $doc->setField("point_of_interest_t", $location->point_of_interest);
                $doc->setField("administrative_area_level_3_t", $location->administrative_area_level_3);
                $doc->setField("administrative_area_level_2_t", $location->administrative_area_level_2);
                $doc->setField("administrative_area_level_1_t", $location->administrative_area_level_1);
                $doc->setField("country_t", $location->country);
                $doc->setField("continent_t", $location->continent);
                $doc->setField("planetary_body_t", $location->planetary_body);
            
                $doc->setField("map_type_s", $location->map_type);
                $doc->setField("address_s", $location->address);
                $doc->setField("route_s", $location->route);
                $doc->setField("street_number_s", $location->street_number);
                $doc->setField("postal_code_s", $location->postal_code);
                $doc->setField("postal_code_prefix_s", $location->postal_code_prefix);
                $doc->setField("sublocality_s", $location->sublocality);
                $doc->setField("locality_s", $location->locality);
                $doc->setField("natural_feature_s", $location->natural_feature);
                $doc->setField("establishment_s", $location->establishment);
                $doc->setField("point_of_interest_s", $location->point_of_interest);
                $doc->setField("administrative_area_level_3_s", $location->administrative_area_level_3);
                $doc->setField("administrative_area_level_2_s", $location->administrative_area_level_2);
                $doc->setField("administrative_area_level_1_s", $location->administrative_area_level_1);
                $doc->setField("country_s", $location->country);
                $doc->setField("continent_s", $location->continent);
                $doc->setField("planetary_body_s", $location->planetary_body);
            }
            if (array_key_exists('action_location', $locations)) {
                $location = $locations['action_location'];
                $doc->setField("action_latitude", $location->latitude);
                $doc->setField("action_longitude", $location->longitude);
                $doc->setField("action_zoom_level", $location->zoom_level);
            
                $doc->setField("action_map_type_t", $location->map_type);
                $doc->setField("action_address_t", $location->address);
                $doc->setField("action_route_t", $location->route);
                $doc->setField("action_street_number_t", $location->street_number);
                $doc->setField("action_postal_code_t", $location->postal_code);
                $doc->setField("action_postal_code_prefix_t", $location->postal_code_prefix);
                $doc->setField("action_sublocality_t", $location->sublocality);
                $doc->setField("action_locality_t", $location->locality);
                $doc->setField("action_natural_feature_t", $location->natural_feature);
                $doc->setField("action_establishment_t", $location->establishment);
                $doc->setField("action_point_of_interest_t", $location->point_of_interest);
                $doc->setField("action_administrative_area_level_3_t", $location->administrative_area_level_3);
                $doc->setField("action_administrative_area_level_2_t", $location->administrative_area_level_2);
                $doc->setField("action_administrative_area_level_1_t", $location->administrative_area_level_1);
                $doc->setField("action_country_t", $location->country);
                $doc->setField("action_continent_t", $location->continent);
                $doc->setField("action_planetary_body_t", $location->planetary_body);
            
                $doc->setField("action_map_type_s", $location->map_type);
                $doc->setField("action_address_s", $location->address);
                $doc->setField("action_route_s", $location->route);
                $doc->setField("action_street_number_s", $location->street_number);
                $doc->setField("action_postal_code_s", $location->postal_code);
                $doc->setField("action_postal_code_prefix_s", $location->postal_code_prefix);
                $doc->setField("action_sublocality_s", $location->sublocality);
                $doc->setField("action_locality_s", $location->locality);
                $doc->setField("action_natural_feature_s", $location->natural_feature);
                $doc->setField("action_establishment_s", $location->establishment);
                $doc->setField("action_point_of_interest_s", $location->point_of_interest);
                $doc->setField("action_administrative_area_level_3_s", $location->administrative_area_level_3);
                $doc->setField("action_administrative_area_level_2_s", $location->administrative_area_level_2);
                $doc->setField("action_administrative_area_level_1_s", $location->administrative_area_level_1);
                $doc->setField("action_country_s", $location->country);
                $doc->setField("action_continent_s", $location->continent);
                $doc->setField("action_planetary_body_s", $location->planetary_body);
            }
        }
###############################################################################

        return $doc;

    }

    /**
    *   This piece of code generates sql code to fetch items outside the safe environment of Omeka
    *   
    *
    **/
    private function illegal_sql_generator($search_string, $item_id, $element_name, $collection_id){
        $db = get_db();
        $search_string = mb_convert_encoding($search_string, "CP1252", "UTF-8");
        $search_string = mysql_escape_string($search_string);
    	$sql = "
    	SELECT items.id, text
    	FROM {$db->Item} items 
    	JOIN {$db->ElementText} element_texts 
    	ON items.id = element_texts.record_id 
    	JOIN {$db->Element} elements 
    	ON element_texts.element_id = elements.id 
    	JOIN {$db->ElementSet} element_sets 
    	ON elements.element_set_id = element_sets.id 
    	AND elements.name = '" . $element_name . "'
        AND items.collection_id = '" . $collection_id . "'";
    	if ($search_string) {$sql .= "AND element_texts.text = '" . $search_string . "'"; }
    	if ($item_id) {$sql .= " AND items.id = '" . $item_id . "'"; }
    	return $sql;
    }

    /*  Specific code for checking the "Privacy Required" value of a person 
    * without going through the official permission system.
    * A dirty dirty solution!
    *
    * Example: get_elements_private_status_by_value("Muiser, Iwe")
    * @Returns boolean
    */
    private function get_elements_private_status_by_value($search_string, $element_name = "Title", $collection_id = 4){
        $db = get_db();
    	$config = $db->getAdapter()->getConfig();
        $db_hack = new Zend_Db_Adapter_Pdo_Mysql(array( //call database for checking
        	'host'     => $config["host"],
        	'username' => $config["username"],
        	'password' => $config["password"],
        	'dbname'   => $config["dbname"]));
        if (!$db->getConnection()) {
            _log("__CONNECTION ROTTEN IN get_elements_private_status_by_value");
            return false;
        }
        $sql = self::illegal_sql_generator($search_string, false, $element_name, $collection_id);
        $stmt = $db_hack->prepare($sql);
		$stmt->execute();
		$itemId = $stmt->fetch();
    	if ($itemId){
    	    if (array_key_exists("id", $itemId)){
        	    $sql2 = self::illegal_sql_generator(false, $itemId["id"], "Privacy Required", $collection_id);
                $stmt = $db_hack->prepare($sql2);
        		$stmt->execute();
        		$item = $stmt->fetch();
        		if ($item){
        	    	if (array_key_exists("text", $item)){
                        if ($item["text"] == "ja"){
                            return true;
                        }
                    }
                }
            }
    	}
    	return false;
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
