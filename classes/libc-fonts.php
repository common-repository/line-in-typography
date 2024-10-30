<?php
/**
 *	Line In Settings API base class.
 *
 *	Here Be Dragons! Other than the Prefix, don't change this unless you know what you're doing!
 *
 *	@version 0.6.2
 */
if ( !class_exists('LIT_LIBC_Fonts' ) ) { 
	class LIT_LIBC_Fonts {
		private $_font_list	= array();
		private $_font_styles = array();
		private $_font_sizes = array();
		
		public function __construct( $use_system = true ) {
			
			if ( $use_system ) 
				$this->_set_system_fonts();
			$this->_set_sizes();
			$this->_set_styles();
		}

		private function _set_sizes() {
			$this->_font_sizes = array( 
				'11',
				'12',
				'13',
				'14',
				'16',
				'18',
				'21',
				'24',
				'28',
				'32',
				'36',
				'42',
				'48'
			);
			
		}
		
		private function _set_styles() {
			$this->_font_styles = array(
				'n' => "Normal",
				'b' => "Bold",
				'i' => "Italic",
				'bi' => "Bold/Italic"
			);
			
		}

		private function _set_system_fonts() {
			$this->_font_list['arial'] = array(
				'name' 		=> "Arial",
				'filename' 	=> 'system',
				'type'		=> 'sans-serif'
			);
			$this->_font_list['verdana'] = array(
				'name' 		=> "Verdana",
				'filename' 	=> 'system',
				'type'		=> 'sans-serif'
			);
			
			$this->_font_list['georgia'] = array(
				'name' 		=> "Georgia",
				'filename' 	=> 'system',
				'type'		=> 'serif'
			);
			$this->_font_list['helvetica'] = array(
				'name' 		=> "Helvetica",
				'filename' 	=> 'system',
				'type'		=> 'sans-serif'
			);
			$this->_font_list['trebuchet'] = array(
				'name' 		=> "Trebuchet",
				'filename' 	=> 'system',
				'type'		=> 'sans-serif'
			);
			$this->_font_list['times'] = array(
				'name' 		=> "Times New Roman",
				'filename' 	=> 'system',
				'type'		=> 'serif'
			);
			$this->_font_list['tahoma'] = array(
				'name' 		=> "Tahoma",
				'filename' 	=> 'system',
				'type'		=> 'sans-serif'
			);
			$this->_font_list['palatino'] = array(
				'name' 		=> "Palatino",
				'filename' 	=> 'system',
				'type'		=> 'serif'
			);
			$this->_font_list['impact'] = array(
				'name' 		=> "Impact",
				'filename' 	=> 'system',
				'type'		=> 'sans-serif'
			);
			$this->_font_list['courier'] = array(
				'name' 		=> "Courier",
				'filename' 	=> 'system',
				'type'		=> 'monospace'
			);
			$this->_font_list['palatino'] = array(
				'name' 		=> "Palatino",
				'filename' 	=> 'system',
				'type'		=> 'serif',
				'list'		=> '"Palatino Linotype", "Georgia"'
			);
									
			
		}
		
		/**
		 *	Filename should be relative to the plugin dir
		 *	
		 *	@param 	string			$slug		url friendly slug (no spaces)
		 *	@param	string			$name		The name of the font To be used in the CSS
		 *	@param	string			$filename	The url relative to the plugin directory
		 *	@param	string			$type		The type of font (serif|sans-serif|monospace)
		 *	@param	string|array	$style		A single style or a set of styles
		 */
		public function add_font( $slug, $name, $filename, $type = 'serif', $style = 'regular' ) {
			$new_array[$slug] = array(
				'name'		=> $name . ' *',
				'family'	=> str_replace( ' ', '', $name ),
				'filename'	=> $filename,
				'type'		=> $type,
				'style'		=> $style
			);
			
			$current_list = $this->_font_list;
			
			$this->_font_list = array_merge($new_array,$current_list);	
		
		}
		
		public function get_font_list() {
			return $this->_font_list;
		}
		
		public function get_font_sizes() {
			return $this->_font_sizes;
		}
		
		public function get_font_styles() {
			return $this->_font_styles;
		}
		
		public function list_fonts( $id, $name, $selected ) {
		
			?>
			<select name='<?php echo $name; ?>[slug]' id='<?php echo $id; ?>-slug'>
				<?php foreach ( $this->get_font_list() as $slug => $details ) { ?>
					<option value="<?php echo $slug; ?>"
					<?php if ( $selected['slug'] == $slug ) { 
						echo " selected='selected'";
					
					} ?>
					
					><?php echo $details['name']; ?></option>
				
				<?php } ?>
			</select>
			
			<select name='<?php echo $name; ?>[style]' id='<?php echo $id; ?>-style'>
				<?php foreach ( $this->get_font_styles() as $slug => $details ) { ?>
					<option value="<?php echo $slug; ?>"
					<?php if ( $selected['style'] == $slug ) { 
						echo " selected='selected'";
					
					} ?>
					
					><?php echo $details; ?></option>
				
				<?php } ?>
			</select>
						
			<select name='<?php echo $name; ?>[size]' id='<?php echo $id; ?>-size'>
				<?php foreach ( $this->get_font_sizes() as  $size ) { ?>
					<option value="<?php echo $size; ?>"
					<?php if ( $selected['size'] == $size ) { 
						echo " selected='selected'";
					
					} ?>
					
					><?php echo $size; ?></option>
				
				<?php } ?>
			</select>
						
			<?php
		}
		
		public function get_font_info( $slug ) {

			return $this->_font_list[$slug];
		}	
		
		/**
		 *	This encodes the fonts to be printed out into the stylesheets on browsers that support it.
		 *
		 *	It would be good if I could do a check to make sure that file_get_contents() worked and then fall back
		 *	to just delivering the font file if it didn't. That should be added to the to-do list.
		 
		private function _encode( $file, $mime ) {
			$contents = file_get_contents($file);
			if ( $contents ) {
				$base64 = base64_encode($contents);
			} else {
				$base64 = false;	
			}
			return ($base64);
		}
		
		*/
		/**
		 *	
		 */
		private function _display_font_face( $slug ) {
			$output = false;
			if ( isset( $this->_font_list[$slug] ) 
				&& $this->_font_list[$slug]['filename'] != 'system' && !isset( $this->_loaded[$slug] ) ) {
				$this->_loaded[$slug] = true;
			
				$styles = array();
				// Firstly, we check to see what styles are loaded
				if ( is_array( $this->_font_list[$slug]['style'] ) ) { 
					$styles = $this->_font_list[$slug]['style'];
				}
				$styles[] = 'regular';
			
				foreach ( $styles as $style ) {
					$style_append = false;
					if ( $style != 'regular' ) {
						$style_append = '-' . $style;
					}
				
					$font_weight = 'normal';
					$font_style = 'normal';
					if ( $style == 'bold' || $style == 'bolditalic' ) {
						$font_weight = 'bold';
					}
					if ( $style == 'italic' || $style == 'bolditalic' ) {
						$font_style = 'italic';
					}
					/*src: url('<?php echo $this->_font_list[$slug]['filename']; ?>.eot');
					src: url('<?php echo $this->_font_list[$slug]['filename']; ?>.eot?#iefix') format('embedded-opentype'),
					     url('<?php echo $this->_font_list[$slug]['filename']; ?>.woff') format('woff'),
					     url('<?php echo $this->_font_list[$slug]['filename']; ?>.ttf') format('truetype'),
					     url('<?php echo $this->_font_list[$slug]['filename']; ?>.svg#<?php echo $this->_font_list[$slug]['family']; ?>') format('svg'); */


					// Let's see if we can encode and, if not, fall back to the files themselves
					// $woff = $this->_encode( $this->_font_list[$slug]['filename'] . $style_append .'.woff' );
					$woff = false;
					
					if ( $woff ) {
						$woff = "url('data: font/woff;charset=utf-8;base64," . $woff . "') format('woff')";
					} else {
						$woff = "url('" . $this->_font_list[$slug]['filename'] . $style_append . ".woff') format('woff')";
					}
					
					// $ttf = $this->_encode( $this->_font_list[$slug]['filename'] . $style_append .'.ttf' );
					
					$ttf = false;
					if ( $ttf ) {
						$ttf = "url('data: font/ttf;charset=utf-8;base64," . $ttf . "') format('ttf')";
					} else {
						$ttf = "url('" . $this->_font_list[$slug]['filename'] . $style_append . ".ttf') format('truetype')";
					}

					/*
					@font-face {
					    font-family: '<?php echo $this->_font_list[$slug]['family']; ?>';
					    src: url('<?php echo $this->_font_list[$slug]['filename']; ?><?php echo $style_append; ?>.eot');
					    font-weight: <?php echo $font_weight; ?>;
					    font-style: <?php echo $font_style; ?>;
					} */
					
					// For IE, we'll always need the files
					$family = $this->_font_list[$slug]['family'];
					$filename = $this->_font_list[$slug]['filename'];

					$output .= <<<EOD
				
/** 
 *	This CSS is generated automatically, so please see license.txt in the theme folder 
 *	for more information about these fonts.
 */				
@font-face {
	font-family: '$family';
	src: url('$filename{$style_append}.eot');
	src: url('$filename{$style_append}.eot?#iefix') format('embedded-opentype'),
	     $woff,
	     $ttf,
	     url('$filename{$style_append}.svg#$family') format('svg');
	font-weight: $font_weight;
	font-style: $font_style;
}	
EOD;
				}
			}
			return $output;
		}
		
		
		
		public function get_font_face_css( $slug ) {
			$results = false;
			// If it's an array of families, check each to see if we need to load the @font-face rules
			if ( is_array( $slug ) ) {
				foreach ( $slug as $font ) {
					$results .= $this->_display_font_face( $font );
				}
			} else {
				$this->_display_font_face( $slug );
				
			}
			return $results;
		}
		
		public function get_family_css( $slug ) {
			if ( isset( $this->_font_list[$slug] ) ) {
				if ( $this->_font_list[$slug]['filename'] == 'system' ) {
					$family = $this->_font_list[$slug]['name'];
				} else {
					$family = $this->_font_list[$slug]['family'];
				}
			
				$return = "'" . $family . "', ";
				if ( isset ( $this->_font_list[$slug]['list'] ) ) {
					$return .= $this->_font_list[$slug]['list'] . ", ";
				}

				$return .=  $this->_font_list[$slug]['type'];
				return $return;
			} else {
				return "sans-serif";
			}
		}
		
		
		public function validate_fonts( $input ) {
			
			if ( isset( $this->_font_list[$input['slug']] ) && isset( $this->_font_styles[$input['style']] ) && in_array($input['size'], $this->_font_sizes) ) {
				return true;
			} else {
				return false;
			}
		
		}

	
	}
}

?>