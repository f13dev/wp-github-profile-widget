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
				  <div class="gmpw-headder">
						GitHub @f13dev
					</div>
					<div class="gmpw-profile-picture">
						<img src="https://avatars1.githubusercontent.com/u/18120373?v=3&s=460"  />
					</div>
					<div class="gmpw-names">
						<div class="gmpw-name">
							James Valentine
						</div>
						<div class="gmpw-user">
							@f13dev
						</div>
					</div>

				</div>
				<div class="gmpw-bio">
					BSc (Hons) Computing & IT (Software Development) student with the Open University. A messy PHP coder of 10+ years. Scribbling bits of code on GitHub.
				</div>
				<div class="gmpw-info">
					<span class="gmpw-info-user">
						<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 14 16" width="14"><path d="M4.75 4.95C5.3 5.59 6.09 6 7 6c.91 0 1.7-.41 2.25-1.05A1.993 1.993 0 0 0 13 4c0-1.11-.89-2-2-2-.41 0-.77.13-1.08.33A3.01 3.01 0 0 0 7 0C5.58 0 4.39 1 4.08 2.33 3.77 2.13 3.41 2 3 2c-1.11 0-2 .89-2 2a1.993 1.993 0 0 0 3.75.95zm5.2-1.52c.2-.38.59-.64 1.05-.64.66 0 1.2.55 1.2 1.2 0 .65-.55 1.2-1.2 1.2-.65 0-1.17-.53-1.19-1.17.06-.19.11-.39.14-.59zM7 .98c1.11 0 2.02.91 2.02 2.02 0 1.11-.91 2.02-2.02 2.02-1.11 0-2.02-.91-2.02-2.02C4.98 1.89 5.89.98 7 .98zM3 5.2c-.66 0-1.2-.55-1.2-1.2 0-.65.55-1.2 1.2-1.2.45 0 .84.27 1.05.64.03.2.08.41.14.59C4.17 4.67 3.66 5.2 3 5.2zM13 6H1c-.55 0-1 .45-1 1v3c0 .55.45 1 1 1v2c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h1v3c0 .55.45 1 1 1h2c.55 0 1-.45 1-1v-3h1v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2c.55 0 1-.45 1-1V7c0-.55-.45-1-1-1zM3 13H2v-3H1V7h2v6zm7-2H9V9H8v6H6V9H5v2H4V7h6v4zm3-1h-1v3h-1V7h2v3z"></path></svg>
						F13 Dev<br />
						<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 12 16" width="12"><path d="M6 0C2.69 0 0 2.5 0 5.5 0 10.02 6 16 6 16s6-5.98 6-10.5C12 2.5 9.31 0 6 0zm0 14.55C4.14 12.52 1 8.44 1 5.5 1 3.02 3.25 1 6 1c1.34 0 2.61.48 3.56 1.36.92.86 1.44 1.97 1.44 3.14 0 2.94-3.14 7.02-5 9.05zM8 5.5c0 1.11-.89 2-2 2-1.11 0-2-.89-2-2 0-1.11.89-2 2-2 1.11 0 2 .89 2 2z"></path></svg>
						Margate, United Kingdom
					</span>
					<span class="gmpw-info-website">
						<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 16 16" width="16"><path d="M4 9h1v1H4c-1.5 0-3-1.69-3-3.5S2.55 3 4 3h4c1.45 0 3 1.69 3 3.5 0 1.41-.91 2.72-2 3.25V8.59c.58-.45 1-1.27 1-2.09C10 5.22 8.98 4 8 4H4c-.98 0-2 1.22-2 2.5S3 9 4 9zm9-3h-1v1h1c1 0 2 1.22 2 2.5S13.98 12 13 12H9c-.98 0-2-1.22-2-2.5 0-.83.42-1.64 1-2.09V6.25c-1.09.53-2 1.84-2 3.25C6 11.31 7.55 13 9 13h4c1.45 0 3-1.69 3-3.5S14.5 6 13 6z"></path></svg>
						<a href="#">http://f13dev.com</a><br />
						<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 16 16" width="16"><path d="M4 9h1v1H4c-1.5 0-3-1.69-3-3.5S2.55 3 4 3h4c1.45 0 3 1.69 3 3.5 0 1.41-.91 2.72-2 3.25V8.59c.58-.45 1-1.27 1-2.09C10 5.22 8.98 4 8 4H4c-.98 0-2 1.22-2 2.5S3 9 4 9zm9-3h-1v1h1c1 0 2 1.22 2 2.5S13.98 12 13 12H9c-.98 0-2-1.22-2-2.5 0-.83.42-1.64 1-2.09V6.25c-1.09.53-2 1.84-2 3.25C6 11.31 7.55 13 9 13h4c1.45 0 3-1.69 3-3.5S14.5 6 13 6z"></path></svg>
						Joined on Mar 28, 2016
					</span>
				</div>
				<div class="gmpw-numbers">
					<span>
						1<br />
						Follower
					</span>
					<span>
						0<br />
						Starred
					</span>
					<span>
						9<br />
						Following
					</span>
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
