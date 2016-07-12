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

/**
 * A function to register and enque the stylesheet
 */
function gmpw_style()
{
	wp_register_style( 'f13-gmpw-style', plugins_url('github-profile-widget.css', __FILE__) );
  wp_enqueue_style( 'f13-gmpw-style' );
}

/**
 * A class to generate a GitHub profile widget
 */
class GitHub_Mini_Profile_Widget extends WP_Widget
{
	/** Basic Widget Settings */
	const WIDGET_NAME = "GitHub Mini Profile Widget";
	const WIDGET_DESCRIPTION = "Add a mini version of your GitHub profile to your website.";

	var $textdomain;
	var $fields;

	/**
	* Create a new instance of the GitHub widget
	* by setting the widget setting fields.
	*/
	function __construct()
	{
		$this->textdomain = strtolower(get_class($this));

		//Add fields
		$this->add_field('title', 'Widget title', '', 'text');
		$this->add_field('github_user', 'GitHub ID', '', 'text');
		$this->add_field('github_token', 'GitHub API Token', '', 'text');
		$this->add_field('github_timeout', 'Cache timeout (minutes)', '30', 'number');
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
			if($field_data['type'] === 'text')
			{
				?>
				<p>
					<label for="<?php echo $this->get_field_id($field_name); ?>"><?php _e($field_data['description'], $this->textdomain ); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id($field_name); ?>" name="<?php echo $this->get_field_name($field_name); ?>" type="text" value="<?php echo esc_attr(isset($instance[$field_name]) ? $instance[$field_name] : $field_data['default_value']); ?>" />
				</p>
				<?php

			}
			else
			if($field_data['type'] === 'number')
			{
				?>
				<p>
					<label for="<?php echo $this->get_field_id($field_name); ?>"><?php _e($field_data['description'], $this->textdomain ); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id($field_name); ?>" name="<?php echo $this->get_field_name($field_name); ?>" type="number" value="<?php echo esc_attr(isset($instance[$field_name]) ? $instance[$field_name] : $field_data['default_value']); ?>" />
				</p>
				<?php
			}
			else
			{
				/* Otherwise show an error */
				echo __('Error - Field type not supported', $this->textdomain) . ': ' . $field_data['type'];
			}
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

		// Set the cache name for this instance of the widget
		$cache = get_transient('wpgpw' . md5(serialize($atts)));

		if ($cache)
		{
				// If the cache exists, return it rather than re-creating it
				echo $cache;
		}
		else
		{
			// Get the API results
			$userAPI = $this->f13_get_github_api('https://api.github.com/users/' . $github_user);
			$widget = '
				<div class="gmpw-container">
					<a href="https://github.com/' . $userAPI['login'] . '" class="gmpw-head-link">
						<div class="gmpw-head">
						  <div class="gmpw-headder">
								<svg aria-hidden="true" height="18" version="1.1" viewBox="0 0 16 16" width="18"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0 0 16 8c0-4.42-3.58-8-8-8z"></path></svg>
								GitHub
							</div>
							<div class="gmpw-profile-picture">
								<img src="' . $userAPI['avatar_url'] . '"  />
							</div>
							<div class="gmpw-names">
								<div class="gmpw-name">
									' . $userAPI['name'] . '
								</div>
								<div class="gmpw-user">
									@' . $userAPI['login'] . '
								</div>
							</div>
						</a>
					</div>';

					if ($userAPI['bio'] != '')
					{
						$widget .= '
						<div class="gmpw-bio">
							<span>Bio: </span>
							' . $userAPI['bio'] . '
						</div>';
					}

					$widget .= '
					<div class="gmpw-info">
						<span class="gmpw-info-user">
							<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 14 16" width="14"><path d="M4.75 4.95C5.3 5.59 6.09 6 7 6c.91 0 1.7-.41 2.25-1.05A1.993 1.993 0 0 0 13 4c0-1.11-.89-2-2-2-.41 0-.77.13-1.08.33A3.01 3.01 0 0 0 7 0C5.58 0 4.39 1 4.08 2.33 3.77 2.13 3.41 2 3 2c-1.11 0-2 .89-2 2a1.993 1.993 0 0 0 3.75.95zm5.2-1.52c.2-.38.59-.64 1.05-.64.66 0 1.2.55 1.2 1.2 0 .65-.55 1.2-1.2 1.2-.65 0-1.17-.53-1.19-1.17.06-.19.11-.39.14-.59zM7 .98c1.11 0 2.02.91 2.02 2.02 0 1.11-.91 2.02-2.02 2.02-1.11 0-2.02-.91-2.02-2.02C4.98 1.89 5.89.98 7 .98zM3 5.2c-.66 0-1.2-.55-1.2-1.2 0-.65.55-1.2 1.2-1.2.45 0 .84.27 1.05.64.03.2.08.41.14.59C4.17 4.67 3.66 5.2 3 5.2zM13 6H1c-.55 0-1 .45-1 1v3c0 .55.45 1 1 1v2c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h1v3c0 .55.45 1 1 1h2c.55 0 1-.45 1-1v-3h1v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-2c.55 0 1-.45 1-1V7c0-.55-.45-1-1-1zM3 13H2v-3H1V7h2v6zm7-2H9V9H8v6H6V9H5v2H4V7h6v4zm3-1h-1v3h-1V7h2v3z"></path></svg>
							' . $userAPI['login'] . '<br />';

							if ($userAPI['location'] != '')
							{
								$widget .= '
								<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 12 16" width="12"><path d="M6 0C2.69 0 0 2.5 0 5.5 0 10.02 6 16 6 16s6-5.98 6-10.5C12 2.5 9.31 0 6 0zm0 14.55C4.14 12.52 1 8.44 1 5.5 1 3.02 3.25 1 6 1c1.34 0 2.61.48 3.56 1.36.92.86 1.44 1.97 1.44 3.14 0 2.94-3.14 7.02-5 9.05zM8 5.5c0 1.11-.89 2-2 2-1.11 0-2-.89-2-2 0-1.11.89-2 2-2 1.11 0 2 .89 2 2z"></path></svg>
								' . $userAPI['location'] . '<br />';
							}

							if ($userAPI['email'] != '')
							{
								$widget .= '
								<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 14 16" width="14"><path d="M0 4v8c0 .55.45 1 1 1h12c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1H1c-.55 0-1 .45-1 1zm13 0L7 9 1 4h12zM1 5.5l4 3-4 3v-6zM2 12l3.5-3L7 10.5 8.5 9l3.5 3H2zm11-.5l-4-3 4-3v6z"></path></svg>
								<a href="mailto:' . $userAPI['email'] . '">' . $userAPI['email'] . '</a>';
							}

						$widget .= '
						</span>
						<span class="gmpw-info-website">';

							if ($userAPI['blog'] != '')
							{
								$widget .= '
								<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 16 16" width="16"><path d="M4 9h1v1H4c-1.5 0-3-1.69-3-3.5S2.55 3 4 3h4c1.45 0 3 1.69 3 3.5 0 1.41-.91 2.72-2 3.25V8.59c.58-.45 1-1.27 1-2.09C10 5.22 8.98 4 8 4H4c-.98 0-2 1.22-2 2.5S3 9 4 9zm9-3h-1v1h1c1 0 2 1.22 2 2.5S13.98 12 13 12H9c-.98 0-2-1.22-2-2.5 0-.83.42-1.64 1-2.09V6.25c-1.09.53-2 1.84-2 3.25C6 11.31 7.55 13 9 13h4c1.45 0 3-1.69 3-3.5S14.5 6 13 6z"></path></svg>
								<a href="' . $userAPI['blog'] . '">' . $userAPI['blog'] . '</a><br />';
							}

							$widget .= '
							<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 14 16" width="14"><path d="M8 8h3v2H7c-.55 0-1-.45-1-1V4h2v4zM7 2.3c3.14 0 5.7 2.56 5.7 5.7s-2.56 5.7-5.7 5.7A5.71 5.71 0 0 1 1.3 8c0-3.14 2.56-5.7 5.7-5.7zM7 1C3.14 1 0 4.14 0 8s3.14 7 7 7 7-3.14 7-7-3.14-7-7-7z"></path></svg>
							Joined on ' . $this->gitDate($userAPI['created_at']) . '
						</span>
					</div>';
					// Change to if show numbers
					if (true)
					{
						$starredCount = count($this->f13_get_github_api('https://api.github.com/users/' . $github_user . '/starred'));
						$widget .= '
						<div class="gmpw-numbers">
							<a href="#">
								<span>
									<span>' . $userAPI['followers'] . '</span><br />
									Follower
								</span>
							</a>
							<a href="#">
								<span>
									<span>' . $starredCount . '</span><br />
									Starred
								</span>
							</a>
							<a href="#">
								<span>
									<span>' . $userAPI['following'] . '</span><br />
									Following
								</span>
							</a>
						</div>';
					}
					$widget .= '
					<div class="gmpw-repos">
						<span class="gmpw-repos-public">
							<svg aria-hidden="true" class="octicon octicon-repo" height="16" version="1.1" viewBox="0 0 12 16" width="12"><path d="M4 9H3V8h1v1zm0-3H3v1h1V6zm0-2H3v1h1V4zm0-2H3v1h1V2zm8-1v12c0 .55-.45 1-1 1H6v2l-1.5-1.5L3 16v-2H1c-.55 0-1-.45-1-1V1c0-.55.45-1 1-1h10c.55 0 1 .45 1 1zm-1 10H1v2h2v-1h3v1h5v-2zm0-10H2v9h9V1z"></path></svg>
							<a href="https://gists.github.com/' . $userAPI['login'] . '">' . $userAPI['public_repos'] . ' Public Repos</a>
							</span>
						<span class="gmpw-repos-gists">
							<svg aria-hidden="true" height="16" version="1.1" viewBox="0 0 12 16" width="16"><path d="M7.5 5L10 7.5 7.5 10l-.75-.75L8.5 7.5 6.75 5.75 7.5 5zm-3 0L2 7.5 4.5 10l.75-.75L3.5 7.5l1.75-1.75L4.5 5zM0 13V2c0-.55.45-1 1-1h10c.55 0 1 .45 1 1v11c0 .55-.45 1-1 1H1c-.55 0-1-.45-1-1zm1 0h10V2H1v11z"></path></svg>
							<a href="https://gists.github.com/' . $userAPI['login'] . '">' . $userAPI['public_gists'] . ' Public Gists</a>
						</span>
					</div>
				</div>
			';
			set_transient('wpgpw' . md5(serialize($atts)), $widget, $github_timeout);
			echo $widget;
		}
	}

	private function f13_get_github_api($url)
	 {
			 // Start curl
			 $curl = curl_init();
			 // Set curl options
			 curl_setopt($curl, CURLOPT_URL, $url);
			 curl_setopt($curl, CURLOPT_HTTPGET, true);

			 // Check if a token is set
			 if (preg_replace('/\s+/', '', $this->github_token) != '' || $this->github_token != null)
			 {
					 // If a token is set attempt to send it in the header
					 curl_setopt($curl, CURLOPT_HTTPHEADER, array(
							 'Content-Type: application/json',
							 'Accept: application/json',
							 'Authorization: token ' . $this->github_token
					 ));
			 }
			 else
			 {
					 // If no token is set, send the header as unauthenticated,
					 // some features may not work and a lower rate limit applies.
					 curl_setopt($curl, CURLOPT_HTTPHEADER, array(
							 'Content-Type: application/json',
							 'Accept: application/json'
					 ));
			 }
			 // Set the user agent
			 curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
			 // Set curl to return the response, rather than print it
			 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			 // Get the results
			 $result = curl_exec($curl);

			 // Close the curl session
			 curl_close($curl);

			 // Decode the results
			 $result = json_decode($result, true);

			 // Return the results
			 return $result;
	 }

	 private function gitDate($date)
	 {
		 $dateArray = explode('-', $date);
		 // Add the day to the string
		 $date = substr($dateArray[2], 0, 2) . ' ';
		 // Add the month to the string
		 $date .= $this->getMonth($dateArray[1]) . ' ';
		 // Add the year
		 $date .= $dateArray[0];
		 return $date;
	 }

	 private function getMonth($month)
	 {
		 if ($month == 01)
		 {
			 return 'Jan';
		 }
		 else
		 if ($month == 02)
		 {
			 return 'Feb';
		 }
		 else
		 if ($month == 03)
		 {
			 return 'Mar';
		 }
		 else
		 if ($month == 04)
		 {
			 return 'Apr';
		 }
		 else
		 if ($month == 05)
		 {
			 return 'May';
		 }
		 else
		 if ($month == 06)
		 {
			 return 'Jun';
		 }
		 else
		 if ($month == 07)
		 {
			 return 'Jul';
		 }
		 else
		 if ($month == 08)
		 {
			 return 'Aug';
		 }
		 else
		 if ($month == 09)
		 {
			 return 'Sep';
		 }
		 else
		 if ($month == 10)
		 {
			 return 'Oct';
		 }
		 else
		 if ($month == 11)
		 {
			 return 'Nov';
		 }
		 else
		 if ($month == 12)
		 {
			 return 'Dec';
		 }
	 }

}
