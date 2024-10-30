<?php
/**
 *	Line In Settings API base class.
 *
 *	Here Be Dragons! Other than the Prefix, don't change this unless you know what you're doing!
 *
 *	@version 0.6.0
 */
if ( !class_exists('LIT_LI_Settings_Base') ) {
	abstract class LIT_LI_Settings_Base implements LIT_Constants  {
		static		$reset = false;
		private		$_fonts = false;
		private		$_display_count = 0;
		/**
		 * Current group count for multiple settings groups (not currently used)
		 * @access  private
		 * @var 	int
		 */	
		private		$_group_count = 0;
		/**
		 * Keeps track of the currently selected admin display type
		 * @access  private
		 * @var 	string
		 */	
		private		$_type = 'meta'; // meta, tabs or none
		/**
		 * Array of allowed meta positions
		 * @access  private
		 * @var 	array
		 */	
		private		$_allowed_positions = array( 'normal', 'advanced', 'side' );
		/**
		 * Array of allowed display types using the standard WordPress admin display (meta|tabs|none)
		 * @access  private
		 * @var 	array
		 */	
		private		$_allowed_types = array( 'meta', 'tabs', 'none' );
		/**
		 * Array of options retreived from the database. Only accessible through getters and setters.
		 * @access  private
		 * @var 	array
		 */	
		private		$_options;
		
		private		$_sections;
		
		private		$_buttons;
		
		private		$_current_section = 0;
		private		$_tiny_mce;
		private		$_media_uploader = false;

		/**
		 * The overall plugin id. Set by the constants file.
		 * @access  protected
		 * @var 	string
		 */	
		protected	$plugin_id;
		/**
		 * The language id of the plugin. Set by the constants file
		 * @access  protected
		 * @var 	string
		 */	
		protected	$lang;
		/**
		 * An array options groups (currently limited to 1).
		 * @access  protected
		 * @var 	array
		 */	
		protected	$options_group;
		/**
		 * An array of tags NOT to be filtered by wp_kses. Used to allow <p> tags on tiny_mce input fields.
		 * @access  protected
		 * @var 	array
		 */	
		protected	$allowedtags;
		
		// When dealing with multi-page plugins
		protected	$default_page 		= false;
		protected	$default_section 	= 'default';

		

		
		public function __construct( $group = false, $section = false ) {
			$this->_plugin_id = self::PLUGINID;
			if ( isset( $this->type ) && in_array($this->type, $this->_allowed_types) ) {
				$this->set_type( $this->type );
			}
			
			// Need this because it sets up the name of the options group where our Options Settings are stored.
			// There's probably a better way of handling this.
			$this->_add_settings_group('validate');
			$this->default_group = ( $group ) ? $group : $this->plugin_id;
			$this->default_section = ( $section ) ? $section : 'default';
			// About the only thing we need here.
			add_action( 'widgets_init', array( &$this, 'options_init'), 5 );
			// If we're not in the admin panel, don't need any of this.
			if ( is_admin() ) {
				add_action( 'admin_init', array( &$this, 'load_settings'), 8 );
				add_action( 'admin_init', array( &$this, 'additional_settings'), 9 );
				add_action('admin_init', array( &$this, 'register_settings'), 8 );
				add_action('admin_head',  array( &$this,'load_tiny_mce'));	
			}
		}
		
		
		

				
		/**
		 *	Loop through the sections 
		 */
		public function has_sections() {
			if ( !isset( $this->registered_sections ) ) {
				global $wp_settings_sections;
				$i = 1;
				foreach ( $wp_settings_sections[$this->default_group] as $id => $section ) {
					$this->registered_sections[$i]['id'] = $id;
					$this->registered_sections[$i]['title'] = $section['title'];
					$i++;
				}
			}		
			$this->_display_count++;
			
			if ( isset( $this->registered_sections[$this->_display_count] ) ) {
				return true;
			} else {
				$this->_display_count = 0;
				return false;
			}
		}	


		public function get_box_title() {
			return $this->registered_sections[$this->_display_count]['title'];
		}
		public function get_box_id() {
			return $this->registered_sections[$this->_display_count]['id'];
		}
		
		protected function get_group() {
			return $this->default_group;
		}
		
	
		/**
		 * May be made protected in future. Right now, only supports 1 settings group per Settings Class
		 */
		private function _add_settings_group($callback) {

			$this->_group_count++;
			if ( !method_exists($this, $callback) ) {
				wp_die( $this->_self . " does not have a validate callback for this group. You have passed a method '$callback' that you have not defined in your extending settings class." );
			}	

			// Use the extending class name for the group name. Create an array to allow multiple register_settings call
			// on the same page.
			$this->options_group[] = array(
				//	'group' => $this->self . '-group-' . $this->_group_count,
				// Plugin ID + extending class name for the db options
				
				'options_name' => strtolower( $this->_plugin_id . '-' . $this->self),
				'callback' => $callback
			);

		}		
			
		protected function add_section( $section_name, $intro_callback, $meta_position = 'normal' ) {
			if ( in_array($meta_position, $this->_allowed_positions) ) {
				$position = $meta_position;
			} else {
				$position = 'normal';
			}
			if ( !method_exists($this, $intro_callback) ) {
				wp_die("You passed a callback to the " . $this->self . "->add_section() method that doesn't exist in your extending class " . $this->self . ". You'll need to define this callback method and it needs to be set to public.");
			}
			$this->_current_section++;
			// This should load in all of the sections and their associated fields.
			// The title should be used for the meta box title as well as the section title
			// The intro callback can also be used for the meta box id.
			$id = sanitize_title_with_dashes( $section_name );
			
			$this->_sections[$this->_current_section] = array(
				'id'		=> $id,
				'callback'	=> $intro_callback,
				'title' 	=> $section_name,
				'position' 	=> $position
			);


		}
		protected function add_setting( $id , $title = false, $label = true ) {

			if ( $label ) {
				$label =  $id;
			} else {
				$label = false;		
			}
			
			$this->_settings[$this->_current_section][] = array(
				'id' => $id,
				'callback' => $id,
				'title' => $title, 
				'label' => $label
			);
		}
		
		
		/**
		 *	Initiate the API
		 */
		
		public function register_settings() {
			

			register_setting( 
				$this->default_group,
				$this->options_group[0]['options_name'],
				array( &$this, $this->options_group[0]['callback'] )
			);
			$this->register_sections();
		}
		public function register_sections() {

			// If it's not an array, no sections have been defined.
			// Use default
			if ( !is_array( $this->_sections ) ) {
				$this->register_fields( $this->default_section, 0 );
				return;
			}
			$i = 1;
			foreach ( $this->_sections as $key => $section ) {
				$page = $this->default_group;

				add_settings_section(
					$section['id'],					
					$section['title'],
					array( &$this, $section['callback']),
					$this->default_group
				);
				$this->register_fields( $section['id'], $key );
				$i++;
			}
		}
		public function register_fields( $section_id, $key, $existing_page = false) {
			if ( $existing_page ) {
				$page = $this->default_page;
			} else {
				$page = $section_id;		
			}
			
			if ( isset( $this->_settings[$key] ) ) {
				foreach( $this->_settings[$key] as $setting ) {
					$label = false;
					if ( $setting['label'] ) {
						$label = array( 'label_for' =>  $this->get_id( $setting['label'] ) );
					}
					
					add_settings_field( 
						$setting['id'], 
						$setting['title'], 
						array( $this, $setting['callback']),
						$this->default_group, 
						$section_id,
						$label
					);
				}
				
				
			}
		}
		
		
		protected function set_type( $type ) {
			if ( !in_array($type, $this->_allowed_types) ) {
				return false;
			} else {
				$this->_type = $type;
				return true;
			}
		}
		public function get_type() {
			return $this->_type;
		}
		
		
		public function set_group( $new_page = false ) {
			$this->default_group = $new_page;
		}
		
		public function options_init() {
			

			// Update the allowed tags to include the paragraph tas
			global $allowedtags;
			$allowedtags['p'] = array();
			$this->allowedtags = $allowedtags;

		    // set options equal to defaults
		    $this->_options = get_option( $this->options_group[0]['options_name'] );
		    if ( false === $this->_options ) {
				$this->_options = $this->get_defaults();
		    }
		    if ( isset( $_GET['undo'] ) && !isset( $_GET['settings-updated'] ) && is_array( $this->get_option('previous') ) ) {
		    	$this->_options = $this->get_option('previous');
		    }
			
		    update_option( $this->options_group[0]['options_name'], $this->_options );		
		   
		}
		
		public function get_name( $name = false ) {
			
			if ( $name ) {
				return $this->options_group[0]['options_name'] . "[$name]";
			} else {
				return $this->options_group[0]['options_name'];
			}
			
		}
				
		public function get_id( $field = false ) {
			if ( $field ) {
				return $this->options_group[0]['options_name'] . "-$field";
			} else {
				return $this->_plugin_id;
			}
		}
		public function get_option( $name = false ) {
			if ( $name ) {
				if ( isset( $this->_options[$name] ) ) {
					return $this->_options[$name];
				} else {

					return false;
				}
			} else {
				return $this->_options;
			}
		
		}
		
		protected function delete_option() {

			delete_option( $this->options_group[0]['options_name'] );	
		}
		
		public function get_settings_fields() {
			settings_fields( $this->default_group );
		}
		
		public function get_settings( $method ) {
			
			foreach ( $this->registered_sections as $section ) {
			
				if ( $section['id'] == $method ) {
					
					LIT_do_settings_section( $this->default_group, $section['id'] );
				}
			} 
			
			
		}
		
		
		protected function _add_tiny_mce( $element ) {
			$this->_tiny_mce[] = $this->get_id( $element );
		}
		
		public function load_tiny_mce() {
		
			if ( $this->_tiny_mce != null ) {

			
				$elements = implode(',', $this->_tiny_mce);
				if (function_exists('wp_tiny_mce')) {
				  add_filter(
				  	'teeny_mce_before_init', 
				  		create_function('$a', '
						    $a["theme"] = "advanced";
						    $a["skin"] = "wp_theme";
						    $a["height"] = "200";
						    $a["width"] = "600";
						    $a["onpageload"] = "";
						    $a["mode"] = "exact";
						    $a["elements"] = "' . $elements . '";
						    $a["editor_selector"] = "mceEditor";
						    $a["plugins"] = "safari,inlinepopups,spellchecker";
							$a["theme_advanced_buttons1"] = "bold, italic, blockquote, separator, strikethrough,  undo, redo, link, unlink";
						    $a["forced_root_block"] = false;
						    $a["force_br_newlines"] = true;
						    $a["force_p_newlines"] = true;
						    $a["convert_newlines_to_brs"] = true;
					    return $a;')
					);
				
				 // wp_tiny_mce(true);
				};

			}
		}
		
		public function add_button($type, $label, $id) {
			$this->_buttons[] = array(
				'type' 	=> $type,
				'label'	=> $label,
				'id'	=> $id
			);
		}
		
		public function get_buttons() {
			if ( $this->_buttons == null ) {
				submit_button('Update options', 'primary','submit', false); 
				submit_button('Reset options', 'secondary', 'reset', false); 
			} else {
				foreach ( $this->_buttons as $button ) {
					submit_button($button['label'], $button['type'], $button['id'], false);
				}
			}	
		}	
		
		public function update_cancel() {
			$this->add_button( 'primary', 'Update Options', $this->get_name('update') );
			$this->add_button( 'secondary', 'Cancel', $this->get_name('cancel') ); 
		}
		
		
		public function edit_delete($suffix = false) {
			
			
			echo "<input type='submit' value='Edit' class='button-secondary' name='" . $this->get_name('edit') . "[$suffix]' />";
			echo "<input type='submit' value='Delete' class='button-secondary' name='" . $this->get_name('delete') . "[$suffix]' />";
		}
		
		public function enqueue_thickbox() {
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox');
		}
		
		protected function set_media_uploader( ) {
			$this->_media_uploader = true;
			
		}
		
		protected function uploader_button($id) {
			
			?>
			<input id="btn-<?php echo $this->get_id( $id ); ?>" class='li-upload' type="submit" name="<?php echo $this->get_name( $id . '-button' ); ?>" value="Upload Image" />
			
			<?php
			
		}
		


		
		// This is run after all of the other initilisation. Good for actions or methods  that require
		// everything else to be set up first.
		public function additional_settings() {

			if ( $this->_media_uploader ) {
				$this->enqueue_thickbox();
			}
		}
		
		public function add_font_support( $fonts ) {
			
			if ( $fonts instanceof LIT_LIBC_Fonts ) {
				$this->_fonts = $fonts;
			} else {
				return false;
			} 
		}
		// FONT STUFF (this should one day be moved to its own class)
		protected function custom_fonts() {
			if ( !isset( $this->_fonts ) ) 
				$this->_fonts = new LIT_LIBC_Fonts();
		}
		
		protected function set_colorpicker() {
		
		}
		
		
		protected function fontlist( $id, $selected, $shadow = true ) {
			$this->_fonts->list_fonts( $this->get_id( $id ), $this->get_name( $id ), $selected );
			$this->_colorpicker_setup( $this->get_id( $id ) . '-color', $this->get_name( $id ) . '[color]', $selected['color'], false,  false );	
			

			// Possible support for text shadows?
			if ( $shadow ) :
			?>
			
			<br /> Text Shadow: <?php 
			$this->_colorpicker_setup( $this->get_id( $id ) . '-shadow', $this->get_name( $id ) . '[shadow]', $selected['shadow'], true,  false );	
			?> X:<input type='text' name="<?php echo $this->get_name( $id ); ?>[shadow][x]" id="<?php echo $this->get_id( $id ); ?>-shadow-x" value="<?php echo $selected['shadow']['x']; ?>" size='3' /> 
			Y:<input type='text' name="<?php echo $this->get_name( $id ); ?>[shadow][y]" id="<?php echo $this->get_id( $id ); ?>-shadow-y" value="<?php echo $selected['shadow']['y']; ?>" size='3' /> 
			Blur:<input type='text' name="<?php echo $this->get_name( $id ); ?>[shadow][blur]" id="<?php echo $this->get_id( $id ); ?>-shadow-blur" value="<?php echo $selected['shadow']['blur']; ?>" size='3' /> 
			
			<?php
			endif;
			
		}
		
		
		
		protected function validate_fonts( $input ) {
			
			if ( $this->_fonts->validate_fonts( $input ) ) {
				// The font exists and . Time to check the colour settings
				
				$output['slug'] = $input['slug'];
				$output['style'] = $input['style'];
				$output['size'] = $input['size'];

				if ( $this->validate_colours( $input['color'] ) ) {

					$output['color'] = $input['color'];
					if ( isset( $input['shadow'] ) ) {
						if ( $this->validate_colours( $input['shadow'] ) ) {
							$shadow['x']	= (int) $input['shadow']['x'];
							$shadow['y']	= (int) $input['shadow']['y'];
							$shadow['blur'] = ( (int) $input['shadow']['blur'] >= 0 ) ? $input['shadow']['blur'] : 0;
							
							// The colours are OK, so merge them with the checked shadow settings above
							$output['shadow'] = $input['shadow'];
							$output['shadow'] = array_merge($output['shadow'],$shadow);
						} else {
							return false;
						}
					} 
					

					return $output;	
								
				} else {
					return false;
				} 	
			

			} else {
				return false;
			}
		
		
		}
		
		
		/* Start colourpicker functions */
		
		private function _colorpicker_setup( $id, $name, $current, $transparency = true, $description = true) {
			if ( !$transparency )	
				$current['a'] = 100;
			?>
			 #<input type='text' value='<?php echo $current['hex']; ?>' size='7' class='colorpicker-hex' id='<?php echo $id; ?>-hex' name='<?php echo $name; ?>[hex]' /> 
			 <div id='<?php echo $id; ?>' class='colorpicker-select'>
			     <div style='background-color: #<?php echo $current['hex']; ?>; opacity: <?php echo $current['a'] / 100; ?>;'></div>
			 </div>
			 
			 <input type='hidden' value='<?php echo $current['r']; ?>' size='3' id='<?php echo $id; ?>-r' name='<?php echo $name; ?>[r]' /> 
			  <input type='hidden' value='<?php echo $current['g']; ?>' size='3' id='<?php echo $id; ?>-g' name='<?php echo $name; ?>[g]' /> 
			  <input type='hidden' value='<?php echo $current['b']; ?>' size='3' id='<?php echo $id; ?>-b' name='<?php echo $name; ?>[b]' /> 
			<?php if ( $transparency ) { ?>
			
			 Opacity: <input type='text' value='<?php echo $current['a']; ?>' size='3' class='colorpicker-a' id='<?php echo $id; ?>-a' name='<?php echo $name; ?>[a]' />%
			

			 <?php } ?>
			 
			 <?php if ( $description ) { ?>
			  <p class='description'>For opacity, 0 = fully transparent and 100 = fully opaque. Please note that the opacity setting may not be applied on some older browsers.</p>
			<?php } ?>
			
			
			<?php
				

		}
		
		protected function colorpicker( $id, $current = false, $description = true ) {
			if ( !$current ) {
				$current['hex'] = 'ffff00';
				$current['r'] = 255;
				$current['g'] = 255;
				$current['b'] = 0;
				$current['a'] = 100;
			}
			
			$this->_colorpicker_setup( $this->get_id( $id ), $this->get_name( $id ), $current, true, $description );
		
		}
		
		protected function validate_colours( $colours ) {
							
			// Check this is a valid hex code. If it's generated by the colourpicker, it will be	
			$validateHex = ( preg_match("/\#?[0-9A-F]{6}/i", $colours['hex']) ) ? $colours['hex'] : false ;
			if ( $validateHex ) {
				$output['hex'] = str_replace('#', '', $validateHex);
				// Check these are valid RGB values ( 0 - 255 );
				$output['r'] = ( (int) $colours['r'] >= 0 && (int) $colours['r'] <= 255 ) ? (int) $colours['r'] : false;
				$output['g'] = ( (int) $colours['g'] >= 0 && (int) $colours['g'] <= 255 ) ? (int) $colours['g'] : false;
				$output['b'] = ( (int) $colours['b'] >= 0 && (int) $colours['b'] <= 255 ) ? (int) $colours['b'] : false;
				
				// If they're not, return false
				if ( false === $output['r'] || false === $output['g'] || false === $output['b'] ) {
					return false;				
							
				} 
				$output['a'] = ( isset( $colours['a'] ) && (int) $colours['a'] >= 0 && (int) $colours['a'] <= 100  ) ? (int) $colours['a'] : 100;

				return $output;
				
				// If the alpha is set and is in range, use that otherwise use full opacity (100)
				
			} else {
				return false;
			}
		
		
		}
		
		
		/* End colourpicker functions */
		
		
		protected function check_image( $image, $width, $height, $file ) {
			$results = array();
			// 1) check remote file
			if ( li_check_remote_file($image) ) {
				$url = str_replace(' ', '%20', $image);
				
				$is_icon = stripos($image, '.ico');
				if ( $is_icon !== false ) {
					
					return true;
				}

				// Let's get the width and height
				$image_atts = getimagesize($url);
				
				if ( $image_atts ) {
					if ( 0 == (int) $width || '' == $width ) {
						$results['width'] = $image_atts[0];
					} else {
						$results['width'] = (int) $width;
					}
					
					if ( 0 == (int) $height || '' == $height ) {
						$results['height'] = $image_atts[1];
					} else {
						$results['height'] = (int) $height;
					}
					
					return $results;				
				} else {
					add_settings_error( $this->get_group(), $this->plugin_id . "_" . str_replace(' ', '-', strtolower($file)) . "_error", "The $file Image file you specified doesn't appear to be a valid image file. Please check and try again.", 'error');
				
					return $results['error'] = 'type';
				}
				
				
				
			} else {	
				add_settings_error( $this->get_group(), $this->plugin_id . "_{" .  str_replace(' ', '-', strtolower($file)) . "_error", "The $file Image file you specified doesn't appear to exist or is not accessible over the internet.", 'error');
			
				return $results['error'] = 'exists';
				
			}
	
		}
		
		protected function reset( $data ) {
			
 			if ( isset( $_POST['reset'] ) ) {
				if ( !self::$reset )
	 				add_settings_error( $this->get_group(), $this->plugin_id . "barf", "Settings reset to default", 'updated');
 				$this->delete_option();
 				$output = $this->get_defaults();
				self::$reset = true;

 				return $output;
 			} else {
 				return false;
 			}
		}
		
		protected function list_pages( $name, $page = false ) {
			wp_dropdown_pages( array('show_option_none' => '--SELECT PAGE--', 'selected' => $page, 'name' => $name ) );			
		}

		abstract function load_settings();
		abstract function get_defaults();
		abstract function validate( $data );
	}
}

if ( !function_exists('li_check_remote_file') ) {
	 function li_check_remote_file($url) {
	     $curl = curl_init($url);
	 
	     //don't fetch the actual page, you only want to check the connection is ok
	     curl_setopt($curl, CURLOPT_NOBODY, true);
	 
	     //do request
	     $result = curl_exec($curl);
	 
	     $ret = false;
	 
	     //if request did not fail
	     if ($result !== false) {
	         //if request was ok, check response code
	         $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
	 
	         if ($statusCode == 200) {
	             $ret = true;   
	         }
	     }
	 
	     curl_close($curl);
	 
	     return $ret;
	 }
}



if ( !function_exists('LIT_do_settings_section') ) {
	function LIT_do_settings_section($page, $section) {
		global $wp_settings_sections, $wp_settings_fields;
		
		if ( !isset($wp_settings_sections) || !isset($wp_settings_sections[$page]) )
			return;
		
		echo "<h3>{$wp_settings_sections[$page][$section]['title']}</h3>\n";		
		call_user_func($wp_settings_sections[$page][$section]['callback'], $wp_settings_sections[$page][$section]);
		if ( !isset($wp_settings_fields) || 
			!isset($wp_settings_fields[$page]) || 
			!isset($wp_settings_fields[$page][$wp_settings_sections[$page][$section]['id']]) )   
		return;
		echo '<table class="form-table">';
		do_settings_fields($page, $wp_settings_sections[$page][$section]['id']);
		echo '</table>';
	}
}

	

?>