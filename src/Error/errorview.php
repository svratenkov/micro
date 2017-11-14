<meta charset="UTF-8">
<style type="text/css">
.simple_debug { background: #ddd; font-family:sans-serif; text-align: left; color: #111; }
.simple_debug h1, .simple_debug h2 { margin: 0; padding: 1em; font-size: 1em; font-weight: normal; background: #911; color: #fff; }
.simple_debug h2 { background: #222; }
.simple_debug h3 { margin: 0; padding: 0.4em 0 0; font-size: 1em; font-weight: normal; }
.simple_debug p { margin: 0; padding: 0.2em 0; }
.simple_debug strong { color: blue; }
.simple_debug pre { overflow: auto; white-space: pre-wrap; }
.simple_debug table { width: 100%; display: block; margin: 0 0 0.4em; padding: 0; border-collapse: collapse; background: #fff; }
.simple_debug table td { border: solid 1px #ddd; text-align: left; vertical-align: top; padding: 0.4em; }
.simple_debug div.content { padding: 0.4em 1em 1em; overflow: hidden; }
.simple_debug pre.source { margin: 0 0 1em; padding: 0.4em; background: #fff; border: dotted 1px #b7c680; line-height: 1.2em; }
.simple_debug pre.source span.line { display: block; }
.simple_debug pre.source span.highlight { background: #f0eb96; }
.simple_debug pre.source span.line span.number { color: #666; }
.simple_debug ol.trace { display: block; margin: 0 0 0 2em; padding: 0; list-style: decimal; }
.simple_debug ol.trace li { margin: 0; padding: 0; }
.simple_debug .string { color: red; }
.simple_debug .integer { color: green; }
.simple_debug .key_str { color: darkred; }
.simple_debug .key_int { color: darkgreen; }
.simple_debug .toggler { text-decoration: underline; cursor: pointer; }
.simple_debug .collapsed { display: none; }
</style>

<script type="text/javascript">
function toggle(id)
{
	elem = document.getElementById(id);
	elem.style.display = elem.style.display == 'block' ? 'none' : 'block';
}
</script>

<div class="simple_debug">
	<h1><span class="type"><?= get_class($e) ?> [ <?= $e->desc ?> ]:</span> <span class="message"><?= $e->getMessage() ?></span></h1>

	<div class="content">
		<p><span class="file"><?= $file ?> [ <?= $e->getLine() ?> ]</span></p>

		<?= $source ?>

		<ol class="trace">
		<?php foreach ($trace as $i => $step): ?>
			<li>
				<p>
					<span class="file">
						<?php if ($step['file']):
							$source_id = 'source'.$i; ?>
							<span class="toggler" onclick="toggle('<?= $source_id ?>')"><?= $step['file'] ?> [ <?= $step['line'] ?> ]</span>
						<?php else: ?>
							{<?= 'PHP internal call' ?>}
						<?php endif; ?>
					</span>
					&raquo;
					<?= $step['function'] ?>(
						<?php if ($step['args']):
							$args_id = 'args'.$i; ?>
							<span class="toggler" onclick="toggle('<?= $args_id ?>')"><?= 'arguments ['.count($step['args']).']' ?></span>
						<?php endif; ?>
					)
				</p>

				<?php if (isset($args_id)): ?>
					<div id="<?= $args_id ?>" class="collapsed">
						<table cellspacing="0">
						<?php foreach ($step['args'] as $name => $arg): ?>
							<tr>
								<td><code><?= $name ?></code></td>
								<td><pre><?= $arg ?></pre></td>
							</tr>
						<?php endforeach; ?>
						</table>
					</div>
				<?php endif; ?>

				<?php if (isset($source_id)): ?>
					<pre id="<?= $source_id ?>" class="source collapsed">
						<code><?= $step['source'] ?></code>
					</pre>
				<?php endif; ?>
			</li>
			<?php unset($args_id, $source_id); ?>
		<?php endforeach; ?>
		</ol>
	</div>

	<!-- Environment -->
	<h2><span class="toggler" onclick="toggle('environment')">Environment</span></h2>
	<div id="environment" class="content collapsed">
		<h3><span class="toggler" onclick="toggle('includes')">Included files</span> (<?= count($included) ?>)</h3>
		<div id="includes" class="collapsed">
			<table cellspacing="0">
				<?php foreach ($included as $file): ?>
				<tr>
					<td><code><?= $file ?></code></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<h3><span class="toggler" onclick="toggle('classes')">Declared classes</span> (<?= count($classes) ?>)</h3>
		<div id="classes" class="collapsed">
			<table cellspacing="0">
				<?php foreach ($classes as $class): ?>
				<tr>
					<td><code><?= $class; ?></code></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<h3><span class="toggler" onclick="toggle('extensions')">Loaded extensions</span> (<?= count($extensions) ?>)</h3>
		<div id="extensions" class="collapsed">
			<table cellspacing="0">
				<?php foreach ($extensions as $file): ?>
				<tr>
					<td><code><?= $file ?></code></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

<?php
		foreach ($php_vars as $name => $var):
			if (! $var) continue;
			$id = 'php_'.strtolower($name);
?>
			<h3><span class="toggler" onclick="toggle('<?= $id ?>')"><?= $name ?></span> (<?= sizeof($var) ?>)</h3>
			<div id="<?= $id ?>" class="collapsed">
				<table cellspacing="0">
				<?php foreach ($var as $key => $value): ?>
					<tr>
						<td><code><?= $key; ?></code></td>
						<td><pre><?= $value; ?></pre></td>
					</tr>
				<?php endforeach; ?>
				</table>
			</div>
		<?php endforeach; ?>
	</div>
</div>
