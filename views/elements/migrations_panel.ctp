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
	foreach ($content as $type => $migration) {
		$migration = array_reverse($migration);
		?>
		<h4><?php echo Inflector::humanize($type); ?></h4>
		<?php
		foreach ($migration['map'] as $index => $info) {
			$color = $info['migrated'] === true ? '#cfc' : '#fcc';
			?>
			<div style="background-color: <?php echo $color; ?>; border-bottom: solid #ccc 1px; line-height: 1.75em; padding-left: 0.5em;">
				<?php echo '[' . $info['version'] . '] ' . $info['name']; ?>
			</div>
			<?php
		}
	}
	?>
</div>