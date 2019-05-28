<?php
/* RGM admin module for asset deployment
 * Copyright (C) 2019 RGM Team
 * creator : Eric Belhomme
 *
 * LICENCE :
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
include("../../header.php");
include("../../side.php");
include("../../include/Parsedown.php");

$id_item=0;

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'display':
			$id_item = $_GET['id'];
			break;
	}
}

function admDist_displayItems() {
	global $database_rgmweb;
	$stmt = sqlrequest($database_rgmweb,"SELECT `id`, `name` FROM ol_items ORDER BY `name`");
	while ($item_raw = mysqli_fetch_array($stmt)) {
		echo '<a href="/module/admin_distrib/index.php?&action=display&id=' . $item_raw['id'] . '" class="list-group-item">' . $item_raw['name'];
		$stmt2 = sqlrequest($database_rgmweb,"SELECT oltg.name AS tag FROM ol_items_tags olit INNER JOIN ol_tags oltg ON oltg.id = olit.id_tags WHERE olit.id_item = '" . $item_raw['id'] ."'");
		while ($tag_raw = mysqli_fetch_array($stmt2)) {
			echo '<span class="badge">' . $tag_raw['tag'] . '</span>';
		}
		echo "</a>\n";
	}

}

function admDist_displayItemDescription() {
	global $id_item;
	global $database_rgmweb;
	global $path_distrib;
	$stmt = sqlrequest($database_rgmweb,"SELECT `filename`, `name` FROM ol_items WHERE id = '" . $id_item . "'");
	$sql_raw = mysqli_fetch_array($stmt);
	if ($sql_raw) {
		if (file_exists($path_distrib . '/install/' . $sql_raw['filename'])) {
			// /srv/rgm/distrib/install/win_metricbeat.md
			$md = new Parsedown();
			echo $md->text(file_get_contents($path_distrib . '/install/'. $sql_raw['filename']));
		} else {
			echo '<p>no description available for ' . $sql_raw['name'] . "</p>\n"; 
		}
	}
}

function admDist_displayItemCommand() {
	global $id_item;
	global $database_rgmweb;
	$stmt = sqlrequest($database_rgmweb,"SELECT `command`, `shell` FROM ol_items WHERE id = '" . $id_item . "'");
	$sql_raw = mysqli_fetch_array($stmt);
	if ($sql_raw) {
		echo '<div class="row"><div class="col-md-1">' . getLabel('label.admin_distrib.command.shell') . '</div><div class="col-md">';
		if ($sql_raw['shell'] != '') {
			echo $sql_raw['shell'];
		}
		echo '</div></div>' . "\n" . '<div class="row"><div class="col-md-1">' . getLabel('label.admin_distrib.command.command') . '</div><div class="col-md">';
		echo "\n<code>" . $sql_raw['command'] . "</code>\n" . '</div></div>' . "\n";
	}
}

?>

<div id="page-wrapper">

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?php echo getLabel('label.admin_distrib.title'); ?></h1>
		</div>
	</div>
	<div><?php echo getLabel('label.admin_distrib.heading'); ?>	
	</div>

	<div class="row">
		<div class="col-md-4">
			<h4><?php echo getLabel('label.admin_distrib.title.list_deployments'); ?></h4>
			<div class="list-group">
				<?php admDist_displayItems(); ?>
			</div>
		</div>	
		<div class="col-md-8" style="height: 600px; overflow-y:scroll">
			<h4><?php echo getLabel('label.admin_distrib.title.item_description'); ?></h4>
			<?php admDist_displayItemDescription(); ?>
		</div>	
	</div>	
	<div class="row">
		<h4><?php echo getLabel('label.admin_distrib.title.item_command'); ?></h4>
		<?php admDist_displayItemCommand(); ?>
	</div>
</div>


<?php include("../../footer.php"); ?>
