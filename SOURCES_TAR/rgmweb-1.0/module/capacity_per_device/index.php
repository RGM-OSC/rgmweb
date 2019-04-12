<?php
/*
#########################################
#
# Copyright (C) 2016 EyesOfNetwork Team
# DEV NAME : Quentin HOARAU
# VERSION : 5.1
# APPLICATION : eonweb for eyesofnetwork project
#
# LICENCE :
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
#########################################
*/

include("../../header.php");
include("../../side.php");

?>

<div id="page-wrapper">
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?php echo getLabel("label.capacity_per_device.title"); ?></h1>
		</div>
	</div>
	
	<?php
	// errors management
	if(count($_GET)>0){
		$error = false;
		//print_r($_GET);
		# --- Retrieve the selected host id 
		if(isset($_GET['host_list'])){
			$graphlocal_host = $_GET['host_list'];
		}else{
			message(0," : ".getLabel("message.no_host_value"),"critical");
			$error = true;
		}	
	}
	?>
	
	<div class="row">
		<form method='GET'>
			<div class="form-group col-md-6">
				<label>Equipment :</label><?php get_host_listbox_from_nagios();?><br>
			</div>
		</form>
	</div>
	
	<?php
		if(count($_GET)>0 && $error == false)
		{
			if(isset($graphlocal_host)){
				//echo "DEBUG: ".$graphlocal_host."<br>";
				# --- Print the graph
				//echo "<div class=\"embed-responsive\">";
				echo "		<iframe class=\"iframe\" frameborder=\"0\" scrolling=\"no\" style=\"top: 210px; left: 250px; height: 80%; width: 80%; display: inline;\" src=\"/grafana/dashboard/script/histou.js?host=$graphlocal_host&refresh=30s&height=250&kiosk\" ></iframe>";
				//echo "</div>";
			}
		}
	?>
</div>

<?php include("../../footer.php"); ?>
