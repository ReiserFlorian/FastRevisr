<?php
/**
 * class-revisr-settings-fields.php
 *
 * Displays (and updates) the settings fields.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Settings_Fields {


	private function xcopy($src, $dest) 
	{     	
		foreach (scandir($src) as $file)
		{     		
			$srcfile = rtrim($src, DIRECTORY_SEPARATOR) .DIRECTORY_SEPARATOR. $file;
			$destfile = rtrim($dest, DIRECTORY_SEPARATOR) .DIRECTORY_SEPARATOR. $file;
			if (!is_readable($srcfile)) 
			{
				continue;
			}
			if ($file != '.' && $file != '..')
			{
				if (is_dir($srcfile))
				{
					if (!file_exists($destfile))
					{
						mkdir($destfile);     				
					}     				
					xcopy($srcfile, $destfile);
				} 
				else
				{
					copy($srcfile, $destfile);
				}
			}
		}
	}

	function is_dir_empty($dirname)
	{
		if (!is_dir($dirname)) return false;
		foreach (scandir($dirname) as $file)
		{
				if (!in_array($file, array('.','..'))) return false;
		}
		return true;
	}

	private function move_to_new_location($old, $new)
	{
		if (!file_exists($old))
		{
			return;
		}
		if (is_dir($old))
		{
			$objects = scandir($old);
			foreach ($objects as $object)
			{ 
			  if ($object != "." && $object != "..")
			  { 
				rename($old. DIRECTORY_SEPARATOR . $object, $new. DIRECTORY_SEPARATOR . $object); 
			  } 
			}
		}
		else
		{
			rename($old, $new. DIRECTORY_SEPARATOR. basename($old));
		}
	}
	private function create_dir($path)
	{
		if (file_exists($path))
		{
			return;
		}

		if (mkdir($path))
		{
			// Add .htaccess to prevent direct access.
			$htaccess_content = '<FilesMatch "\.(sql|txt)$">' .
			PHP_EOL . 'Order allow,deny' .
			PHP_EOL . 'Deny from all' .
			PHP_EOL . 'Satisfy All' .
			PHP_EOL . '</FilesMatch>';
			file_put_contents( $path . DIRECTORY_SEPARATOR . '.htaccess', $htaccess_content );
	
			// Add index.php to prevent directory listing.
			$index_content = '<?php // Silence is golden' . PHP_EOL;
			file_put_contents( $path . '/index.php', $index_content );
		}

	}
	private function remove_old_dir($path)
	{
		if (is_writable($path) && is_dir($path))
		{
			rmdir($path);
		}	
	}

	/**
	 * Checks if a setting has been saved and is not empty.
	 * Used to determine if we should update the .git/config.
	 * @access private
	 * @param  string $option The option to check.
	 * @return boolean
	 */
	private function is_updated( $option ) {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( isset( revisr()->options[$option] ) && revisr()->options[$option] != '' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Displays the description for the "General Settings" tab.
	 * @access public
	 */
	public function revisr_general_settings_callback() {
		_e( 'These settings configure the local repository, and may be required for Revisr to work correctly.', 'revisr' );
	}

	/**
	 * Displays the description for the "Remote Settings" tab.
	 * @access public
	 */
	public function revisr_remote_settings_callback() {
		_e( 'These settings are optional, and only need to be configured if you plan to push your website to a remote repository like Bitbucket or Github.', 'revisr' );
	}

	/**
	 * Displays the description for the "Database Dump Settings" tab.
	 * @access public
	 */
	public function revisr_db_dump_settings_callback() {
		_e( 'These settings configure how Revisr should dump your database', 'revisr' );
	}
	/**
	 * Displays/updates the "Username" settings field.
	 * @access public
	 */
	public function username_callback() {

		// Update the .git/config if necessary.
        if ( $this->is_updated( 'username' ) ) {
        	revisr()->git->set_config(  'user', 'name', revisr()->options['username'] );
        }

		$check_username = revisr()->git->get_config( 'user', 'name' );
		if ( $check_username ) {
			$username = $check_username;
		} else {
			$username = '';
		}

		printf(
            '<input type="text" id="username" name="revisr_general_settings[username]" value="%s" class="regular-text revisr-text" />
            <p class="description revisr-description">%s</p>',
           esc_attr( $username ),
            __( 'The username to commit with in Git.', 'revisr' )
        );
	}

	/**
	 * Displays/updates the "Email" settings field.
	 * @access public
	 */
	public function email_callback() {

		// Update the .git/config if necessary.
        if ( $this->is_updated( 'email' ) ) {
        	revisr()->git->set_config( 'user', 'email',  revisr()->options['email'] );
        }

		$check_email = revisr()->git->get_config( 'user', 'email' );
		if ( $check_email ) {
			$email = $check_email;
		}else {
			$email = '';
		}

		printf(
            '<input type="text" id="email" name="revisr_general_settings[email]" value="%s" class="regular-text revisr-text" />
            <p class="description revisr-description">%s</p>',
           	esc_attr( $email ),
            __( 'The email address associated to your Git username. Also used for notifications (if enabled).', 'revisr' )
        );
	}

	/**
	 * Displays/updates the "Git Path" settings field.
	 * @access public
	 */
	public function git_path_callback() {

		$git_path = defined( 'REVISR_GIT_PATH' ) ? REVISR_GIT_PATH : '';

		if ( isset( $_GET['settings-updated'] ) ) {

			$dir 	= revisr()->options['git_path'];
			$line 	= "define('REVISR_GIT_PATH', '$dir');";
			Revisr_Admin::replace_config_line( 'define *\( *\'REVISR_GIT_PATH\'', $line );
			$git_path = revisr()->options['git_path'];
		}

		printf(
			'<input type="text" id="git_path" name="revisr_general_settings[git_path]" class="regular-text revisr-text" value="%s" />
			<p class="description revisr-description">%s</p>',
			esc_attr( $git_path ),
			__( 'If necessary, you can define the installation path to Git here.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the ".gitignore" settings field.
	 * @access public
	 */
	public function gitignore_callback() {

		// Update the .gitignore if necessary.
		if ( isset( $_GET['settings-updated'] ) ) {
			revisr()->git->update_gitignore();
		}

		// Grab the contents from the .gitignore.
		if ( file_exists( revisr()->git->work_tree . '/.gitignore' ) ) {
			$gitignore = file_get_contents( revisr()->git->work_tree . '/.gitignore' );
		} else {
			$gitignore = '';
		}

		// Display the settings field.
		printf(
            '<textarea id="gitignore" name="revisr_general_settings[gitignore]" rows="6" />%s</textarea>
            <p class="description revisr-description">%s</p>',
            esc_textarea( $gitignore ),
            __( 'Add files or directories that you don\'t want to show up in Git here, one per line.<br>This will update the ".gitignore" file for this repository.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Automatic Backups" settings field.
	 * @access public
	 */
	public function automatic_backups_callback() {
		if ( $this->is_updated( 'automatic_backups' ) ) {

			$schedule = revisr()->options['automatic_backups'];

			// Only do anything if the value has been changed.
			if ( revisr()->git->get_config( 'revisr', 'automatic-backups' ) != $schedule ) {

				// Set the new value.
				revisr()->git->set_config( 'revisr', 'automatic-backups', $schedule );

				// Clear the existing cron.
				wp_clear_scheduled_hook( 'revisr_cron' );

				// Schedule the next one!
				if ( 'none' != $schedule ) {
					$next_time = time();
					wp_schedule_event( $next_time, revisr()->options['automatic_backups'], 'revisr_cron' );
				}

			}

		} else {
			$schedule = revisr()->git->get_config( 'revisr', 'automatic-backups' ) ? revisr()->git->get_config( 'revisr', 'automatic-backups' ) : 'none';
		}

		?>

			<select id="automatic_backups" name="revisr_general_settings[automatic_backups]">
				<option value="none" <?php selected( $schedule, 'none' ); ?>><?php _e( 'None', 'revisr' ); ?></option>
				<option value="daily" <?php selected( $schedule, 'daily' ); ?>><?php _e( 'Daily', 'revisr' ); ?></option>
				<option value="weekly" <?php selected( $schedule, 'weekly' ); ?>><?php _e( 'Weekly', 'revisr' ); ?></option>
			</select>
			<span class="description"><?php _e( 'Automatic backups will backup both the files and database at the interval of your choosing.', 'revisr' ); ?></span>

		<?php

	}

	/**
	 * Displays/updates the "Notifications" settings field.
	 * @access public
	 */
	public function notifications_callback() {

		if ( isset( $_GET['settings-updated'] ) ) {
			$notifications = isset( revisr()->options['notifications'] ) ? revisr()->options['notifications'] : 'off';
			revisr()->git->set_config( 'revisr', 'notifications', $notifications );
		}

		$notifications = revisr()->git->get_config( 'revisr', 'notifications' );

		printf(
			'<input type="checkbox" id="notifications" name="revisr_general_settings[notifications]" %s />
			<label for="notifications"><span class="description">%s</span></label>',
			checked( $notifications, 'on', false ),
			__( 'Enabling notifications will send updates about new commits, pulls, and pushes to the email address above.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Uninstall on Delete" settings field.
	 * @access public
	 */
	public function uninstall_on_delete_callback() {

		if ( isset( $_GET['settings-updated'] ) ) {
			$uninstall_on_delete = isset( revisr()->options['uninstall_on_delete'] ) ? 'on' : 'off';
			revisr()->git->set_config( 'revisr', 'uninstall-on-delete', $uninstall_on_delete );
		}

		$uninstall_on_delete = revisr()->git->get_config( 'revisr', 'uninstall-on-delete' );

		printf(
			'<input type="checkbox" id="uninstall_on_delete" name="revisr_general_settings[uninstall_on_delete]" %s  />
			<label for="uninstall_on_delete"><span class="description">%s</span></label>',
			checked( $uninstall_on_delete, 'on', false ),
			__( 'Check to delete all settings and history stored in the database when this plugin is deleted. Settings in the .git/config will not be affected.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Remote Name" settings field.
	 * @access public
	 */
	public function remote_name_callback() {

		// Default to orign as the remote name.
		$remote = $this->is_updated( 'remote_name' ) ? revisr()->options['remote_name'] : 'origin';

		// Set the current remote.
		if ( isset( $_GET['settings-updated'] ) ) {
			revisr()->git->set_config( 'revisr', 'current-remote', $remote );
		}

		$remote = revisr()->git->get_config( 'revisr', 'current-remote' );

		printf(
			'<input type="text" id="remote_name" name="revisr_remote_settings[remote_name]" value="%s" class="regular-text revisr-text" placeholder="origin" />
			<p class="description revisr-description">%s</p>',
			isset( $remote ) ? esc_attr( $remote ) : '',
			__( 'Git sets this to "origin" by default when you clone a repository, and this should be sufficient in most cases. If you\'ve changed the remote name or have more than one remote, you can specify that here.', 'revisr' )
		);

	}

	/**
	 * Displays/updates the "Remote URL" settings field.
	 * @access public
	 */
	public function remote_url_callback() {

		if ( isset( $_GET['settings-updated'] ) ) {

			// Stores the Remote URL.
			$remote = $this->is_updated( 'remote_name' ) ? revisr()->options['remote_name'] : 'origin';
			$add 	= revisr()->git->run( 'remote',  array( 'add', $remote, revisr()->options['remote_url'] ) );

			if ( false == $add ) {
				revisr()->git->run( 'remote', array( 'set-url', $remote, revisr()->options['remote_url'] ) );
			}

		}

		$check_remote = revisr()->git->run( 'ls-remote', array( '--get-url' ) );

		if ( false !== $check_remote && is_array( $check_remote ) ) {
			$remote = $check_remote[0];
		} else {
			$remote = '';
		}

		printf(
			'<input type="text" id="remote_url" name="revisr_remote_settings[remote_url]" value="%s" class="regular-text revisr-text" placeholder="https://user:pass@host.com/user/example.git" /><span id="verify-remote"></span>
			<p class="description revisr-description">%s</p>',
			$remote,
			__( 'Useful if you need to authenticate over "https://" instead of SSH, or if the remote has not already been set through Git.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Revisr Webhook URL" settings field.
	 * @access public
	 */
	public function webhook_url_callback() {
		// Allow the user to unset the Webhook URL.
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( $this->is_updated( 'webhook_url' ) ) {
				revisr()->git->set_config( 'revisr', 'webhook-url', revisr()->options['webhook_url'] );
			} else {
				revisr()->git->run( 'config', array( '--unset', 'revisr.webhook-url' ) );
			}
		}

		// Grab the URL from the .git/config as it MAY be replaced in the database.
		$get_url = revisr()->git->get_config( 'revisr', 'webhook-url' );
		if ( $get_url ) {
			$webhook_url = urldecode($get_url);
		} else {
			$webhook_url = '';
		}
		printf(
			'<input type="text" name="revisr_remote_settings[webhook_url]" value="%s" class="regular-text revisr-text" /><p class="description revisr-description">%s</p>',
			$webhook_url,
			__( 'If you have Revisr installed on another server using the same repository,<br> you can add the Revisr Webhook from that server here to trigger an update when pushing.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Auto Push" settings field.
	 * @access public
	 */
	public function auto_push_callback() {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( isset( revisr()->options['auto_push'] ) ) {
				revisr()->git->set_config( 'revisr', 'auto-push', 'true' );
			} else {
				revisr()->git->run( 'config', array( '--unset', 'revisr.auto-push' ) );
			}
		}

		printf(
			'<input type="checkbox" id="auto_push" name="revisr_remote_settings[auto_push]" %s />
			<label for="auto_push">%s</label>',
			checked( revisr()->git->get_config( 'revisr', 'auto-push' ), 'true', false ),
			__( 'Check to automatically push new commits to the remote repository.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Auto Pull" settings field.
	 * @access public
	 */
	public function auto_pull_callback() {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( isset( revisr()->options['auto_pull'] ) ) {
				revisr()->git->set_config( 'revisr', 'auto-pull', 'true' );
			} else {
				revisr()->git->run( 'config', array( '--unset', 'revisr.auto-pull' ) );
			}
		}

		printf(
			'<input type="checkbox" id="auto_pull" name="revisr_remote_settings[auto_pull]" %s />
			<label for="auto_pull">%s</label>',
			checked( revisr()->git->get_config( 'revisr', 'auto-pull' ), 'true', false ),
			__( 'Check to generate the Revisr Webhook and allow Revisr to automatically pull commits from a remote repository.', 'revisr' )
		);
		$remote 	= new Revisr_Remote();
		$token 		= $remote->get_token();

		if ( $token ) {
			$post_hook 	= get_admin_url() . 'admin-post.php?action=revisr_update&token=' . $remote->get_token();

			?>
			<div id="post-hook">
				<p class="description revisr-description"><?php _e( 'Revisr Webhook:', 'revisr' ); ?></p>
				<input id="post-hook-input" type="text" value="<?php echo $post_hook; ?>" disabled />
				<p class="description revisr-description"><?php _e( 'You can add the above webhook to Bitbucket, GitHub, or another instance of Revisr to automatically update this repository.', 'revisr' ); ?></p>
			</div>
			<?php
		}
		else {
			echo '<p id="post-hook" class="description">' . __( 'There was an error generating the webhook. Please make sure that Revisr has write access to the ".git/config" and try again.', 'revisr' ) . '</p>';
		}

	}

	/**
	 * Displays/updates the "Auto create environment" settings field.
	 * @access public
	 */
	public function auto_create_script_env() {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( isset( revisr()->options['auto_create_script_env'] ) ) {
				revisr()->git->set_config( 'revisr', 'auto_create_script_env', 'true' );
			} else {
				revisr()->git->run( 'config', array( '--unset', 'revisr.auto_create_script_env' ) );
			}
		}

		printf(
			'<input type="checkbox" id="auto_create_script_env" name="revisr_db_dump_settings[auto_create_script_env]" %s />
			<label for="auto_create_script_env">%s</label>',
			checked( revisr()->git->get_config( 'revisr', 'auto_create_script_env' ), 'true', false ),
			__( 'Check to let revisr automatically create the script environment to dump the mysql database. This includes creating the directories for the external python scripts, for the logging and the mysqldumps as well as the copying of the scripts mysql2dump.py and dump2mysql.py to the script location. If you want to use your own environment or your own dumping mechnism, just uncheck this checkbox', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Auto create environment" settings field.
	 * @access public
	 */
	public function use_python_interpreter_callback() {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( isset( revisr()->options['use_python_interpreter_callback'] ) ) {
				revisr()->git->set_config( 'revisr', 'use_python_interpreter_callback', 'true' );
			} else {
				revisr()->git->run( 'config', array( '--unset', 'revisr.use_python_interpreter_callback' ) );
			}
		}

		printf(
			'<input type="checkbox" id="use_python_interpreter_callback" name="revisr_db_dump_settings[use_python_interpreter_callback]" %s />
			<label for="use_python_interpreter_callback">%s</label>',
			checked( revisr()->git->get_config( 'revisr', 'use_python_interpreter_callback' ), 'true', false ),
			__( 'If checked, the python interpreter will be used for the external scripts, otherwise the scripts will be called directly in the shell environment of the host system', 'revisr' )
		);
	}


	/**
	 * Displays/updates the "mysql2dump" and "dump2mysql" settings field.
	 * @access public
	 */
	public function mysql_dump_helper_callback() {
		$oldMysql2DumpPath = revisr()->git->get_config( 'revisr', 'mysql2dump' );
		$oldDump2MysqlPath = revisr()->git->get_config( 'revisr', 'dump2mysql' );
		$usePythonInt = false;
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( $this->is_updated( 'mysql2dump' ) ) {
				revisr()->git->set_config( 'revisr', 'mysql2dump', revisr()->options['mysql2dump'] );
			} else {
				revisr()->git->set_config( 'revisr', 'mysql2dump', "./scripts/mysql2dump.py" );
				$usePythonInt = true;
			}
			if ( $this->is_updated( 'dump2mysql' ) ) {
				revisr()->git->set_config( 'revisr', 'dump2mysql', revisr()->options['dump2mysql'] );
			} else {
				revisr()->git->set_config( 'revisr', 'dump2mysql', "./scripts/dump2mysql.py" );
				$usePythonInt = true;
			}
		}
		
		// Grab the URL from the .git/config as it will be replaced in the database.
		$path = revisr()->git->get_config( 'revisr', 'mysql2dump' );
		if ( $path !== false ) {
			$mysql2dump_path = $path;
		} else {
			$mysql2dump_path = "./scripts/mysql2dump.py";
			$usePythonInt = true;
		}
		
		$path = revisr()->git->get_config( 'revisr', 'dump2mysql' );
		if ( $path !== false ) {
			$dump2mysql_path = $path;
		} else {
			$dump2mysql_path = "./scripts/dump2mysql.py";
			$usePythonInt = true;
		}

		if ($usePythonInt)
		{
			revisr()->git->set_config( 'revisr', 'use_python_interpreter_callback', 'true' );
		}

		if (revisr()->git->get_config( 'revisr', 'auto_create_script_env' ) == 'true')
		{
			if ($oldMysql2DumpPath !== false && $oldMysql2DumpPath !== $mysql2dump_path)
			{
				if (basename($oldMysql2DumpPath) == "mysql2dump.py")
				{
					unlink(get_home_path() . $oldMysql2DumpPath);
					$oldDir = get_home_path() . dirname($oldMysql2DumpPath);
					if (is_dir_empty($oldDir))
					{
						rmdir($oldDir);
					}
				}
				if (basename($mysql2dump_path) == "mysql2dump.py")
				{
					create_dir(get_home_path() . dirname($mysql2dump_path));
				}
			}
			if ($oldDump2MysqlPath !== false && $oldDump2MysqlPath !== $dump2mysql_path)
			{
				if (basename($oldDump2MysqlPath) == "dump2mysql.py")
				{
					unlink(get_home_path() . $oldDump2MysqlPath);
					$oldDir = get_home_path() . dirname($oldDump2MysqlPath);
					if (is_dir_empty($oldDir))
					{
						rmdir($oldDir);
					}
				}
				if (basename($dump2mysql_path) == "dump2mysql.py")
				{
					create_dir(get_home_path() . dirname($dump2mysql_path));
				}
			}
			$scriptDirPath = dirname($mysql2dump_path);
			if ($scriptDirPath == dirname($dump2mysql_path))
			{
				xcopy(plugin_dir_path(__FILE__). "revisr" . DIRECTORY_SEPARATOR . "scripts", get_home_path() . $scriptDirPath);
			}
		}
		printf(
			'<input type="text" id="mysql2dump" name="revisr_db_dump_settings[mysql2dump]" class="regular-text revisr-text" value="%s" /><br><label for="mysql2dump">%s</label><br><br>
			<input type="text" id="dump2mysql" name="revisr_db_dump_settings[dump2mysql]" class="regular-text revisr-text" value="%s" /><br><label for="dump2mysql">%s</label><br><br>
			<p class="description revisr-description">%s</p>',
			esc_attr($mysql2dump_path),
			__( 'Path to the external script, which creates dump files from the mysql database', 'revisr' ),
			esc_attr($dump2mysql_path),
			__( 'Path to the external script, which loads the dump files into the mysql database', 'revisr' ),
			__( 'Leave blank to reset to default. The path must be given relatively to the root folder of the webpage (normally the www folder).', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "dump path" settings field.
	 * @access public
	 */
	public function mysql_dump_path_callback() {
		$oldPath = revisr()->git->get_config( 'revisr', 'mysq_dump_path' );
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( $this->is_updated( 'mysq_dump_path' ) ) {
				revisr()->git->set_config( 'revisr', 'mysq_dump_path', revisr()->options['mysq_dump_path'] );
			} else {
				revisr()->git->set_config( 'revisr', 'mysq_dump_path', "./mysqldump/" );
			}
		}

		// Grab the URL from the .git/config as it will be replaced in the database.
		$path = revisr()->git->get_config( 'revisr', 'mysq_dump_path' );
		if ( $path !== false ) {
			$dump_path = $path;
		} else {
			$dump_path = './mysqldump/';
		}

		if (revisr()->git->get_config( 'revisr', 'auto_create_script_env' ) == 'true')
		{
			if ($oldPath !== false && $oldPath !== $dump_path)
			{
				create_dir(get_home_path() . $dump_path);
				move_to_new_location(get_home_path() . $oldPath, get_home_path() . $dump_path);
				remove_old_dir(get_home_path() . $oldPath);
			}
		}

		printf(
			'<input type="text" id="mysq_dump_path" name="revisr_db_dump_settings[mysq_dump_path]" class="regular-text revisr-text" value="%s" />
			<p class="description revisr-description">%s</p>',
			esc_attr($dump_path),
			__( 'Path to store the mysql dump. Leave blank to reset to default. The path must be given relative to the root folder of the webpage (normally the www folder).', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "log path" settings field.
	 * @access public
	 */
	public function mysql_log_path_callback() {
		$oldPath = revisr()->git->get_config( 'revisr', 'log_path' );
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( $this->is_updated( 'log_path' ) ) {
				revisr()->git->set_config( 'revisr', 'log_path', revisr()->options['log_path'] );
			} else {
				revisr()->git->set_config( 'revisr', 'log_path', "./mysqlDumpLog.txt" );
			}
		}

		// Grab the URL from the .git/config as it will be replaced in the database.
		$path = revisr()->git->get_config( 'revisr', 'log_path' );
		if ( $path !== false ) {
			$log_path = $path;
		} else {
			$log_path = './mysqlDumpLog.txt';
		}

		if (revisr()->git->get_config( 'revisr', 'auto_create_script_env' ) == 'true')
		{
			if ($oldPath !== false && $oldPath !== $log_path)
			{
				create_dir(get_home_path() . $log_path);
				move_to_new_location(get_home_path() . $oldPath, get_home_path() . $log_path);
				remove_old_dir(get_home_path() . $oldPath);
			}
		}

		printf(
			'<input type="text" id="log_path" name="revisr_db_dump_settings[log_path]" class="regular-text revisr-text" value="%s" />
			<p class="description revisr-description">%s</p>',
			esc_attr($log_path),
			__( 'Path to store the mysql dump log. Leave blank to reset to default. The path must be given relative to the root folder of the webpage (normally the www folder).', 'revisr' )
		);
	}

	// /**
	//  * Displays/updates the "DB Tracking" settings field.
	//  * @access public
	//  */
	// public function tracked_tables_callback() {
	// 	if ( $this->is_updated( 'db_tracking' ) ) {
	// 		revisr()->git->set_config( 'revisr', 'db-tracking', revisr()->options['db_tracking'] );
	// 	}

	// 	if ( $db_tracking = revisr()->git->get_config( 'revisr', 'db-tracking' ) ) {
	// 		if ( $db_tracking == 'custom' && $this->is_updated( 'tracked_tables' ) ) {
	// 			revisr()->git->run( 'config', array( '--unset-all', 'revisr.tracked-tables' ) );
	// 			$tables = revisr()->options['tracked_tables'];
	// 			foreach ( $tables as $table ) {
	// 				revisr()->git->run( 'config', array( '--add', 'revisr.tracked-tables', $table ) );
	// 			}
	// 		} elseif ( $db_tracking != 'custom' ) {
	// 			revisr()->git->run( 'config', array( '--unset-all', 'revisr.tracked-tables' ) );
	// 		}
	// 	} else {
	// 		$db_tracking = '';
	// 	}
	// 	// Allows the user to select the tables they want to track.
	// 	$db 	= new Revisr_DB();
	// 	$tables = $db->get_tables();
	// 	$sizes  = $db->get_sizes();
	// 	echo '<div id="advanced-db-tracking" style="display:none;"><br><select name="revisr_database_settings[tracked_tables][]" multiple="multiple" style="width:35em;height:250px;">';

	// 	if ( is_array( $tables ) ) {

	// 		foreach ( $tables as $table ) {

	// 			$size = isset( $sizes[$table] ) ? $sizes[$table] : '';
	// 			$table_selected = '';

	// 			if ( in_array( $table, $db->get_tracked_tables() ) ) {
	// 				$table_selected = ' selected';
	// 			}

	// 			echo "<option value='$table'$table_selected>$table $size</option>";

	// 		}

	// 	}

	// 	echo '</select></div>';
	// }

	// /**
	//  * Displays/updates the "Development URL" settings field.
	//  * NOTE: DO NOT USE THE OPTION AS STORED IN THE DATABASE!
	//  * @access public
	//  */
	// public function development_url_callback() {
	// 	// Allow the user to unset the dev URL.
	// 	if ( isset( $_GET['settings-updated'] ) ) {
	// 		if ( $this->is_updated( 'development_url' ) ) {
	// 			revisr()->git->set_config( 'revisr', 'dev-url', esc_url_raw( revisr()->options['development_url'] ) );
	// 		} else {
	// 			revisr()->git->run( 'config', array( '--unset', 'revisr.dev-url' ) );
	// 		}
	// 	}

	// 	// Grab the URL from the .git/config as it will be replaced in the database.
	// 	$get_url = revisr()->git->get_config( 'revisr', 'dev-url' );
	// 	if ( $get_url !== false ) {
	// 		$dev_url = $get_url;
	// 	} else {
	// 		$dev_url = '';
	// 	}

	// 	printf(
	// 		'<input type="text" id="development_url" name="revisr_database_settings[development_url]" class="regular-text revisr-text" value="%s" />
	// 		<p class="description revisr-description">%s</p>',
	// 		$dev_url,
	// 		__( 'If you\'re importing the database from a seperate environment, enter the WordPress Site URL for that environment here to replace all occurrences of that URL with the current Site URL during import. This MUST match the WordPress Site URL of the database being imported.', 'revisr' )
	// 	);
	// }

	// /**
	//  * Displays/updates the "DB Driver" settings field.
	//  * @access public
	//  */
	// public function db_driver_callback() {
	// 	if ( $this->is_updated( 'db_driver' ) ) {
	// 		revisr()->git->set_config( 'revisr', 'db-driver', revisr()->options['db_driver'] );
	// 	}

	// 	$current = revisr()->git->get_config( 'revisr', 'db-driver' );

	// }

	// /**
	//  * Displays/updates the "Path to MySQL" settings field.
	//  * @access public
	//  */
	// public function mysql_path_callback() {
	// 	if ( isset( $_GET['settings-updated'] ) ) {
	// 		if ( $this->is_updated( 'mysql_path' ) ) {

	// 			// Properly escape trailing backslashes on Windows.
	// 			if ( substr( revisr()->options['mysql_path'], -1 ) === '\\' ) {
	// 				revisr()->options['mysql_path'] .= '\\';
	// 			}

	// 			revisr()->git->set_config( 'revisr', 'mysql-path', revisr()->options['mysql_path'] );

	// 		} else {
	// 			revisr()->git->run( 'config', array( '--unset', 'revisr.mysql-path' ) );
	// 		}
	// 	}

	// 	if ( $get_path = revisr()->git->get_config( 'revisr', 'mysql-path' ) ) {
	// 		$mysql_path = $get_path;
	// 	} else {
	// 		$mysql_path = '';
	// 	}

	// 	printf(
	// 		'<input type="text" id="mysql_path" name="revisr_database_settings[mysql_path]" value="%s" class="regular-text revisr-text" placeholder="" />
	// 		<p class="description revisr-description">%s</p>',
	// 		esc_attr( $mysql_path ),
	// 		__( 'Leave blank if the full path to MySQL has already been set on the server. Some possible settings include:
	// 		<br><br>For MAMP: /Applications/MAMP/Library/bin/<br>
	// 		For WAMP: C:\wamp\bin\mysql\mysql5.6.12\bin\ ', 'revisr' )
	// 	);
	// }

	// /**
	//  * Displays/updates the "Reset DB" settings field.
	//  * @access public
	//  */
	// public function reset_db_callback() {

	// 	if ( isset( $_GET['settings-updated'] ) ) {

	// 		if ( isset( revisr()->options['reset_db'] ) ) {
	// 			revisr()->git->set_config( 'revisr', 'import-checkouts', 'true' );
	// 		} else {
	// 			revisr()->git->run( 'config', array( '--unset-all', 'revisr.import-checkouts' ) );
	// 		}

	// 		if ( isset( revisr()->options['import_db'] ) ) {
	// 			revisr()->git->set_config( 'revisr', 'import-pulls', 'true' );
	// 		} else {
	// 			revisr()->git->run( 'config',  array( '--unset-all', 'revisr.import-pulls' ) );
	// 		}
	// 	}

	// 	printf(
	// 		'<input type="checkbox" id="reset_db" name="revisr_database_settings[reset_db]" %s /><label for="reset_db">%s</label><br><br>
	// 		<input type="checkbox" id="import_db" name="revisr_database_settings[import_db]" %s /><label for="import_db">%s</label><br><br>
	// 		<p class="description revisr-description">%s</p>',
	// 		checked( revisr()->git->get_config( 'revisr', 'import-checkouts' ), 'true', false ),
	// 		__( 'Import database when changing branches?', 'revisr' ),
	// 		checked( revisr()->git->get_config( 'revisr', 'import-pulls' ), 'true', false ),
	// 		__( 'Import database when pulling commits?', 'revisr' ),
	// 		__( 'If checked, Revisr will automatically import the above tracked tables while pulling from or checking out a branch. The tracked tables will be backed up beforehand to provide a restore point immediately prior to the import. Use this feature with caution and only after verifying that you have a full backup of your website.', 'revisr' )
	// 	);
	// }
}
