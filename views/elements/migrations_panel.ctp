<?php
/**
 * Copyright 2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<h2><?php __d('migrations', 'Migration Status') ?></h2>
<div class="code-table">
	<?php
	foreach ($content as $plugin => $migrationSet):
		$pluginName = Inflector::humanize($plugin);
		?>
		<h4><?php echo $pluginName; ?></h4>
		<?php
		$migrationSet = array_reverse($migrationSet);
		//echo $toolbar->makeNeatArray($migrationSet);
		foreach ($migrationSet as $index => $info):
			$color = $info['migrated'] === true ? '#cfc' : '#fcc';
			?>
			<div style="background-color: <?php echo $color; ?>; border-bottom: solid #ccc 1px; line-height: 1.75em; padding-left: 0.5em;">
				<?php echo '[' . $info['id'] . '] ' . $info['name']; ?>
			</div>
			<?php
		endforeach;
	endforeach;
	?>
</div>