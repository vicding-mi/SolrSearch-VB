<?php

/**
 * @package     omeka
 * @subpackage  solr-search
 * @copyright   2012 Rector and Board of Visitors, University of Virginia
 * @license     http://www.apache.org/licenses/LICENSE-2.0.html
 */


class SolrSearch_ResultsController
    extends Omeka_Controller_AbstractActionController
{

        /*
        Same as tags autocomplete but with more parameters
        returns a page with a simple list
        */
        function autocompleteAction(){
            $this->_fields = $this->_helper->db->getTable('SolrSearchField');
            $searchText = $this->_getParam('term');
            $elementId = $this->_getParam('id');
            $sorting = $this->_getParam('sorting');         //(to return latest existing ID's for instance)
            if (!$sorting) $sorting = "ASC";
            $limit = $this->_getParam('limit');         //(to return latest existing ID's for instance)
            if (!$limit) $limit = "15";

            // Connect to Solr.
            $solr = SolrSearch_Helpers_Index::connect();

            // Construct the query.
//            $query = $elementId . ":*" . $searchText . "*";
            $query = "*:*";

            $params = array(
                'rows'                => 0,
                'facet'               => 'true',
                'facet.field'         => $elementId,
                'facet.mincount'      => 1,
                'facet.limit'         => $limit,
                'facet.prefix'        => $searchText
//                'facet.sort'          => get_option('solr_search_facet_sort'),
//                'fl'                  => $elementId
            );

            // Execute the query.
            $response = $solr->search($query, 0, 0, $params);
            
            $returner = array();
            
            foreach($response->facet_counts->facet_fields->{$elementId} as $key => $facet){
                $returner[] = $key;
            }
            
            $this->_helper->json($returner);
        }

        /*
        Same as tags autocomplete but with more parameters
        returns a page with a simple list
        */
        function autocompleteActionTEST(){
            // Get pagination settings.
            $limit = 15;
            $page  = 1;
            $start = 0;

            // determine whether to display private items or not
            // items will only be displayed if:
            // solr_search_display_private_items has been enabled in the Solr Search admin panel
            // user is logged in
            // user_role has sufficient permissions

            $user = current_user();
            if(get_option('solr_search_display_private_items')
                && $user
                && is_allowed('Items','showNotPublic')) {
                // limit to public items
                $limitToPublicItems = false;
            } else {
                $limitToPublicItems = true;
            }

            // Execute the query.
            $results = $this->_search($start, $limit, $limitToPublicItems);

            // Set the pagination.
            Zend_Registry::set('pagination', array(
                'page'          => $page,
                'total_results' => $results->response->numFound,
                'per_page'      => $limit
            ));

            // Push results to the view.
            $this->session->page_nr = 1;
            $this->view->results = $results;
            
            $this->_helper->json(print_r($results, true));
        }

    /**
     * Cache the facets table.
     */
    public function init()
    {
        
        $solr_search_fields = $this->_helper->db->getTable('SolrSearchField');
                
        $this->_fields = $solr_search_fields;
        
        $this->session = new Zend_Session_Namespace();
    }


    /**
     * Intercept queries from the simple search form.
     */
    public function interceptorAction()
    {
        $this->_redirect('solr-search?'.http_build_query(array(
            'q' => $this->_request->getParam('query')
        )));
    }


    protected function _publicAccessIndexedRestriction(){
        
        // Get a list of active indexes.
        $indexed = $this->_fields->getIndexedKeys();
        
        //replacing returing indexes privacy sensitive fields with admin fields
        $admin_replace = array("39_s", "60_s");
        if ($user = current_user()){ #different idexes when logged in
            foreach ($indexed as $ikey => $index){
                if (in_array($index, $admin_replace)){
                    $indexed[$ikey] = $index  . "_admin";
                }
            }
        }
        return $indexed;
    }

    public function searchFormAction(){
        
        $indexed = $this->_publicAccessIndexedRestriction();
        
        $this->view->indexed = $indexed;
    }


    public function resultListAction()
    {
    
        // Get pagination settings.
        $limit = get_option('per_page_public');
        
        $this->session->page_nr += 1;
        $page  = $this->session->page_nr;
        $start = ($page-1) * $limit;

        // determine whether to display private items or not
        // items will only be displayed if:
        // solr_search_display_private_items has been enabled in the Solr Search admin panel
        // user is logged in
        // user_role has sufficient permissions

        $user = current_user();
        if(get_option('solr_search_display_private_items')
            && $user
            && is_allowed('Items','showNotPublic')) {
            // limit to public items
            $limitToPublicItems = false;
        } else {
            $limitToPublicItems = true;
        }

        // Execute the query.
        $results = $this->_session_search($start, $limit, $limitToPublicItems);

        // Push results to the view.
        $this->view->results = $results;
    }
    


    /**
     * Display Solr results.
     */
    public function indexAction()
    {
        
        // Get pagination settings.
        $limit = get_option('per_page_public');
        $page  = $this->_request->page ? $this->_request->page : 1;
        $start = ($page-1) * $limit;

        // determine whether to display private items or not
        // items will only be displayed if:
        // solr_search_display_private_items has been enabled in the Solr Search admin panel
        // user is logged in
        // user_role has sufficient permissions

        $user = current_user();
        if(get_option('solr_search_display_private_items')
            && $user
            && is_allowed('Items','showNotPublic')) {
            // limit to public items
            $limitToPublicItems = false;
        } else {
            $limitToPublicItems = true;
        }

        // Execute the query.
        $results = $this->_search($start, $limit, $limitToPublicItems);

        // Set the pagination.
        Zend_Registry::set('pagination', array(
            'page'          => $page,
            'total_results' => $results->response->numFound,
            'per_page'      => $limit
        ));

        // Push results to the view.
        $this->session->page_nr = 1;
        $this->view->results = $results;

    }


    /**
     * Pass setting to Solr search
     *
     * @param int $offset Results offset
     * @param int $limit  Limit per page
     * @return SolrResultDoc Solr results
     */
    protected function _session_search($offset, $limit, $limitToPublicItems = true)
    {

        // Connect to Solr.
        $solr = SolrSearch_Helpers_Index::connect();

        // Get the parameters.
        $params = $this->_getParameters();

        // Construct the query.
        $query = $this->session->query;

        // Execute the query.
        return $solr->search($query, $offset, $limit, $params);

    }

    /**
     * Pass setting to Solr search
     *
     * @param int $offset Results offset
     * @param int $limit  Limit per page
     * @return SolrResultDoc Solr results
     */
    protected function _search($offset, $limit, $limitToPublicItems = true)
    {

        // Connect to Solr.
        $solr = SolrSearch_Helpers_Index::connect();

        // Get the parameters.
        $params = $this->_getParameters();

        // Construct the query.
        $query = $this->_getQuery($limitToPublicItems);

        // Execute the query.
        _log("SOLRQUERY->" . $query);
        return $solr->search($query, $offset, $limit, $params);

    }


    /**
     * Form the complete Solr query.
     *
     * @return string The Solr query.
     */
    protected function _getQuery($limitToPublicItems = true)
    {

        // Get the `q` GET parameter.
        $query = $this->_request->q;

        // If defined, replace `:`; otherwise, revert to `*:*`.
        // Also, clean it up some.
        if (!empty($query)) {
            $query = str_replace(':', ' ', $query);
            $to_remove = array('[', ']');
            foreach ($to_remove as $c) {
                $query = str_replace($c, '', $query);
            }
        } else {
            $query = '*:*';
        }

        // Get the `facet` GET parameter
        $facet = $this->_request->facet;

        // Form the composite Solr query.
        if (!empty($facet)) $query .= " AND {$facet}";

        // Limit the query to public items if required
        if($limitToPublicItems) {
           $query .= ' AND public:"true"';
        }

        // Get the `free` search GET parameter (pre-turned into string)
        $free = "{$this->_request->free}";

        $to_remove_free = array('{', '}');
        foreach ($to_remove_free as $c) {
            $free = str_replace($c, '', $free);
        }
        $to_remove_other = array('-');
        foreach ($to_remove_other as $c) {
            $free = str_replace($c, ' ', $free);
        }

        // Form the composite Solr query.
        if (!empty($free)) $query .= " AND $free";

        $this->session->query = $query;
        $this->view->query = $this->_request->q;
        $this->view->facet = $this->_request->facet;
        $this->view->free = $this->_request->free;
        return $query;

    }


    /**
     * Construct the Solr search parameters.
     *
     * @return array Array of fields to pass to Solr
     */
    protected function _getParameters()
    {

        $facets = $this->_publicAccessFacetsRestriction();
        $default_search_field = $this->_defaultSearchField();
        
        return array(
            'facet'               => 'true',
            'facet.field'         => $facets,
            'facet.mincount'      => 1,
            'facet.limit'         => get_option('solr_search_facet_limit'),
            'facet.sort'          => get_option('solr_search_facet_sort'),
            'hl'                  => get_option('solr_search_hl') ? 'true' : 'false',
            'hl.snippets'         => get_option('solr_search_hl_snippets'),
            'hl.fragsize'         => get_option('solr_search_hl_fragsize'),
            'hl.maxAnalyzedChars' => get_option('solr_search_hl_max_analyzed_chars'),
            'hl.fl'               => $this->_publicAccessResultRestriction(),
            'df'                  => $default_search_field
        );
    }
    
    protected function _publicAccessFacetsRestriction(){
        
        // Get a list of active facets.
        $facets = $this->_fields->getActiveFacetKeys();
        
        //replacing returing facets privacy sensitive fields with admin fields
        $admin_replace = array("39_s", "60_s");
        if ($user = current_user()){ #different facets when logged in
            foreach ($facets as $fkey => $facet){
                if (in_array($facet, $admin_replace)){
                    $facets[$fkey] = $facet . "_admin";
                }
            }
        }
        return $facets;
    }
    
    protected function _defaultSearchField(){
        if ($user = current_user()) {
            return "text_admin";
        }
        else{
            return "text";
        }
    }    
    
    protected function _publicAccessResultRestriction(){
        if ($user = current_user()) {
            return "*_t"; #with "*_admin"??
        }
        else{ //a fairly extreme protection of privacy
            return "*_t";
//            return "1_t";
        }
    }


}
