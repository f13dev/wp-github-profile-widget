<?php
/*
Plugin Name: GitHub Mini Profile Widget
Plugin URI: http://f13dev.com
Description: Add a mini version of your GitHub profile to a widget on a WordPress powered site.
Version: 1.0
Author: Jim Valentine - f13dev
Author URI: http://f13dev.com
Text Domain: github-mini-profile-widget
License: GPLv3
*/

/*
Copyright 2016 James Valentine - f13dev (jv@f13dev.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

/**
* Register the widget
*/
add_action('widgets_init', create_function('', 'return register_widget("GitHub_Mini_Profile_Widget");'));
// Register the css
add_action( 'wp_enqueue_scripts', 'gmpw_style');

// Add the stylesheet
function gmpw_style()
{
	$gmpw_stylesheet = plugin_dir_url( __FILE__ ) . 'github-profile-widget.css';
	echo $gmpw_stylesheet;
	wp_register_style( 'f13-gmpw-style', plugins_url('github-profile-widget.css', __FILE__) );
  wp_enqueue_style( 'f13-gmpw-style' );
}

// Widget class
class GitHub_Mini_Profile_Widget extends WP_Widget
{
	/** Basic Widget Settings */
	const WIDGET_NAME = "GitHub Mini Profile Widget";
	const WIDGET_DESCRIPTION = "Add a mini version of your GitHub profile to your website.";

	var $textdomain;
	var $fields;

	/**
	* Construct the widget
	*/
	function __construct()
	{
		$this->textdomain = strtolower(get_class($this));

		//Add fields
		$this->add_field('title', 'Enter title', '', 'text');
		$this->add_field('github_user', 'GitHub ID', '', 'text');
		$this->add_field('github_token', 'GitHub API Token', '', 'text');
		//Init the widget
		parent::__construct($this->textdomain, __(self::WIDGET_NAME, $this->textdomain), array( 'description' => __(self::WIDGET_DESCRIPTION, $this->textdomain), 'classname' => $this->textdomain));
	}

	/**
	* Widget frontend
	*
	* @param array $args
	* @param array $instance
	*/
	public function widget($args, $instance)
	{
		$title = apply_filters('widget_title', $instance['title']);

		echo $args['before_widget'];

		if (!empty($title))
		echo $args['before_title'] . $title . $args['after_title'];

		$this->widget_output($args, $instance);

		echo $args['after_widget'];
	}

	/**
	* Adds a text field to the widget
	*
	* @param $field_name
	* @param string $field_description
	* @param string $field_default_value
	* @param string $field_type
	*/
	private function add_field($field_name, $field_description = '', $field_default_value = '', $field_type = 'text')
	{
		if(!is_array($this->fields))
		$this->fields = array();

		$this->fields[$field_name] = array('name' => $field_name, 'description' => $field_description, 'default_value' => $field_default_value, 'type' => $field_type);
	}

	/**
	* Widget backend
	*
	* @param array $instance
	* @return string|void
	*/
	public function form( $instance )
	{
		/**
		* Create a header with basic instructions.
		*/
		?>
		<br/>
		Use this widget to add a mini version of your GitHub profile as a widget<br/>
		<br/>
		Get your access token from <a href="https://github.com/settings/tokens" target="_blank">https://github.com/settings/tokens</a>.<br/>
		<br/>
		<?php
		/* Generate admin form fields */
		foreach($this->fields as $field_name => $field_data)
		{
			if($field_data['type'] === 'text'):
				?>
				<p>
					<label for="<?php echo $this->get_field_id($field_name); ?>"><?php _e($field_data['description'], $this->textdomain ); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id($field_name); ?>" name="<?php echo $this->get_field_name($field_name); ?>" type="text" value="<?php echo esc_attr(isset($instance[$field_name]) ? $instance[$field_name] : $field_data['default_value']); ?>" />
				</p>
				<?php
				/* Otherwise show an error */
			else:
				echo __('Error - Field type not supported', $this->textdomain) . ': ' . $field_data['type'];
			endif;
		}
	}

	/**
	* Updating widget by replacing the old instance with new
	*
	* @param array $new_instance
	* @param array $old_instance
	* @return array
	*/
	public function update($new_instance, $old_instance)
	{
		return $new_instance;
	}

	/**
	 * Function to load the widget
	 */
	private function widget_output($args, $instance)
	{
		extract($instance);

		/**
		 * Require the twitter.php file to load the widget content
		 */
		$widget = '
			<div class="gmpw-container">
				<div class="gmpw-head">
					<div class="gmpw-banner">
						<!-- Contains GitHub banner -->
					</div>
					<div class="gmpw-profile-picture">
						<!-- Contains GitHub profile picture -->
					</div>
					<div class="gmpw-name">
						James Valentine
					</div>
					<div class="gmpw-user">
						f13dev
					</div>
				</div>
				<div class="gmpw-bio">
					GitHub bio goes here
				</div>
				<div class="gmpw-info">
					<span class="gmpw-info-user">F13 Dev</span>
					<span class="gmpw-info-location">Margate, United Kingdom</span>
					<span class="gmpw-info-website"><a href="#">http://f13dev.com</a></span>
					<span class="gmpw-info-joined">Joined on Mar 28, 2016</span>
				</div>
				<div class="gmpw-repos">
					<span class="gmpw-repos-public">15 Public Repositories</span>
					<span class="gmpw-repos-gists">2 Public Gists</span>
				</div>
			</div>
		';
		echo $widget;
	}
}
