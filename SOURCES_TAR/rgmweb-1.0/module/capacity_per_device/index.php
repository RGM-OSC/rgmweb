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
		if(isset($_GET['period'])){
			$graphlocal_period = $_GET['period'];
		}else{
			//message(0," : ".getLabel("message.no_host_value"),"critical");
			//$error = true;
		}		
	}
	?>
	
	<div class="row">
		<form method='GET'>
			<div class="form-group col-md-6">
				<label>Equipment :</label>
				<?php get_host_listbox_from_nagios();?><br>
			</div>
		</form>
	</div>
	
	<?php
		if(count($_GET)>0 && $error == false)
		{
			if(isset($graphlocal_host)){
				# --- Print the graph
				
				echo "<iframe frameborder=\"0\" scrolling=\"no\" style=\"height: 600px; width: 100%; display: inline-block;\" src=\"/grafana/dashboard/script/histou.js?host=$graphlocal_host&refresh=30s&height=250&kiosk\" ></iframe>";

				//for service in "select distinct("service") FROM (select "service","value" from metrics where "host" = 'localhost.localdomain');"
				//for grach in "select distinct("performanceLabel") FROM (select "performanceLabel","service","value" from metrics where "host" = 'localhost.localdomain' and "service" = 'load');"

				//curl -G 'http://localhost:8086/query?pretty=true' --data-urlencode "db=nagflux" --data-urlencode "q=select distinct(\"performanceLabel\") FROM (select \"performanceLabel\",\"service\",\"value\" from metrics where \"host\" = 'localhost.localdomain' and \"service\" = 'load')"

				// DETERMINE SERVICES
				$influx_query_service = urlencode("select distinct(\"service\") FROM (select \"service\",\"value\" from metrics where \"host\" = '".$graphlocal_host."')");
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_TIMEOUT, 1); //timeout in seconds
				curl_setopt($ch, CURLOPT_URL,"http://127.0.0.1:8086/query?db=nagflux&q=$influx_query_service");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				$json_data  =  json_decode(curl_exec ($ch),true);
				curl_close ($ch);
				
				$list_service = array();
				foreach ($json_data['results'][0]['series'][0]['values'] as $key1) {
					array_push($list_service, $key1[1]);
				}


				foreach ($list_service as &$service_influx) {
    					//echo "Service:".$service_influx."</br>";

    					$graph_height=285;

    					// DETERMINE NUMBER OF GRAPH
						$influx_query_graph = urlencode("select distinct(\"performanceLabel\") FROM (select \"performanceLabel\",\"service\",\"value\" from metrics where \"host\" = '".$graphlocal_host."' and \"service\" = '".$service_influx."')");
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_TIMEOUT, 1); //timeout in seconds
						curl_setopt($ch, CURLOPT_URL,"http://127.0.0.1:8086/query?db=nagflux&q=$influx_query_graph");
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
						$json_data  =  json_decode(curl_exec ($ch),true);
						curl_close ($ch);

						$list_graph = array();
						foreach ($json_data['results'][0]['series'][0]['values'] as $key1) {
							array_push($list_graph, $key1[1]);
						}

						$graph_height = sizeof($list_graph) * $graph_height;
						echo "<iframe frameborder=\"0\" scrolling=\"no\" style=\"height: ".$graph_height."px; width: 100%; display: inline-block;\" src=\"/grafana/dashboard/script/histou.js?host=$graphlocal_host&service=".$service_influx."&refresh=30s&height=250&kiosk\" ></iframe>";

				}

			}
		}
	?>
</div>

<?php include("../../footer.php"); ?>
