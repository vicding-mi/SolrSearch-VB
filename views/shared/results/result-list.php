<?php foreach ($results->response->docs as $doc): ?>

  <!-- Document. -->
  <div class="result">

    <!-- Header. -->
    <div class="result-header">

      <!-- Record URL. -->
      <?php $url = SolrSearch_Helpers_View::getDocumentUrl($doc); ?>

      <!-- Title. -->
      <ul style="margin-bottom:5px">
      <a href="<?php echo $url; ?>" class="result-title"><?php
              $title = is_array($doc->title) ? $doc->title[0] : $doc->title;
              if (empty($title)) {
                  $title = '<i>' . __('Untitled') . '</i>';
              }
              echo $title;
          ?></a>
      </ul>
    </div>
    
    <!-- Metadata. -->
    <div class="result-meta">
    	<span class="result-meta-left" style="font-style: italic">
            <?php if ($subgenre = $doc->subgenre): ?>
                <?php echo $subgenre; ?>
            <?php endif; ?>
        </span>
    	<span class="result-meta-right">
    		<?php
		
    			$date_field = is_array($doc->date_s) ? $doc->date_s[0] : $doc->date_s;	//40_s
    			$dates = explode(' ', $date_field);
									
    			$time = strtotime($dates[0]);
    			$year = date('Y', $time);
    			echo $year;
	
    		?>
    	</span>
    </div>
    
    <!-- Highlighting. -->
    <?php $highlighted = false; ?>
    <?php if (get_option('solr_search_hl')): ?>
      <ul class="hl" style="margin-left:0px">
        <?php foreach($results->highlighting->{$doc->id} as $field): ?>
          <?php foreach($field as $hl): ?>
            <?php $highlighted = true;?>
            <li class="snippet"><?php echo strip_tags($hl, '<em>'); ?></li>
          <?php endforeach; ?>
        <?php endforeach; ?>
        
        <!-- Snippet if no highlighting. -->
        <?php if (!$highlighted):?>
            <li class="snippet"><?php echo snippet($doc->description, 0, 200); ?></li>
        <?php endif; ?>
        
      </ul>
    <?php endif; ?>

    <!-- Tags. -->
    <?php if ($doc->tag && is_array($doc->tag)): ?>
        <div class="tags"><p><strong><?php echo __('Tags'); ?>:</strong>
        <?php $taglist = array(); ?>
        <?php foreach ($doc->tag as $key => $value):
            $url = SolrSearch_Helpers_Facet::addFacet("tag", $value);
            $taglist[] = '<a href="' . $url . '" class="facet-value">' . $value . '</a>'; ?>
        <?php endforeach; ?>
        <?php echo join(", ", $taglist); ?>
        </div>
    <?php endif; ?>
    <?php if (!is_array($doc->tag)): 
        $url = SolrSearch_Helpers_Facet::addFacet("tag", $doc->tag);?>
        <div class="tags"><p><strong><?php echo __('Tags'); ?>:</strong>
        <a href="<?php echo $url; ?>" class="facet-value"><?php echo $doc->tag; ?></a>
    <?php endif; ?>
    

    <?php
      $item = get_db()->getTable($doc->model)->find($doc->modelid);
      echo item_image_gallery(
          array('wrapper' => null,
            'linkWrapper' => array('class' => 'admin-thumb panel',
                                    'style' => 'display:inline; margin:10px'),
            'link' => array('class' => 'link'),
            'image' => array('class' => 'image')),
          'square_thumbnail',
          true,
          $item
      );
    ?>

  </div>
  
  <hr>

<?php endforeach; ?>