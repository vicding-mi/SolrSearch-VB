
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 cc=80; */

/**
 * @package     omeka
 * @subpackage  solr-search
 * @copyright   2012 Rector and Board of Visitors, University of Virginia
 * @license     http://www.apache.org/licenses/LICENSE-2.0.html
 */

jQuery(function($) {

  $('.facet-label').each(function() {
    $(this).textinplace({
      form_name: $(this).attr('data-form-name'),
      revert_to: $(this).attr('data-revert-to')
    });
  });

});
