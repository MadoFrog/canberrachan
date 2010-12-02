<?php
	require 'inc/functions.php';
	require 'inc/display.php';
	if (file_exists('inc/instance-config.php')) {
		require 'inc/instance-config.php';
	}
	require 'inc/config.php';
	require 'inc/template.php';
	require 'inc/user.php';
	require 'inc/mod.php';
	
	// If not logged in
	if(!$mod) {
		if(isset($_POST['login'])) {
			// Check if inputs are set and not empty
			if(	!isset($_POST['username']) ||
				!isset($_POST['password']) ||
				empty($_POST['username']) ||
				empty($_POST['password'])
				) loginForm(ERROR_INVALID, $_POST['username']);
			
			// Open connection
			sql_open();
			
			if(!login($_POST['username'], $_POST['password']))
				loginForm(ERROR_INVALID, $_POST['username']);
			
			// Login successful
			// Set cookies
			setCookies();
			
			// Redirect
			header('Location: ?' . MOD_DEFAULT, true, REDIRECT_HTTP);
			
			// Close connection
			sql_close();
		} else {
			loginForm();
		}
	} else {
		$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		$regex = Array(
			'board' => str_replace('%s', '(\w{1,8})', preg_quote(BOARD_PATH, '/'))
		);
		
		if(preg_match('/^\/?$/', $query)) {
			// Dashboard
			$body = '';
			
			$body .= 	'<fieldset><legend>Boards</legend>' . 
						ulBoards() . 
						'</fieldset>';
			
			// TODO: Statistics, etc, in the dashboard.
			
			echo Element('page.html', Array(
				'index'=>ROOT,
				'title'=>'Dashboard',
				'body'=>$body
				)
			);
		} elseif(preg_match('/^\/new$/', $query)) {
			// New board
			$body = '';
			
			if(isset($_POST['new_board'])) {
				// Create new board
				if(	!isset($_POST['uri']) ||
					!isset($_POST['title']) ||
					!isset($_POST['subtitle'])
				)	error(ERROR_MISSEDAFIELD);
				
				$b = Array(
					'uri' => $_POST['uri'],
					'title' => $_POST['title'],
					'subtitle' => $_POST['subtitle']
				);
				
				// Check required fields
				if(empty($b['uri']))
					error(sprintf(ERROR_REQUIRED, 'URI'));
				if(empty($b['title']))
					error(sprintf(ERROR_REQUIRED, 'title'));
				
				// Check string lengths
				if(strlen($b['uri']) > 8)
					error(sprintf(ERROR_TOOLONG, 'URI'));
				if(strlen($b['title']) > 20)
					error(sprintf(ERROR_TOOLONG, 'title'));
				if(strlen($b['subtitle']) > 40)
					error(sprintf(ERROR_TOOLONG, 'subtitle'));
				
				if(!preg_match('/^\w+$/', $b['uri']))
					error(sprintf(ERROR_INVALIDFIELD, 'URI'));
				
				mysql_query(sprintf(
					"INSERT INTO `boards` VALUES (NULL, '%s', '%s', " .
							(empty($b['subtitle']) ? 'NULL' :  "'%s'" ) .
					")",
						mysql_real_escape_string($b['uri']),
						mysql_real_escape_string($b['title']),
						mysql_real_escape_string($b['subtitle'])
				), $sql) or error(mysql_error($sql));
				
				// Open the board
				openBoard($b['uri']) or error("Couldn't open board after creation.");
				
				// Create the posts table
				mysql_query(Element('posts.sql', Array('board' => $board['uri'])), $sql) or error(mysql_error($sql));
				
				// Build the board
				buildIndex();
			}
			
			$body .= form_newBoard();
			
			// TODO: Statistics, etc, in the dashboard.
			
			echo Element('page.html', Array(
				'index'=>ROOT,
				'title'=>'New board',
				'body'=>$body
				)
			);
		} elseif(preg_match('/^\/' . $regex['board'] . '(' . preg_quote(FILE_INDEX, '/') . ')?$/', $query, $matches)) {
			// Board index
			
			$boardName = $matches[1];
			// Open board
			openBoard($boardName);
			
			echo Element('index.html', index(1));		
			
		} else {
			error("Page not found.");
		}
	}
	
	// Close the connection in-case it's still open
	sql_close();
?>

