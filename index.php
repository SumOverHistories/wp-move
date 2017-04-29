<?php
require_once("includes/wpmove.php");
wpmove::init();
?>
	
	<!doctype html>
	
	<html lang="en">
	<head>
		<meta charset="utf-8">
		
		<title>Move your WordPress installation</title>
		<meta name="description" content="Move your WordPress installation">
		<meta name="author" content="Daniel Hani">
		
		<link rel="stylesheet" href="css/style.css?v=1.0">
	
	</head>
	
	<body>
	<h1>WP-Move - Move your Wordpress installation to another Server/Domain</h1>
	<div class="explanation">
		<span class="title">Explanation</span>
		<span class="description">1. Don't put 'http://' or 'https://' in front of the domain. Use the checkbox instead</span>
		<span class="description">2. You have to manually add 'www.' to the domain.</span>
	</div>

	<?php if (!empty(wpmove::$aErrors)) : ?>
		<div class="errors-headline">Please fix the following errors before proceeding:</div>
		<?php foreach (wpmove::$aErrors as $aError) : ?>
			<div class="error"><?php echo $aError; ?></div>
		<?php endforeach; ?>
	<?php else : ?>
		<table class="blogs">
			<tr>
				<th>URL</th>
				<th>HTTPS</th>
			</tr>
			<?php foreach (wpmove::$aBlogs as $oBlog) : ?>
				<tr>
					<td>
						<?php echo $oBlog['url']; ?>
					</td>
					<td>
						<?php echo $oBlog['https']; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php if (wpmove::$bUpdate === false) : ?>
			<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
				<fieldset>
					<label for="newdomain">New Domain:</label>
					<input id="newdomain" type="text" name="newdomain"><br>
					<label for="https">Add HTTPS:</label>
					<input id="https" type="checkbox" name="https"><br>
					<input class="button" type="submit" value="Submit">
				</fieldset>
			</form>
		<?php else : ?>
			<table class="blogs">
				<tr>
					<th>Table</th>
					<th>Number of Changes</th>
				</tr>
				<?php foreach (wpmove::$aAffectedRows as $sTable => $sChanges) : ?>
					<tr>
						<td><?php echo $sTable ?></td>
						<td><?php echo $sChanges ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<div class="message">The queries took: <?php echo wpmove::$iExecTime; ?> seconds.</div>
			<div><a class="button" href="/wp-move/index.php">Back</a></div>
		<?php endif; ?>
	<?php endif; ?>
	
	</body>
	</html>

<?php wpmove::closeConnection(); ?>