<?php
/*
 * Config Section
 */
$password = 'CHANGEME!';
$jqueryurl = 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js';
$lessjsurl = 'http://cdnjs.cloudflare.com/ajax/libs/less.js/1.3.0/less-1.3.0.min.js';
$lessgroupfile = 'write-less-groups.json';

// add a list of less files that will compiled as one (in order).
// Use a json file like so:
/* // write-less-groups.json
{
	"lessgroup.css" : [
		"variables.less",
		"mixins.less",
		"normalize.less",
		"main.less",
		"responsive.less",
		"print.less"
	],
	"other.css" : [
		"variables.less",
		"mixins.less",
		"normalize.less",
	]
}
*/

/* End of Config Section */


// Check password
$pass = ( isset( $_COOKIE['lessmakerpassword'] ) && $password === $_COOKIE['lessmakerpassword'] );

// Move up a directory
chdir('../');

//$lessgroup = array();
$lessgroups = array();
if ( file_exists( $lessgroupfile ) ) {
	$lessgroups = json_decode( file_get_contents( $lessgroupfile ), true );
	if ( ! is_array( $lessgroups ) ) $lessgroups = array();
	$values = array_values( $lessgroups );
	$values = array_shift( $values );
	if ( ! empty( $lessgroups ) && ! is_array( $values ) )
		$lessgroups = array( "lessgroup.css" => $lessgroups );
	unset( $values );
}

// Collect all LESS files
$lessfiles = glob( '*.less' );

// Collect all CSS files
$cssfiles = glob( '*.css' );

// Handle Save:
// Check if the request was a load or a submit
// Check editfile request matches an actual file
// Check Password
if ( isset( $_REQUEST['ajaxsubmit'] ) && $_REQUEST['ajaxsubmit'] && in_array( $_REQUEST['editfile'], $lessfiles ) && $pass ) {
	
	$newless = stripslashes( $_REQUEST['less'] ); // Contains the less data
	$lessfile = $_REQUEST['editfile']; // requested File name
	$cssfile = str_replace( '.less', '.css', $lessfile ); // assumes css File name default
	$newcss = $_REQUEST['css']; // Contains the css data
	
	if ( is_array( $newcss ) ) {
		foreach ( $newcss as $stylesheet => $styles ) {
			if ( array_key_exists( $stylesheet, $lessgroups ) )
				write_the_file( $stylesheet, stripslashes( $styles ) );
		}
	} else {
		$newcss = stripslashes( $newcss ); // Contains the css data
		
		// If group, concatinate all css into one file (because of dependancies).
		if ( ! empty( $lessgroups ) ) {
			foreach ( $lessgroups as $name => $lessgroup ) {
				if ( in_array( $lessfile, $lessgroup ) )
					$cssfile = $name; // overwrites assumed css file name.
				write_the_file( $cssfile, $newcss );
			}
		} else {
			write_the_file( $cssfile, $newcss );
		}
	}
	
	write_the_file( $lessfile, $newless );
	
	exit();
}

// filters out the grouped less files - they'll be added back later, as optgroups.
foreach ( $lessfiles as $i => $file ) {
	if ( ! empty( $lessgroups ) ) {
		foreach ( $lessgroups as $name => $lessgroup ) {
			if ( in_array( $file, $lessgroup ) )
				unset( $lessfiles[ $i ] );
		}
	}
}
function write_the_file( $filename, $data ) {
	if ( file_exists( $filename ) ) {
		if ( is_writable( $filename ) ) {
			if ( $handle = fopen( $filename, 'wb' ) ) {
				if ( 0 < fwrite( $handle, $data ) ) {
					echo "Successfully wrote to file ($filename)".PHP_EOL;
				} else if ( false === fwrite( $handle, $data ) ) {
					echo "Cannot write to file ($filename)".PHP_EOL;
				} else if ( 0 === fwrite( $handle, $data ) ) {
					echo "wrote 0 bytes to ($filename)".PHP_EOL;
				}
				fclose( $handle );
				
			} else {
				echo "cannot open file ($filename)".PHP_EOL;
			}
		} else {
			echo "file ($filename) is not writable".PHP_EOL;
		}
	} else {
		if ( $handle = fopen( $filename, 'wb' ) ) {
			echo "Successfully created file ($filename)".PHP_EOL;
			if ( 0 < fwrite( $handle, $data ) ) {
				echo "Successfully wrote to file ($filename)".PHP_EOL;
			} else if ( false === fwrite( $handle, $data ) ) {
				echo "Cannot write to file ($filename)".PHP_EOL;
			} else if ( 0 === fwrite( $handle, $data ) ) {
				echo "wrote 0 bytes to ($filename)".PHP_EOL;
			}
			fclose( $handle );
			
		} else {
			echo "cannot open file ($filename)".PHP_EOL;
		}
	}
}


$file = empty( $_REQUEST['file'] ) ? '' : $_REQUEST['file'];
$logout = isset( $_REQUEST['logout'] );
$less = '';
$error = false;
$preless = '';
$postless = '';
$filegroups = array();
$contents = array();

if ( in_array( $file, $lessfiles ) ) // Not in a group
{
	$less = file_get_contents( $file ); // File is whitelisted (by virtue of existing)
}
else if ( ! empty( $lessgroups ) ) {
	foreach ( $lessgroups as $name => $lessgroup ) {
		if ( in_array( $file, $lessgroup ) ) // File is in a Group
		{
			$pre = true;
			$contents[$name] = array();
			$filegroups[] = $name;
			foreach( $lessgroup as $gfile ) {
				if ( $pre ) { // dependancies, before current file
					if ( $gfile == $file ) {
						$pre = false;
						continue;
					}
					$preless .= file_get_contents( $gfile );
					$contents[$name]['preless'] = $preless;
				}
				else // dependancies, after current file
					$postless .= file_get_contents( $gfile );
					$contents[$name]['postless'] = $postless;
			}
			// Current file
			if ( empty( $less ) ) 
				$less = file_get_contents( $file );
		}
	}
}
else
{
	$error = true;
}
?>
<html>
<head>
<meta charset="UTF-8" />
	<title>Write LESS</title>
	<script src="<?php echo $jqueryurl ?>"></script>
	<script src="<?php echo $lessjsurl ?>"></script>
	<script src="CodeMirrorCustom-2.32.min.js"></script>
	<link href="CodeMirrorCustom-2.32.min.css" rel="stylesheet">
<style>

/*html, body {
	height: 100%;
	margin: 0;
	padding: 0;
}*/
textarea {
	display: block;
}
.CodeMirror {
	border: 1px solid #DFDFDF;
	background-color: white;
	border-radius: 3px;
	margin: 0;
	-moz-background-clip: padding;
	-webkit-background-clip: padding-box;
	background-clip: padding-box;
	overflow: hidden;
	font-family: "Courier New", Courier, monospace;
	font-size: 11px;
}
.CodeMirror-scroll {
    height: auto;
}
#control {
	position: fixed;
	top: 0; right: 0; bottom: auto; left: 0;
	height: 20px;
	overflow: auto;
	margin: 0;
	background-color: white;
	z-index: 100;
	padding: 10px;
}
#editors {
	margin-top:40px;
	overflow: auto;
}
#error p,
#update p {
	margin: 0;
}
#error,
#update {
	margin: 0;
	margin-right: 30px;
	padding: 0 10px;
	float: left;
	
	background-color: #FFFFE0;
	border-color: #E6DB55;
	font-size: 13px;
	padding: 0 0.6em;
	border-radius: 3px 3px 3px 3px;
	border-style: solid;
	border-width: 1px;line-height: 20px;
}
select[name="file"] {
	margin-right: 30px;
	float: left;
}
textarea[disabled], textarea.css {
	display: none;
}
/*
*/
</style>
</head>
<body>

<form action="./" method="post" id="control" autocomplete="off">
	<?php if ( $pass ) { ?>
	<select name="file" autocomplete="off">
	 <option value="">Select a file</option>
	<?php 
	foreach ( $lessfiles as $lessfile ) {
		$sel = $lessfile == $file ? ' selected="selected"': '';
		echo '<option' . $sel . '>' . $lessfile . '</option>';
	}
	if ( ! empty( $lessgroups ) ) {
		foreach ( $lessgroups as $name => $lessgroup ) {
		?>
		<optgroup label="<?php echo $name ?>">
			<?php 
			foreach ( $lessgroup as $lessfile ) {
				$sel = $lessfile == $file ? ' selected="selected"': '';
				echo '<option' . $sel . ' value="' . $lessfile . '&amp;group=' . $name .'">' . $lessfile . '</option>';
			}
			?>
		</optgroup>
		<?php
		}
	} ?>
	</select>
	<?php } else { ?>
	<input type="password" name="lessmakerpassword" />
	<?php } ?>
	<div id="error"></div>
	<div id="update"></div>
</form>
<?php if ( $file && $pass ) { ?>

<form action="./" method="post" id="editors" autocomplete="off">
	<input type="hidden" value="<?php echo $file ?>" name="editfile" autocomplete="off" />
		<textarea id="less" autocomplete="off" name="less"><?php echo htmlentities( $less, ENT_QUOTES, 'UTF-8' ); ?></textarea>
		<?php
		if ( ! empty( $filegroups ) ) {
			
			foreach ( $filegroups as $name ) {
				$preless = isset( $contents[$name]['preless'] ) ? $contents[$name]['preless']: '';
				$postless = isset( $contents[$name]['postless'] ) ? $contents[$name]['postless']: '';
				?>
				<div data-group="<?php echo $name ?>">
				<textarea disabled class="preless" autocomplete="off"><?php
					echo htmlentities( $preless, ENT_QUOTES, 'UTF-8' );
				?></textarea>
				
				<textarea disabled class="postless" autocomplete="off"><?php
					echo htmlentities( $postless, ENT_QUOTES, 'UTF-8' );
				?></textarea>
				</div>
				<?php
			}
		} else { ?>
			<textarea disabled id="preless" autocomplete="off"><?php
				echo htmlentities( $preless, ENT_QUOTES, 'UTF-8' );
			?></textarea>
			<textarea disabled id="postless" autocomplete="off"><?php
				echo htmlentities( $postless, ENT_QUOTES, 'UTF-8' );
			?></textarea>
			<?php
		}
		?>
		
		<?php
		if ( ! empty( $filegroups ) ) {
			foreach ( $filegroups as $name ) {
				?>
				<textarea class="css" id="css-<?php echo $name ?>" name="css[<?php echo $name ?>]" data-group="<?php echo $name ?>" autocomplete="off"></textarea>
				<?php 
			}
		} else {
			?>
			<textarea class="css" id="css" name="css" autocomplete="off"></textarea>
			<?php
		}
		?>
</form>

<?php } ?>

<script>
jQuery( document ).ready( function( $ ) {
	var loaded = false,
		$error = $('#error'),
		$update = $('#update'),
		$css = $('textarea.css'),
		$less = $('#less'),
		$form = $('#editors'),
		$control = $('#control'),
		parser = new( less.Parser ),
		lesseditor,
		errorLine, errorText,
		cookie = 'lessmakerpassword',
		limit_id;
	
	// Handle Logout
	if ( <?php echo $logout ? 'true' : 'false'; ?> ) {
		eraseCookie( cookie );
		window.location.href = window.location.protocol + "//" + window.location.hostname + window.location.pathname
	}
	
	// Handle File Select
	$('select[name="file"]',"#control").change( fileChange );
	
	// Main Setup
	if ( $less.get(0) ) {
		lesseditor = CodeMirror.fromTextArea( $less.get(0), {
			theme: "ambiance",
			lineNumbers : true,
			matchBrackets : true,
			mode: "text/x-less",
			indentWithTabs: true,
			tabSize: 4,
			indentUnit: 4, 
			onChange: pre_compile,
		});
		compile();
		loaded = true;
		CodeMirror.commands.save = function() {
			$form.submit();
		};
		$form.submit( function( event ){
			event.preventDefault();
			compile();
			if ( ! errorText ) {
				$.ajax({  
					type: "POST",  
					url: window.location,  
					data: $(this).serialize()+'&ajaxsubmit=1',
					cache: false,
					success: saved 
				});
			} else {
				$update.html("<p>Saved unavailable until Error is fixed.</p>").show().delay(3000).fadeOut();
			}
		});
	} else {
		$control.submit( function( event ){
			createCookie( cookie, $('input[name="lessmakerpassword"]', this).val(), 1 );
		});
	}
	$update.hide();
	lesseditor.refresh();
	lesseditor.refresh();
	lesseditor.refresh();
	lesseditor.refresh();
	
	function saved( data ) {
		console.log( data );
		$update.html("<p>"+ data +"</p>").show().delay(3000).fadeOut();
	}
	function fileChange() {
		var file = $(this).val();
		if ( '' != file )
			window.location.href = window.location.protocol + "//" + window.location.hostname + window.location.pathname + "?file=" + $(this).val();
	}
	// simple rate limiter
	function pre_compile() {
		clearTimeout( limit_id );
		limit_id = setTimeout( compile, 500 );
	}
	function compile() {
		lesseditor.save();
		$css.each(function(){
			$this = $(this);
			var group = $this.data('group');
			if ( group ) {
				if ( ! $this.data( 'preless' ) )
					$this.data( 'preless', $('div[data-group="' + group + '"] .preless').val() );
				if ( ! $this.data( 'postless' ) )
					$this.data( 'postless', $('div[data-group="' + group + '"] .postless').val() );
			} else {
				$this.data( 'preless', '' );
				$this.data( 'postless', '' );
			}
			parser.parse( $this.data( 'preless' ) + lesseditor.getValue() + $this.data( 'postless' ), function ( err, tree ) {
				if ( err  ){
					doError( err, $this );
				} else {
					//try {
						$error.fadeOut();
						$this.val( tree.toCSS() );
						clearCompileError();
					//}
					//catch ( err ) {
					//	doError( err, $this );
					//}
				}
			});
		});
	}
	function doError( err, $this ) {
		if ( loaded ) {
			$error.removeClass( 'error' ).addClass( 'updated' );
			$error.show().html( "<p><strong>Warning: &nbsp; </strong>" + err.message + "</p>" );
		} else {
			$error.show().html( "<p><strong>Error: &nbsp; </strong>" + err.message + "</p>" );
		}
		clearCompileError();
		
		var line = err.line -  $this.data( 'preless' ).split("\n").length;
		
		errorLine = lesseditor.setMarker( line, '<strong>*%N%</strong>', "cm-error");
		lesseditor.setLineClass( errorLine, "cm-error");
		
		var pos = lesseditor.posFromIndex( err.index + 1 );
		var token = lesseditor.getTokenAt( pos );
		var start = lesseditor.posFromIndex( err.index );
		var end = lesseditor.posFromIndex( err.index + token.string.length )
		errorText = lesseditor.markText( start, end, "cm-error");
	}
	function clearCompileError() {
		if ( errorLine ) {
			lesseditor.clearMarker( errorLine );
			lesseditor.setLineClass( errorLine, null );
			errorLine = false;
		}
		if ( errorText ) errorText.clear();
		errorText = false;
	}
	
	// http://www.quirksmode.org/js/cookies.html
	function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}
	
	function readCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}
	
	function eraseCookie(name) {
		createCookie(name,"",-1);
	}
});
</script>
</body>
</html>