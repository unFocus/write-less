<?php
/*
 * Config Section
 */
$password = 'CHANGEME!';
$jqueryurl = 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js';
$lessjsurl = 'http://cdnjs.cloudflare.com/ajax/libs/less.js/1.3.0/less-1.3.0.min.js';

// add a list of less files that will compiled as one (in order).
$lessgroup = array();

/* End of Config Section */

$pass = ( $password === $_COOKIE['lessmakerpassword'] );
chdir('../');
$lessfiles = glob( '*.less' );
$cssfiles = glob( '*.css' );
if ( $_REQUEST['ajaxsubmit'] && in_array( $_REQUEST['editfile'], $lessfiles ) && $pass ) {
	$newless = stripslashes( $_REQUEST['less'] );
	$newcss = stripslashes( $_REQUEST['css'] );
	$lessfile = $_REQUEST['editfile'];
	$cssfile = str_replace( '.less', '.css', $lessfile );
	if ( in_array( $lessfile, $lessgroup ) )
		$cssfile = 'lessgroup.css';
	write_the_file( $lessfile, $newless );
	write_the_file( $cssfile, $newcss );
	exit();
}

// filters out the grouped less files - they'll be added back later, as optgroups.
foreach ( $lessfiles as $i => $file ) {
	if ( in_array( $file, $lessgroup ) )
		unset( $lessfiles[ $i ] );
}

function write_the_file( $filename, $data ) {
	if ( file_exists( $filename ) ) {
		if ( is_writable( $filename ) ) {
			if ( $handle = fopen( $filename, 'wb' ) ) {
				if ( 0 < fwrite( $handle, $data ) ) {
					echo "Successfully wrote to file ($filename)";
				} else if ( false === fwrite( $handle, $data ) ) {
					echo "Cannot write to file ($filename)";
				} else if ( 0 === fwrite( $handle, $data ) ) {
					echo "wrote 0 bytes to ($filename)";
				}
				fclose( $handle );
				
			} else {
				echo "cannot open file ($filename)";
			}
		} else {
			echo "file ($filename) is not writable";
		}
	} else {
		if ( $handle = fopen( $filename, 'wb' ) ) {
			echo "Successfully created file ($filename)";
			if ( 0 < fwrite( $handle, $data ) ) {
				echo "Successfully wrote to file ($filename)";
			} else if ( false === fwrite( $handle, $data ) ) {
				echo "Cannot write to file ($filename)";
			} else if ( 0 === fwrite( $handle, $data ) ) {
				echo "wrote 0 bytes to ($filename)";
			}
			fclose( $handle );
			
		} else {
			echo "cannot open file ($filename)";
		}
	}
}
$file = $_REQUEST['file'];
$logout = isset( $_REQUEST['logout'] );
$less = '';
$error = false;
$preless = '';
$postless = '';
if ( in_array($file, $lessgroup ) )
{
	$pre = true;
	foreach( $lessgroup as $gfile ) {
		if ( $pre ) {
			if ( $gfile == $file ) {
				$pre = false;
				continue;
			}
			$preless .= file_get_contents( $gfile );
		}
		else
			$postless .= file_get_contents( $gfile );
	}
	$less = file_get_contents( $file );
}
else
if ( in_array( $file, $lessfiles ) )
	$less = file_get_contents( $file );
else
	$error = true;
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
body {
	height: 100%;
	margin: 0;
	padding: 0;
}
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
	height: 95%;
}
.CodeMirror-scroll {
    height: auto;
    min-height: 50px;
	max-height: 100%;
}
#files {
	position: fixed;
	top: 0; right: 0; bottom: auto; left: 0;
	height: 40px;
	overflow: auto;
	margin: 0;
	background-color: white;
	z-index: 100;
}
#editors {
	position: absolute;
	top: 40px; right: 0; bottom: 0; left: 0;
	height: auto;
	overflow: auto;
	margin: 0;
    min-height: 300px;
}
</style>
</head>
<body>
<?php if ( $file && $pass ) { ?>

<form action="./" method="post" id="editors" autocomplete="off">
	<input type="hidden" value="<?php echo $file ?>" name="editfile" autocomplete="off" />
	<div style="overflow: hidden; height: 100%">
		<div style="width: 49%; float: left; overflow: hidden; margin-right: 2%;">
		<textarea style="display:none;" disabled id="preless" autocomplete="off"><?php
			echo htmlentities( $preless, ENT_QUOTES, 'UTF-8' );
		?></textarea>
		<textarea id="less" name="less" autocomplete="off"><?php echo htmlentities( $less, ENT_QUOTES, 'UTF-8' ); ?></textarea>
		<textarea style="display:none;" disabled id="postless" autocomplete="off"><?php
			echo htmlentities( $postless, ENT_QUOTES, 'UTF-8' );
		?></textarea>
		</div>
		<div style="width: 49%; float: left; overflow: hidden;">
		<div id="error"></div>
		<textarea id="css" name="css" autocomplete="off"><?php echo htmlentities( $css, ENT_QUOTES, 'UTF-8' ); ?></textarea>
		</div>
	</div>
</form>

<?php } ?>

<form action="./" method="post" id="control" autocomplete="off">
	<?php if ( $pass ) { ?>
	<select name="file" autocomplete="off">
	 <option value="">Select a file</option>
	<?php 
	foreach ( $lessfiles as $lessfile ) {
		$sel = $lessfile == $file ? ' selected="selected"': '';
		echo '<option' . $sel . '>' . $lessfile . '</option>';
	}
	?>
	<?php if ( count( $lessgroup ) > 0 ): ?>
	<optgroup label="lessgroup.less">
		<?php 
		foreach ( $lessgroup as $lessfile ) {
			$sel = $lessfile == $file ? ' selected="selected"': '';
			echo '<option' . $sel . '>' . $lessfile . '</option>';
		}
		?>
	</optgroup>
	</select>
	<?php endif; ?>
	<?php } else { ?>
	<input type="password" name="lessmakerpassword" />
	<?php } ?>
</form>

<script>
jQuery( document ).ready( function( $ ) {
	var loaded = false,
		$error = $('#error'),
		$css = $('#css'),
		$less = $('#less'),
		$form = $('#editors'),
		$control = $('#control'),
		parser = new( less.Parser ),
		preless = (document.getElementById("preless") || {value:""}).value,
		postless = (document.getElementById("postless") || {value:""}).value,
		lesseditor,
		csseditor,
		errorLine, errorText,
		lessElem, cssElem,
		cookie = 'lessmakerpassword';
	lessElem = document.getElementById("less");
	cssElem = document.getElementById("css");
	$('select[name="file"]',"#control").change( fileChange );
	function fileChange() {
		var file = $(this).val();
		if ( '' != file )
			window.location.href = window.location.protocol + "//" + window.location.hostname + window.location.pathname + "?file=" + $(this).val();
	}
	if ( <?php echo $logout ? 'true' : 'false'; ?> ) {
		eraseCookie( cookie );
		window.location.href = window.location.protocol + "//" + window.location.hostname + window.location.pathname
	}
	if ( lessElem && cssElem ) {
		lesseditor = CodeMirror.fromTextArea( lessElem, {
			theme: "ambiance",
			lineNumbers : true,
			matchBrackets : true,
			mode: "text/x-less",
			indentWithTabs: true,
			tabSize: 4,
			indentUnit: 4, 
			onChange: pre_compile,
		});
		csseditor = CodeMirror.fromTextArea( cssElem, {
			theme: "ambiance",
			lineNumbers : true,
			matchBrackets : true,
			indentWithTabs: true,
			indentWithTabs: true,
			tabSize: 4,
			indentUnit: 4, 
			mode: "css",
		});
		compile();
		loaded = true;
		csseditor.refresh();
		lesseditor.refresh();
		CodeMirror.commands.save = function() {
			$form.submit();
		};
		$form.submit( function( event ){
			event.preventDefault();
			compile();
			$.ajax({  
				type: "POST",  
				url: window.location,  
				data: $(this).serialize()+'&ajaxsubmit=1',
				cache: false,
				success: saved 
			});
		});
	} else {
		$control.submit( function( event ){
			createCookie( cookie, $('input[name="lessmakerpassword"]', this).val(), 1 );
		});
	}
	function saved( data ) {
		//console.log( data );
	}
	// simple rate limiter
	var limit_id;
	function pre_compile() {
		clearTimeout( limit_id );
		limit_id = setTimeout( compile, 500 );
	}
	function compile() {
		lesseditor.save();
		parser.parse( preless + lesseditor.getValue() + postless, function ( err, tree ) {
			if ( err  ){
				doError( err );
			} else {
				try {
					$error.hide();
					csseditor.setValue( tree.toCSS() );
					csseditor.save();
					$css.next( '.CodeMirror' ).show();
					csseditor.refresh();
					clearCompileError();
				}
				catch ( err ) {
					doError( err );
				}
			}
		});
	}
	function doError( err ) {
		//console.dir( err );
		$css.next( '.CodeMirror' ).hide();
		if ( loaded ) {
			$error.removeClass( 'error' ).addClass( 'updated' );
			$error.show().html( "<p><strong>Warning: &nbsp; </strong>" + err.message + "</p>" );
		} else {
			$error.show().html( "<p><strong>Error: &nbsp; </strong>" + err.message + "</p>" );
		}
		clearCompileError();
		
		var line = err.line - preless.split("\n").length;
		
		errorLine = lesseditor.setMarker( line, '<strong>*%N%</strong>', "cm-error");
		lesseditor.setLineClass( errorLine, "cm-error");
		
		var pos = lesseditor.posFromIndex( err.index + 1 );
		var token = lesseditor.getTokenAt( pos );
		var start = lesseditor.posFromIndex( err.index );
		var end = lesseditor.posFromIndex( err.index + token.string.length )
		errorText = lesseditor.markText( start, end, "cm-error");
		
		csseditor.setValue( "" );
		csseditor.save();
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