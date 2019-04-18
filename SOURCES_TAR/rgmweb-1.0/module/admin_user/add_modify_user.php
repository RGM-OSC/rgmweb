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

	<?php
		/********************************************************
		*		FUNCTIONS DECLARATIONS			                *
		********************************************************/

		// Retrieve Group Information
		function retrieve_user_info($user_id)
		{
			global $database_eonweb;
			return sqlrequest("$database_eonweb","SELECT user_name, user_descr, user_email, group_id, user_passwd, user_type, user_location, user_limitation, user_language  FROM users WHERE user_id='$user_id'");
		}

		// Display user language selection  
		function GetUserLang() {

			global $database_eonweb;
			global $user_id;
			global $path_languages;

			// definition of variables and Research language files
			$path_label_lang = "label.admin_user.user_lang_"; 
			$files = array('en');
			$handler = opendir($path_languages);

			while ($file = readdir($handler)) {
				if(preg_match('#messages-(.+).json#', $file, $matches)){
					$files[] = $matches[1];
				}
			}

			closedir($handler);
			$files = array_filter($files);
			array_unshift($files,"0");
			$files = array_unique($files);

			// creation of a select and catch values
			$langtmp = mysqli_result(sqlrequest("$database_eonweb","SELECT user_language FROM users WHERE user_id='".$user_id."'"),0);
			$res = '<select class="form-control" name="user_language">';
			foreach($files as $v) {
				if($v == $langtmp){
					$res.="<option value='".$v."' selected=selected>".getLabel($path_label_lang.$v)."</option>";
				}
				else{
					$res.="<option value='".$v."'>".getLabel($path_label_lang.$v)."</option>";
				}
			}
			$res .= '</select>';

			return $res;
		}
		
		//--------------------------------------------------------

		// Update User Information & Right
		function update_user($user_id, $user_name, $user_descr, $user_email, $user_group, $user_password1, $user_password2 ,$user_type, $user_location, $user_limitation, $old_group_id, $old_name, $create_user_in_nagvis, $nagvis_role_id, $user_language)
		{
			global $database_host;
			global $database_username;
			global $database_password;

			global $database_eonweb;
			global $database_lilac;
			global $database_nagvis;
			global $path_eonweb;
			global $dir_imgcache;

			// Check if user exist
			if($user_name!=$old_name)	
				$user_exist=mysqli_result(sqlrequest("$database_eonweb","SELECT count('user_name') from users where user_name='$user_name';"),0);
			else
				$user_exist=0;

			// Check user_descr
			if($user_descr=="")
				$user_descr=$user_name;

			$passwd_temp='';
			if ($user_password1 != "" && $user_password1 != null && $user_password1 != $user_password2 && $user_password1 != 'abcdefghijklmnopqrstuvwxyz') {
				message(8," : Passwords do not match",'error');
				return;
			} elseif ($user_password1 != "" && $user_password1 != null && $user_password1 == $user_password2 && $user_password1 != "abcdefghijklmnopqrstuvwxyz") {
				$passwd_temp = md5($user_password1);
			}

			if (($user_name != "") && ($user_name != null) && ($user_id != null) && ($user_id != "") && ($user_exist == 0)) {

				$eonweb_groupname=mysqli_result(sqlrequest("$database_eonweb","SELECT group_name FROM groups WHERE group_id='$user_group'"),0,"group_name");			
				$eonweb_oldgroupname=mysqli_result(sqlrequest("$database_eonweb","SELECT group_name FROM groups WHERE group_id='$old_group_id'"),0,"group_name");			

				// Update into eonweb
				sqlrequest("$database_eonweb","UPDATE users set user_name='$user_name', user_descr='$user_descr', user_email='$user_email', "
					."group_id='$user_group', user_type='$user_type', user_location='$user_location' ,user_limitation='$user_limitation', "
					."user_language='$user_language' WHERE user_id = '$user_id'");
			
				if ($passwd_temp != '') {
					sqlrequest("$database_eonweb","UPDATE users SET user_passwd='$passwd_temp' WHERE user_id = '$user_id'");
				}
			
				// Update into lilac
				$lilac_userid=mysqli_result(sqlrequest("$database_lilac","SELECT id FROM nagios_contact WHERE name='$old_name'"),0,"id");
				$lilac_groupid=mysqli_result(sqlrequest("$database_lilac","SELECT id FROM nagios_contact_group WHERE name='$eonweb_groupname'"),0,"id");
				$lilac_oldgroupid=mysqli_result(sqlrequest("$database_lilac","SELECT id FROM nagios_contact_group WHERE name='$eonweb_oldgroupname'"),0,"id");
				sqlrequest("$database_lilac","UPDATE nagios_contact set name='".str_replace(","," ",$user_name)."', alias='$user_descr', email='$user_email' WHERE name ='$old_name'");
				sqlrequest("$database_lilac","DELETE from nagios_contact_group_member WHERE contact='$lilac_userid' and contactgroup='$lilac_groupid'");
				sqlrequest("$database_lilac","DELETE from nagios_contact_group_member WHERE contact='$lilac_userid' and contactgroup='$lilac_oldgroupid'");
				if($lilac_groupid!="" and $lilac_userid!="" and $user_limitation!="1")
					sqlrequest("$database_lilac","INSERT into nagios_contact_group_member (contactgroup,contact) values('$lilac_groupid','$lilac_userid')");

				// update user into nagvis
				// this is nagvis default salt for password encryption security
				$nagvis_salt = '29d58ead6a65f5c00342ae03cdc6d26565e20954';
				$nagvis_userid=mysqli_result(sqlrequest("$database_nagvis","SELECT userId FROM users WHERE name='".$_POST["user_name_old"]."'"),0,"userId");
				if ($nagvis_userid == 0) {
					// no user found in nagvis, so if checkbox is checked, we create
					if ($create_user_in_nagvis=="yes") {
						sqlrequest("$database_nagvis", "INSERT INTO users (name) VALUES ('$user_name')");
					}
				}
				$nagvis_userid=mysqli_result(sqlrequest("$database_nagvis","SELECT userId FROM users WHERE name='".$_POST["user_name_old"]."'"),0,"userId");
				if ($nagvis_userid > 0) {
					if ($create_user_in_nagvis=="yes") {
						sqlrequest("$database_nagvis", "UPDATE users SET name = '$user_name' WHERE userId = $nagvis_userid");
						if ($passwd_temp != '') {
							sqlrequest("$database_nagvis", "UPDATE users SET password = '".sha1($nagvis_salt.$passwd_temp)."' WHERE userId = $nagvis_userid");
						}
						if (mysqli_result(sqlrequest("$database_nagvis","SELECT count(*) AS found FROM users2roles WHERE userId = $nagvis_userid"),0,"found") == 0 ) {
							sqlrequest("$database_nagvis", "INSERT INTO users2roles (userId, roleId) VALUES ('$nagvis_userid', '$nagvis_role_id')");
						} else {
							sqlrequest("$database_nagvis", "UPDATE users2roles SET roleId = $nagvis_role_id WHERE userId = $nagvis_userid");
						}
					} else { // delete in nagvis
						sqlrequest("$database_nagvis", "DELETE FROM users WHERE userId = ".$nagvis_userid);
						sqlrequest("$database_nagvis", "DELETE FROM users2roles WHERE userId = ".$nagvis_userid);
					}
				}

				// logging action
				logging("admin_user","UPDATE : $user_id $user_name $user_descr $user_limitation $user_group $user_type $user_location");

				// renaming files
				if($user_name!=$old_name){
					foreach (glob("$path_eonweb/$dir_imgcache/$old_name*.png") as $filename)
						unlink($filename);
					if(file_exists("$path_eonweb/$dir_imgcache/$old_name-ged.xml"))
						rename("$path_eonweb/$dir_imgcache/$old_name-ged.xml","$path_eonweb/$dir_imgcache/$user_name-ged.xml");
				}
				message(8," : User updated",'ok');
			}
			elseif ($user_exist != 0 && $user_name!=$old_name)
				message(8," : User $user_name already exists",'warning');
			else
				message(8," : User name can not be empty",'warning');
		}

		/********************************************************
		*		END OF FUNCTIONS DECLARATIONS		*
		********************************************************/


		// Global parameter
		global $database_eonweb;
		global $database_lilac;

		// Get parameter
		$user_change_passord = retrieve_form_data("user_change_passord",null);
		$user_id = retrieve_form_data("user_id",null);

		// Secure the change password
		if (($user_change_passord != null) && ($user_id != $_COOKIE['user_id']))
			message(0,"No Access Right","critical");

		$user_location = retrieve_form_data("user_location","");
		$user_location = ldap_escape($user_location);
		$user_email = retrieve_form_data("user_mail","");
		$user_descr = retrieve_form_data("user_descr","");
		$user_descr = htmlspecialchars($user_descr, ENT_QUOTES);
		$user_group = retrieve_form_data("user_group","");
		$user_type = retrieve_form_data("user_type","");
		$user_limitation = retrieve_form_data("user_limitation","");
		$user_language = retrieve_form_data("user_language","");
		$old_group_id = mysqli_result(sqlrequest($database_eonweb,"select group_id from users where user_id='$user_id'"),0,"group_id");
		$old_name = retrieve_form_data("user_name_old","");

		$create_user_in_nagvis = retrieve_form_data("create_user_in_nagvis","");
		$nagvis_role_id = retrieve_form_data("nagvis_group","");

		if($user_type=="1"){
			$result = sqlrequest($database_eonweb,"select login from ldap_users_extended where dn='$user_location'");
			$username = mysqli_result($result,0,"login");
			$user_name = strtolower($username);
			//message(8,"User location1: $user_location",'ok');	// For debug pupose, to be removed
			//message(8,"User name1: $user_name",'ok');		// For debug pupose, to be removed
			$user_password1 = "abcdefghijklmnopqrstuvwxyz";
			$user_password2 = "abcdefghijklmnopqrstuvwxyz";		
		}
		else{
			$user_name = retrieve_form_data("user_name",null);
			$user_password1 = retrieve_form_data("user_password1","");
			$user_password2 = retrieve_form_data("user_password2","");
		}

		if ($user_id == null) 
		{
			echo '<div class="row">
					<div class="col-lg-12">
						<h1 class="page-header">'.getLabel("label.admin_user.title_new").'</h1>
					</div>
				</div>';
			
			//------------------------------------------------------------------------------------------------
			// ACCOUNT CREATION (New user ID)
			//------------------------------------------------------------------------------------------------
			if 	(isset($_POST['add']))
			{
				$create_user_in_nagvis = retrieve_form_data("create_user_in_nagvis","");
				if($create_user_in_nagvis == "yes"){ $nagvis_user = true; }
				else { $nagvis_user = false; }
				
				$user_group = retrieve_form_data("user_group","");
				$nagvis_grp = retrieve_form_data("nagvis_group", "");
				$user_id=insert_user(stripAccents($user_name), $user_descr, $user_email, $user_group, $user_password1, $user_password2, $user_type, $user_location,$user_limitation, true, $create_user_in_nagvis, $nagvis_grp, $user_language);
				//message(8,"User location: $user_location",'ok');	// For debug pupose, to be removed

				// Retrieve Group Information from database
				if($user_id){
					$user_name_descr = retrieve_user_info($user_id);
					$user_name=mysqli_result($user_name_descr,0,"user_name");
					$user_email=mysqli_result($user_name_descr,0,"user_email");
					$user_descr=mysqli_result($user_name_descr,0,"user_descr");
					$user_group=mysqli_result($user_name_descr,0,"group_id");
					$user_type=mysqli_result($user_name_descr,0,"user_type");
					$user_limitation = retrieve_form_data("user_limitation","");
					$user_language = retrieve_form_data("user_language","");
					$user_location=mysqli_result($user_name_descr,0,"user_location");
					$user_password1= "abcdefghijklmnopqrstuvwxyz";
					$user_password2= "abcdefghijklmnopqrstuvwxyz";
				}
			}
			//------------------------------------------------------------------------------------------------
		}
		else
		{
			echo '<div class="row">
					<div class="col-lg-12">
						<h1 class="page-header">'.getLabel("label.admin_user.title_upd").'</h1>
					</div>
				</div>';

			//------------------------------------------------------------------------------------------------
						// ACCOUNT UPDATE (and retrieve parameters)
						//------------------------------------------------------------------------------------------------
			if (isset($_POST['update'])){
				update_user($user_id, stripAccents($user_name), $user_descr, $user_email, $user_group, $user_password1, $user_password2, $user_type, $user_location, $user_limitation, $old_group_id, $old_name, $create_user_in_nagvis, $nagvis_role_id, $user_language);	
				//message(8,"Update: User location = $user_location",'ok');	// For debug pupose, to be removed
				//message(8,"Update: User name =  $user_name",'ok');			// For debug pupose, to be removed
			}

			// Retrieve Group Information from database
			$user_name_descr = retrieve_user_info($user_id);
			$user_name=mysqli_result($user_name_descr,0,"user_name");
			$user_email=mysqli_result($user_name_descr,0,"user_email");
			$user_descr=mysqli_result($user_name_descr,0,"user_descr");
			$user_group=mysqli_result($user_name_descr,0,"group_id");
			$user_type=mysqli_result($user_name_descr,0,"user_type");
			$user_limitation=mysqli_result($user_name_descr,0,"user_limitation");
			$user_location=mysqli_result($user_name_descr,0,"user_location");
			$user_password1="abcdefghijklmnopqrstuvwxyz";
			$user_password2="abcdefghijklmnopqrstuvwxyz";

			// search the user in Nagvis (to check the checkbox if he's found)
			$nagvis_user_count=mysqli_result(sqlrequest("$database_nagvis","SELECT count(*) AS count_user FROM users WHERE name='$user_name'"),0,"count_user");
            if ($nagvis_user_count > 0) {
				$nagvis_user = true;
			} else {
				$nagvis_user = false;
			}

			//message(8,"Mod: User name =  $user_name",'ok');                      // For debug pupose, to be removed

			//------------------------------------------------------------------------------------------------
		}

		// search all nagvis groups
		$id_nagvis = false;
		$role_id = false;
		$nagvis_roles=sqlrequest("$database_nagvis", "SELECT roleId, name FROM roles");
		$nagvis_userid=mysqli_result(sqlrequest("$database_nagvis","SELECT userId from users WHERE name = '$user_name'"),0,"userId");

		if ($nagvis_userid > 0) {
			$id_nagvis = $nagvis_userid;
			$req = mysqli_result(sqlrequest("$database_nagvis","SELECT roleId FROM users2roles WHERE userId = $id_nagvis"),0,"roleId");
			if ($req > 0) {
				$role_id = $req;
			}
		}
	?>

	<form id="form_user" action='./add_modify_user.php' method='POST' name='form_user'>
		<input type='hidden' name='user_id' value='<?php echo $user_id?>'>
		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_name") ?></label>
			<div class="col-md-9">
				<input class="form-control" type='text' name='user_name' value='<?php echo $user_name?>'>
				<input type='hidden' name='user_name_old' value='<?php echo $user_name?>'>
			</div>
		</div>
			
		<?php if($user_id!="1"){ ?>
			<div class="row form-group">
				<label class="col-md-3"><?php echo getLabel("label.admin_user.user_limit"); ?></label>
				<div class="col-md-9">
					<?php
						if($user_limitation=="1") $checked="checked='yes'";
						else $checked="";
						echo "<input type='checkbox' class='checkbox' name='user_limitation' value='1' $checked onclick='disable_group()'>";
					?>
				</div>
			</div>
			
			<div class="row form-group">
				<label class="col-md-3"><?php echo getLabel("label.admin_user.user_ldap"); ?></label>
				<div class="col-md-9">
					<?php
						if($user_type=="1") $checked="checked='checked'";
						else $checked="";
						echo "<input type='checkbox' class='checkbox' name='user_type' value='1' $checked onclick='disable()'>";
					?>
				</div>
			</div>
			
			<div class="row form-group">
				<label class="col-md-3"><?php echo getLabel("label.admin_user.ldap_log"); ?></label>
				<div class="col-md-9">
					<?php
						echo "<input class='form-control' id='user_location' name='user_location' type='text' value='".htmlspecialchars($user_location, ENT_QUOTES)."'>";
					?>
				</div>
			</div>
		<?php 
		} 
		else {
			echo "<input type='hidden' name='user_type' value='0'>";
			echo "<input type='hidden' name='user_group' value='1'>";
			echo "<input type='hidden' name='create_user_in_nagvis' value='yes'>";
			echo "<input type='hidden' name='nagvis_group' value='1'>";
		}
		?>
		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_mail"); ?></label>
			<div class="col-md-9">
				<input class="form-control" type='text' name='user_mail' value='<?php echo $user_email?>'>
			</div>
		</div>

		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_desc"); ?></label>
			<div class="col-md-9">
				<input class="form-control" type='text' name='user_descr' value='<?php echo $user_descr?>'>
			</div>
		</div>
		
		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_pwd"); ?></label>
			<div class="col-md-9">
				<input class="form-control" type='password' name='user_password1' value='<?php echo $user_password1?>'>
			</div>
		</div>
		
		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_pwd2"); ?></label>
			<div class="col-md-9">
				<input class="form-control" type='password' name='user_password2' value='<?php echo $user_password2?>'>
			</div>
		</div>
		
		<!-- Adding a language defined by user -->
		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_lang"); ?></label>
			<div class="col-md-9">
				<?php echo GetUserLang(); ?>
			</div>
		</div>
		
		<!-- If not user admin -->
		<?php if($user_id!="1") { ?>
		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_group"); ?></label>
			<div class="col-md-9">
				<select class="form-control" name='user_group' size=1>
					<?php
						$result=sqlrequest("$database_eonweb","SELECT group_id,group_name from groups");
						while ($line = mysqli_fetch_array($result))
						{
							if ($user_group == $line[0])
								echo "<OPTION value='$line[0]' SELECTED>$line[1] </OPTION>";
							else
								echo "<OPTION value='$line[0]'>$line[1] </OPTION>";					
						}
					?>
				</select>
			</div>
		</div>

		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_nagvis"); ?></label>
			<div class="col-md-9">
				<div class="input-group col-md-5">
					<span class="input-group-addon">
		                <?php
							if(isset($nagvis_user) && $nagvis_user=="yes") $checked="checked='checked'";
		                    else $checked="";
		                    echo "<input type='checkbox' class='checkbox' name='create_user_in_nagvis' value='yes' $checked>";
						?>
					</span>
					<select class="form-control" name="nagvis_group">
					<?php
						while ($line = mysqli_fetch_array($nagvis_roles)) {
							$selected = '';
							if (!isset($_GET["user_id"]) && $line["name"] == "Guests" && !$role_id) {
								$selected = "selected";
							}
							if ($role_id == $line["roleId"]) {
								$selected = "selected";
							}
							echo "<option value=\"" . $line["roleId"] . "\" $selected>" . $line["name"] . "</option>";
						}
						?>
					</select>
				</div>
			</div>
		</div>
<!-- 
		<div class="row form-group">
			<label class="col-md-3"><?php echo getLabel("label.admin_user.user_cacti"); ?></label>
			<div class="col-md-9">
				<?php
					if(isset($cacti_user) && $cacti_user == "yes") $checked = "checked='checked'";
                    else $checked = "";
                    echo "<input type='checkbox' class='checkbox' name='create_user_in_cacti' value='yes' $checked>";
				?>
			</div>
		</div>
	-->	
		<?php } ?>
		<div class="form-group">
			<?php
				if ($user_id !=null)
					echo "<button class='btn btn-primary' type='submit' name='update' value='update'>".getLabel("action.update")."</button>";
				else
					echo "<button class='btn btn-primary' type='submit' name='add' value='add'>".getLabel("action.add")."</button>";
				echo "<button class='btn btn-default' style='margin-left: 10px;' type='button' name='back' value='back' onclick='location.href=\"index.php\"'>".getLabel("action.cancel")."</button>";
			?>
		</div>
	</form>

</div>

<?php include("../../footer.php"); ?>
