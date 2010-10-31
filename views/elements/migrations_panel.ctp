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
		if (empty($migration['map'])) {
			continue;
		}
		$migration = array_reverse($migration);
		?>
		<br/>
		<h4><?php echo Inflector::humanize($type); ?></h4>
		<?php
		foreach ($migration['map'] as $index => $info) {
			if (empty($info['migrated'])) {
				$status = array(
					'color' => '#fcc',
					'image' => 'http://cakephp.org/img/test-fail-icon.png');
			} else {
				$status = array(
					'color' => '#cfc',
					'image' => 'http://cakephp.org/img/test-pass-icon.png');
			}
			?>
			<div style="background: <?php echo $status['color']; ?> url(<?php echo $status['image']; ?>) 5px 3px no-repeat; border-bottom: solid #ccc 1px; line-height: 1.75em; padding-left: 27px;">
				<?php echo sprintf('[%s] %s', $info['version'], $info['name']); ?>
			</div>
			<?php
		}
	}
	?>
</div>