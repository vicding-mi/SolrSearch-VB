jQuery(window).scroll(function()
{
    if(jQuery(window).scrollTop() == jQuery(document).height() - jQuery(window).height())
    {
        console.log("scrolled down");
        jQuery('div#loadmoreajaxloader').show();
        jQuery.ajax({
        url: resultList,
        success: function(html){
            console.log("ajaxed");
            if(html)
            {
                jQuery("#solr-results").append(html);
                jQuery('div#loadmoreajaxloader').hide();
            }else
            {
                jQuery('div#loadmoreajaxloader').html('<center>Einde van de lijst.</center>');
            }
        }
        });
    }
});