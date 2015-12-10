<?php

/**
 * @package     omeka
 * @subpackage  solr-search
 * @copyright   2012 Rector and Board of Visitors, University of Virginia
 * @license     http://www.apache.org/licenses/LICENSE-2.0.html
 */

?>

<?php queue_css_file('results'); ?>

<?php queue_js_file('moment'); ?>
<?php queue_js_file('jquery.daterangepicker'); ?>
<?php queue_js_string('var search_url = "' . url("solr-search") . '";
                        var autocompleteChoicesUrl = ' . js_escape(url('solr-search/results/autocomplete')) . ';'); ?>
<?php queue_js_file('searchformfunctions'); ?>
<?php 
$key = get_option('geolocation_gmaps_key');// ? get_option('geolocation_gmaps_key') : 'AIzaSyD6zj4P4YxltcYJZsRVUvTqG_bT1nny30o';
$lang = "nl";
queue_js_url("https://maps.googleapis.com/maps/api/js?sensor=false&libraries=places&key=$key&language=$lang");
?>
<?php echo head(array('title' => __('Solr Search')));?>

<script type="text/javascript" charset="utf-8">
//<![CDATA[

//]]>
</script>

<h1><?php echo __('Search the Collection'); ?></h1>

<style>
	#content > div{
		-webkit-box-shadow: none;
		box-shadow: none;
	}
</style>

<br>

<div id="solr" style="border:0px">
    <!-- Applied facets. -->
    <div id="solr-applied-facets" style="float:left">
	    <ul>
    		<!-- Get the applied facets. -->
    		<?php 
    			$count = 0;
    			foreach (SolrSearch_Helpers_Facet::parseFacets() as $f): 
    				$count++;
    		?>
    		  <li>

    			<!-- Facet label. -->
    			<?php $label = SolrSearch_Helpers_Facet::keyToLabel($f[0]); ?>
    			<span class="applied-facet-label"><?php echo __($label); ?>:</span>
    			<span class="applied-facet-value"><?php echo $f[1]; ?></span>

    			<!-- Remove link. -->
    			<?php $url = SolrSearch_Helpers_Facet::removeFacet($f[0], $f[1]); ?>
    			(<a href="<?php echo $url; ?>"><?php echo __('remove'); ?></a>)

    		  </li>
    		<?php
    			endforeach;		
    		?>
    	</ul>
	
    	<?php if($count == 0) echo '<span>Geen filters geselecteerd</span>' ?>
    </div>

    <?php echo SolrSearch_Helpers_View::visualize_results_functions($_REQUEST); ?>
</div>

<!-- Facets. -->
<?php 
$applied_freesearch = SolrSearch_Helpers_Facet::parseFreeSearch();
$applied_facets = SolrSearch_Helpers_Facet::parseFacets();

$applied_searches = array_merge($applied_freesearch, $applied_facets);

//reordering facets to pre fill the form based on URL arguments
$ordered_applied_facets = array();
foreach($applied_searches as $applied_facet){
    // change into free text search instead of facet string search
    $af = str_replace("_t", "", str_replace("_w", "", $applied_facet[0]));
    $af_t = $af . "_t";
    if (!array_key_exists($af_t, $ordered_applied_facets)){
        $ordered_applied_facets[$af_t] = array();
    }
    $ordered_applied_facets[$af_t][] = $applied_facet[1];
}

?>

<div id="solr-form">

    <h2><?php echo __('Search'); ?></h2>
    <!-- In order from the settings -->

    <!-- Search form. -->
    <form id="solr-search-form">
        <table width="100%">
            <tr width="100px">
                <td style="vertical-align: top;"><strong><?php echo "Vrije zoektermen"; ?></strong></td>
                <td></td>
                <td><input type="text" title="<?php echo __('Search keywords') ?>" class="free-search-value" id="freequery" style="width:50%" value="<?php
                    echo array_key_exists('q', $_GET) ? $_GET['q'] : ''; ?>" /></td>
            </tr>
          </span>
          <?php foreach ($indexed as $facet_name): ?>
              <?php $free_facet_name = preg_replace('/_s_admin$/i', '_t_admin', $facet_name); ?>
              <?php $free_facet_name = preg_replace('/_s$/i', '_t', $free_facet_name); ?>
              <tr>
                  <?php $label = __(SolrSearch_Helpers_Facet::keyToLabel($facet_name)); ?>
                  <td style="vertical-align: top;" width="100px"><strong><?php echo $label; ?></strong></td>
                  <td style="vertical-align: top;" width="40px"><button type="button" class="add_search"><?php echo "+"; ?></button></td>
                  <td>
                  <?php if (array_key_exists($free_facet_name, $ordered_applied_facets)): ?>
                      <?php foreach($ordered_applied_facets[$free_facet_name] as $facet_search_value): ?>
                          <div style="display: inline">
                              <?php
                                $form_element = "no filled form element";
                                $form_element = $this->formText(
                                    $free_facet_name,
                                    $facet_search_value,
                                    array("class" => "facet-search-value",
                                        "style" => "margin-bottom:0; width:50%; min-width: 85px;")
                                );
                                if(plugin_is_active('SimpleVocab')) {
                                    $simpleVocabTerm = get_db()->getTable('SimpleVocabTerm')->findByElementId(str_replace("_s", "", $facet_name));
                                    if ($simpleVocabTerm){
                                        $terms = explode("\n", $simpleVocabTerm->terms);
                                        $selectTerms = array('' => '-') + array_combine($terms, $terms);
                                        $form_element = $this->formSelect(
                                            $free_facet_name,
                                            $facet_search_value,
                                            array("class" => "facet-search-value",
                                                    'style' => 'margin-bottom:0; width:50%; min-width: 85px; font-size:1.2em;'), 
                                            $selectTerms
                                        );
                                    }
                                }
                                echo $form_element;?>
                              <button type="button" class="remove_search" style="display:none;">-</button>
                          </div>
                      <?php endforeach; ?>
                  <?php endif; ?>
                  <?php if (!array_key_exists($facet_name, $ordered_applied_facets)): ?>
                      <div style="display: inline">
                          <?php
                            $form_element = "no empty form element";
                            $form_element = $this->formText(
                                $free_facet_name,
                                "",
                                array("class" => "facet-search-value",
                                    "style" => "margin-bottom:0; width:50%; min-width: 85px;")
                            );
                            if(plugin_is_active('SimpleVocab')) {
                                $simpleVocabTerm = get_db()->getTable('SimpleVocabTerm')->findByElementId(str_replace("_s", "", $facet_name));
                                if ($simpleVocabTerm){
                                    $terms = explode("\n", $simpleVocabTerm->terms);
                                    $selectTerms = array('' => '-') + array_combine($terms, $terms);
                                    $form_element = $this->formSelect(
                                        $free_facet_name,
                                        "",
                                        array("class" => "facet-search-value",
                                            'style' => 'margin-bottom:0; width:50%; min-width: 85px; font-size:1.2em;'), 
                                        $selectTerms
                                    );
                                }
                            }
                            echo $form_element;
                            ?>
                          <button type="button" class="remove_search" style="display:none;">-</button>
                      </div>
                  <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            
            
            <tr>
                <?php $label = __("Place of narration") . " (radius)"; ?>
                <td style="vertical-align: top;" width="100px"><strong><?php echo $label; ?></strong></td>
                <td></td>
                <td>
                    <?php echo $this->formText('geolocation-address', "", array('name'=>'geolocation-address','id'=>'geolocation-address','class'=>'textinput', "style" => "margin-bottom:0; width:45%")); ?>
                    <?php echo $this->formText('geolocation-radius', 10, array('name'=>'geolocation-radius','size' => '10','id'=>'geolocation-radius','class'=>'textinput', "style" => "margin-bottom:0; width: 55px;")); ?>
                </td>
            </tr>
            
            <?php if(is_allowed('Users', 'browse')): ?>
            <tr>
            <td><strong><?php echo $this->formLabel('user-search', __('Search By User'));?></strong></td>
                <td></td>
                <td>
                <?php
                    echo $this->formSelect(
                        'owner_id',
                        '',
                        array('id' => 'owner_id',
                                "class" => "facet-search-value",
                                "style" => "margin-bottom:0; width:50%"),
                        get_table_options('User')
                    );
                ?>
                </td>
            </tr>
            <?php endif; ?>
            
        </table>
        <input id="search-form-submit" type="submit" value="<?php echo __("Search"); ?>" />
    </form>
</div>

<?php echo foot();
