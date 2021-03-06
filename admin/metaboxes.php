<?php
if( !defined('ABSPATH') ){
	exit;
}	

class Curatescape_Meta_Box {
		
	public function __construct($id, $post_type, $metabox_title, $fields, $appendFile) {
		
		$this->id = $id;
		$this->post_type = $post_type;
		$this->fields = $fields;
		$this->metabox_title = $metabox_title;
		$this->appendFile = $appendFile ? $appendFile : false;

		add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
		add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );

	}
	

	public function init_metabox() {

		add_action( 'add_meta_boxes', array( $this, 'add_metabox'  )        );
		add_action( 'save_post',      array( $this, 'save_metabox' ), 10, 2 );

	}

	public function add_metabox() {
		
		add_meta_box(
			$this->id,
			$this->metabox_title,
			array( $this, 'render_metabox' ),
			$this->post_type,
			'normal',
			'high'
		);

	}

	public function render_metabox( $post ) {

		// Add nonce for security and authentication.
		$nonce_action = $this->id.'_nonce_action';
		$nonce_name = $this->id.'_nonce';
		wp_nonce_field( $nonce_action, $nonce_name );
		
		$html = null;
		foreach($this->fields as $field){
			
			$value = get_post_meta( $post->ID, $field['name'], true );
			if( empty( $value ) ) $value = '';
			
			$repeatable=$field['repeatable'];
			$repeatable_button=( $repeatable > 0 ) ? '<div class="repeatable_button"><span class="dashicons dashicons-plus-alt"></span>Add</div>' : null;
			
			$ui_class= $field['custom_ui'] ? 'hidden custom_ui' : null;
			
			$html .= '<tr id="'.$field['name'].'_row" class="'.$ui_class.'">';
			switch ($field['type']) {
				
				/* TEXT - repeatable */
			    case 'text':
				    if($repeatable){	    			    
					    $input=null;
					    for($i=0; $i<=$repeatable; $i++){
						    $user_value=isset($value[$i]) ? $value[$i] : null;
						    $visibility = (!$user_value && !$i==0) ? 'hidden' : 'visible'; // only show first field and fields with data
						    $input .= '<div class="'.$field['name'].'_container'.' '.$visibility.'">';
						    $input .= '<input type="text" id="'.$field['name'].'['.$i.']'.'" name="'.$field['name'].'['.$i.']'.'" class="'.$field['name'].'_field_'.$i.'" placeholder="" value="' .$user_value. '">';
						    $input .= '</div>';
					    }
				    }else{
					    $input = '<input type="text" id="'.$field['name'].'" name="'.$field['name'].'" class="'.$field['name'].'_field" placeholder="" value="' . $value. '">';
				    }
			        $html .= '<th>'.
			        '<label for="'.$field['name'].'" class="'.$field['name'].'_label">'.$field['label'].'</label>'.
			        '</th><td>'.$input.$repeatable_button.'<br><span class="description">'.$field['helper'].'</span></td>';
			        continue 2;
			        
			    /* TEXTAREA - repeatable */    
			    case 'textarea':
				    if($repeatable){	    			    
					    $input=null;
					    for($i=0; $i<=$repeatable; $i++){
						    $user_value=isset($value[$i]) ? $value[$i] : null;
						    $visibility = (!$user_value && !$i==0) ? 'hidden' : 'visible'; // only show first field and fields with data
						    $input .= '<div class="'.$field['name'].'_container'.' '.$visibility.'">';
						    $input .= '<textarea id="'.$field['name'].'['.$i.']'.'" name="'.$field['name'].'['.$i.']'.'" class="'.$field['name'].'_field_'.$i.'"'. 
			        	'placeholder="">'.$user_value.'</textarea>';
			        		$input .= '</div>';
					    }
				    }else{
					    $input = '<textarea id="'.$field['name'].'" name="'.$field['name'].'" class="'.$field['name'].'_field"'. 
			        	'placeholder="">'.$value.'</textarea>';
				    }			    
			        $html .= '<th>'.
			        '<label for="'.$field['name'].'" class="'.$field['name'].'_label">'.$field['label'].'</label>'.
			        '</th><td>'.$input.$repeatable_button.'<br><span class="description">'.$field['helper'].'</span></td>';
			        continue 2;
			    
			    /* SELECT */    
			    case 'select':
			    	$options = $field['options'];
			    	if( count($options) > 0 ){
				    	$options_html=null;
				    	foreach($options as $option){
					    	$options_html .= '<option value="'.$option['name'].'" ' . selected( $value, $option['name'], false ) . '> '.$option['label'].'</option>';
				    	}
				        $html .= '<th>'.
				        '<label for="'.$field['name'].'" class="'.$field['name'].'_label">'.$field['label'].'</label>'.
				        '</th><td>'.
				        '<select id="'.$field['name'].'" name="'.$field['name'].'" class="'.$field['name'].'_field">'.$options_html.'</select><br><span class="description">'.$field['helper'].'</span></td>';			    	
			    	}
			        continue 2;
			    
			    /* CHECKBOX */
			    case 'checkbox':
			        $html .= '<th>'.
			        '<label for="'.$field['name'].'" class="'.$field['name'].'_label">'.$field['label'].'</label>'.
			        '</th><td>'.
			        '<input type="checkbox" id="'.$field['name'].'" name="'.$field['name'].'" class="'.$field['name'].'_field"'.
			        	'value="' . $value . '" ' . checked( $value, 'checked', false ) . '>'.
			        	'<span class="description">'.$field['helper'].'</span></td>';
			        continue 2;
			        
			}
			$html .= '</tr>';

		}
		
		// Form fields.
		echo '<table class="form-table">'.$html.'</table>';
		
		// Include external file for any fields requiring a custom UI
		if($this->appendFile){
			include $this->appendFile;
		}

	}

	public function save_metabox( $post_id, $post ) {

		// Nonce for security and authentication
		$nonce_name   = isset($_POST[$this->id.'_nonce']) ? $_POST[$this->id.'_nonce'] : null;
		$nonce_action = $this->id.'_nonce_action';

		// Nonce is set
		if ( ! isset( $nonce_name ) )
			return;

		// Nonce is valid
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) )
			return;

		// User has permissions 
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		// Not an autosave
		if ( wp_is_post_autosave( $post_id ) )
			return;

		// Not a revision
		if ( wp_is_post_revision( $post_id ) )
			return;
			
		// Check if there was a multisite switch before
		if ( is_multisite() && ms_is_switched() )
			return;


		// Sanitize user input and update database		
		foreach($this->fields as $field){
			$repeatable=$field['repeatable'];
			switch ($field['type']) {
				
			    case 'text':
			    if($repeatable){
				    $new=array();
				    for($i=0; $i<=$repeatable; $i++){
					    $arr=$_POST[$field['name']];
					    $new[$i] = $arr[$i] ? sanitize_text_field( $arr[$i]  ) : '';
				    }
				    $new=array_values(array_filter($new)); // reset array indexes to ignore empty values
				    update_post_meta( $post_id, $field['name'], $new );	
			    }else{
				    $new = isset( $_POST[ $field['name'] ] ) ? sanitize_text_field( $_POST[ $field['name'] ] ) : '';
				    update_post_meta( $post_id, $field['name'], $new );				    
			    }
			    continue 2;
			    				
			    case 'textarea':
			    if($repeatable){
				    $new=array();
				    for($i=0; $i<=$repeatable; $i++){
					    $arr=$_POST[$field['name']];
					    $new[$i] = $arr[$i] ? sanitize_text_field( $arr[$i]  ) : '';
				    }
				    $new=array_values(array_filter($new)); // reset array indexes to ignore empty values
				    update_post_meta( $post_id, $field['name'], $new );					    
			    }else{
				    $new = isset( $_POST[ $field['name'] ] ) ? sanitize_text_field( $_POST[ $field['name'] ] ) : '';
				    update_post_meta( $post_id, $field['name'], $new );				    
			    }
			    continue 2;

			    case 'select':
			    $new = isset( $_POST[ $field['name'] ] ) ? $_POST[ $field['name'] ] : '';
			    update_post_meta( $post_id, $field['name'], $new );
			    continue 2;

			    case 'checkbox':
			    $new = isset( $_POST[ $field['name'] ] ) ? 'checked' : '';
			    update_post_meta( $post_id, $field['name'], $new );
			    continue 2;			    			    
			}

		}
	}

}	
	
	 
// Init metaboxes
if(is_admin()){
	
	new Curatescape_Meta_Box('tour_details',
		'tours', 
		__('Tour Details','wp_curatescape'),
		array(
			array(
				'label'		=> __('Postscript Text','wp_curatescape'),
				'name'		=> 'tour_postscript',
				'type'		=> 'textarea',
				'options'	=> null,
				'custom_ui'	=> false,
				'helper'	=> __('Add postscript text to the end of the tour, for example, to thank a sponsor or add directional information.','wp_curatescape'),
				'repeatable'=> 0,
				),	
		),null
	);
	
	new Curatescape_Meta_Box('tour_locations',
		'tours',
		__('Tour Locations','wp_curatescape'),
		array(
			array(
				'label'		=> __('Stories for this Tour','wp_curatescape'),
				'name'		=> 'tour_locations',
				'type'		=> 'text',
				'options'	=> null,
				'custom_ui'	=> false, // this hidden form field will save Story post IDs as an ordered array
				'helper'	=> __('Choose locations for this tour. You can <a href="/wp-admin/edit.php?post_type=stories">add and edit Story posts here</a>.','wp_curatescape'),
				'repeatable'=> 0,
				),					
		),'custom_ui/tour_items.php'
	);	

	new Curatescape_Meta_Box('story_story_header',
		'stories',
		__('Story Header','wp_curatescape'),
		array(
			array(
				'label'		=> __('Subtitle','wp_curatescape'),
				'name'		=> 'story_subtitle',
				'type'		=> 'text',
				'options'	=> null,
				'custom_ui'	=> false,
				'helper'	=> __('Enter a subtitle for the tour.','wp_curatescape'),
				'repeatable'=> 0,
				),
			array(
				'label'		=> __('Lede','wp_curatescape'),
				'name'		=> 'story_lede',
				'type'		=> 'textarea',
				'options'	=> null,
				'custom_ui'	=> false,
				'helper'	=> __('A brief introductory section that is intended to entice the reader to read the full entry.','wp_curatescape'),
				'repeatable'=> 0,
				),						
		), null
	);	

	new Curatescape_Meta_Box('story_media',
		'stories',
		__('Media Files','wp_curatescape'),
		array(
			array(
				'label'		=> __('Choose Media','wp_curatescape'),
				'name'		=> 'story_media',
				'type'		=> 'text',
				'options'	=> null,
				'custom_ui'	=> false,
				'helper'	=> __('Select files from the Media Library and/or upload new files. These files will be used to create structured display areas for each media type, including images, audio, and video. Drag and drop to change the order of files.','wp_curatescape'),
				'repeatable'=> 0,
				)
		), 'custom_ui/story_media.php'
	);	
		
	new Curatescape_Meta_Box('story_location_details',
		'stories',
		__('Location Details','wp_curatescape'),
		array(
			array(
				'label'		=> __('Street Address','wp_curatescape'),
				'name'		=> 'story_street_address',
				'type'		=> 'text',
				'options'	=> null,
				'custom_ui'	=> false,
				'helper'	=> __('A detailed street/mailing address for a physical location.','wp_curatescape'),
				'repeatable'=> 0,
				),
			array(
				'label'		=> __('Access Information','wp_curatescape'),
				'name'		=> 'story_access_information',
				'type'		=> 'textarea',
				'options'	=> null,
				'custom_ui'	=> false,
				'helper'	=> __('Information regarding physical access to a location, including restrictions (e.g. "Private Property"), walking directions (e.g. "To reach the peak, take the trail on the left"), or other useful details (e.g. "Location is approximate").','wp_curatescape'),
				'repeatable'=> 0,
				),
			array(
				'label'		=> __('Official Website','wp_curatescape'),
				'name'		=> 'story_official_website',
				'type'		=> 'text',
				'options'	=> null,
				'custom_ui'	=> false,
				'helper'	=> __('An official website related to the entry. Use <a href="https://guides.github.com/features/mastering-markdown/" target="_blank">markdown</a> to create an active link, e.g. to link to Google use <pre>[google](https://google.com)</pre>.','wp_curatescape'),
				'repeatable'=> 0,
				),				
			array(
				'label'		=> __('Map Coordinates','wp_curatescape'),
				'name'		=> 'location_coordinates',
				'type'		=> 'text',
				'options'	=> null,
				'custom_ui'	=> true, // this hidden form field will save coordinates as a JSON array
				'helper'	=> __('Use the map to add geo-coordinates for this location as a bracketed array, e.g. <pre>[41.503240,-81.675249]</pre>','wp_curatescape'),
				'repeatable'=> 0,
				),
			array(
				'label'		=> __('Map Zoom','wp_curatescape'),
				'name'		=> 'location_zoom',
				'type'		=> 'text',
				'options'	=> null,
				'custom_ui'	=> true, // this hidden form field will save zoom level automatically
				'helper'	=> __('Use the map to add the default zoom level for this location as a single integer between 1 and 20.','wp_curatescape'),
				'repeatable'=> 0,
				)													
		), 'custom_ui/story_location_details.php'
	);

	new Curatescape_Meta_Box('story_related_resources',
		'stories',
		__('Related Resources','wp_curatescape'),
		array(
			array(
				'label'		=> __('Related Resources','wp_curatescape'),
				'name'		=> 'story_related_resources',
				'type'		=> 'textarea',
				'options'	=> null,
				'custom_ui'	=> false,
				'helper'	=> __('The name of or link to a related resource, often used for citation information. Use <a href="https://guides.github.com/features/mastering-markdown/" target="_blank">markdown</a> to add formatting as needed.','wp_curatescape'),
				'repeatable'=> 16,
				)
		), null
	);		
								
}