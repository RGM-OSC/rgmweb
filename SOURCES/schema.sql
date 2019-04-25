-- MySQL 


DROP TABLE IF EXISTS `auth_settings`;
CREATE TABLE `auth_settings` (
	`auth_type` tinyint(1) NOT NULL DEFAULT '0',
	`ldap_ip` varchar(255) DEFAULT NULL,
	`ldap_port` int(11) DEFAULT NULL,
	`ldap_search` varchar(255) DEFAULT NULL,
	`ldap_user` varchar(255) DEFAULT NULL,
	`ldap_password` varchar(255) DEFAULT NULL,
	`ldap_rdn` varchar(255) DEFAULT NULL,
	`ldap_user_filter` varchar(255) DEFAULT NULL,
	`ldap_group_filter` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`auth_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

LOCK TABLES `auth_settings` WRITE;
INSERT INTO `auth_settings` VALUES (0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
UNLOCK TABLES;

--
-- Table structure for table `groupright`
--

DROP TABLE IF EXISTS `groupright`;
CREATE TABLE `groupright` (
	`group_id` int(11) NOT NULL,
	`tab_1` enum('0','1') NOT NULL DEFAULT '0',
	`tab_2` enum('0','1') NOT NULL DEFAULT '0',
	`tab_3` enum('0','1') NOT NULL DEFAULT '0',
	`tab_4` enum('0','1') NOT NULL DEFAULT '0',
	`tab_5` enum('0','1') NOT NULL DEFAULT '0',
	`tab_6` enum('0','1') NOT NULL DEFAULT '0',
	`tab_7` enum('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (`group_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

LOCK TABLES `groupright` WRITE;
INSERT INTO `groupright` VALUES (1,'1','1','1','1','1','1','1');
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
	`group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`group_name` varchar(255) NOT NULL,
	`group_descr` text,
	`group_dn` varchar(255) DEFAULT NULL,
	`group_type` tinyint(1) DEFAULT NULL,
	PRIMARY KEY (`group_id`,`group_name`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

LOCK TABLES `groups` WRITE;
INSERT INTO `groups` VALUES (1,'admins','Administrator group',NULL,NULL);
UNLOCK TABLES;

--
-- Table structure for table `ldap_groups_extended`
--

DROP TABLE IF EXISTS `ldap_groups_extended`;
CREATE TABLE `ldap_groups_extended` (
	`dn` varchar(255) NOT NULL,
	`group_name` varchar(255) DEFAULT NULL,
	`checked` smallint(6) NOT NULL,
	PRIMARY KEY (`dn`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

--
-- Table structure for table `ldap_users`
--

DROP TABLE IF EXISTS `ldap_users`;
CREATE TABLE `ldap_users` (
	`dn` varchar(255) NOT NULL,
	`login` varchar(255) NOT NULL,
	PRIMARY KEY (`dn`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


--
-- Table structure for table `ldap_users_extended`
--

DROP TABLE IF EXISTS `ldap_users_extended`;
CREATE TABLE `ldap_users_extended` (
	`dn` varchar(255) NOT NULL,
	`login` varchar(255) NOT NULL,
	`user` varchar(255) NOT NULL,
	`checked` smallint(6) NOT NULL,
	PRIMARY KEY (`dn`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
	`id` mediumint(9) NOT NULL AUTO_INCREMENT,
	`date` varchar(255) NOT NULL,
	`user` varchar(255) NOT NULL,
	`module` varchar(255) NOT NULL,
	`description` varchar(255) NOT NULL,
	`source` varchar(255) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
	`session_id` int(11) NOT NULL,
	`user_id` int(11) NOT NULL,
	PRIMARY KEY (`session_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
	`user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`group_id` int(11) NOT NULL,
	`user_name` varchar(255) NOT NULL,
	`user_passwd` varchar(255) NOT NULL,
	`user_descr` varchar(255) DEFAULT NULL,
	`user_email` varchar(255) DEFAULT NULL,
	`user_type` tinyint(1) NOT NULL,
	`user_location` varchar(255) DEFAULT NULL,
	`user_limitation` tinyint(1) NOT NULL,
	`user_language` char(2) DEFAULT '0',
	PRIMARY KEY (`user_id`,`user_name`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
LOCK TABLES `users` WRITE;
INSERT INTO `users` VALUES (1,1,'admin','21232f297a57a5a743894a0e4a801fc3','default user',NULL,0,'',0,'0');
UNLOCK TABLES;

DELIMITER $$
-- Triggers on users table to synchronize RGM users with Grafana users
-- Trigger on INSERT
DROP TRIGGER IF EXISTS `rgm_user_insert` $$
CREATE TRIGGER rgm_user_insert AFTER INSERT on rgmweb.users FOR EACH ROW
BEGIN
	DECLARE is_grpadmin BOOL;
	DECLARE userid MEDIUMINT;
	DECLARE createddate DATETIME;
	DECLARE defrole varchar(20);

	--
	-- Grafana user synchronization
	--
	SET is_grpadmin = FALSE;
	SET defrole = 'View';

	IF NEW.group_id = 1 THEN
		SET is_grpadmin = TRUE;
		SET defrole = 'Editor';
	END IF;

	INSERT INTO `grafana`.`user` SET
		login = NEW.user_name,
		email = NEW.user_email,
		name = NEW.user_descr,
		org_id = 1,
		is_admin = is_grpadmin,
		email_verified = TRUE,
		created = NOW();

	SET userid = (SELECT id FROM `grafana`.`user` WHERE login = NEW.user_name);
	SET createddate = (SELECT created FROM `grafana`.`user` WHERE login = NEW.user_name);
	INSERT INTO grafana.org_user SET
		org_id = 1,
		user_id = userid,
		role = defrole,
		created = createddate,
		updated = NOW();

	--
	-- Lilac user synchronization
	--
	CALL lilac.create_update_lilac_user_from_rgmweb(NEW.user_name, NEW.user_descr, NEW.user_email);
END;

-- Trigger on UPDATE
DROP TRIGGER IF EXISTS `rgm_user_update` $$
CREATE TRIGGER rgm_user_update AFTER UPDATE on rgmweb.users FOR EACH ROW
BEGIN
	DECLARE is_grpadmin BOOL;
	DECLARE userid MEDIUMINT;
	DECLARE defrole varchar(20);

	--
	-- Grafana user synchronization
	--
	SET is_grpadmin = FALSE;
	SET defrole = 'View';

	IF NEW.group_id = 1 THEN
		SET is_grpadmin = TRUE;
		SET defrole = 'Editor';
	END IF;

	UPDATE `grafana`.`user` SET
		email = NEW.user_email,
		name = NEW.user_descr,
		org_id = 1,
		is_admin = is_grpadmin,
		email_verified = TRUE,
		updated = NOW()
		WHERE login = NEW.user_name;

	SET userid = (SELECT id FROM `grafana`.`user` WHERE login = NEW.user_name);

	UPDATE `grafana`.`org_user` SET
		role = defrole,
		updated = NOW()
	WHERE user_id = userid;

	--
	-- Lilac user synchronization
	--
	CALL lilac.create_update_lilac_user_from_rgmweb(NEW.user_name, NEW.user_descr, NEW.user_email);
END;

-- Trigger on DELETE
DROP TRIGGER IF EXISTS `rgm_user_delete` $$
CREATE TRIGGER rgm_user_delete AFTER DELETE on rgmweb.users FOR EACH ROW
BEGIN
	DECLARE userid MEDIUMINT;
	--
	-- Grafana user synchronization
	--
	SET userid = (SELECT id FROM `grafana`.`user` WHERE login = OLD.user_name);
	DELETE FROM `grafana`.`user` WHERE login = OLD.user_name;
	DELETE FROM `grafana`.`org_user` WHERE user_id = userid;

	--
	-- Lilac user synchronization
	--
	CALL lilac.delete_lilac_user_from_rgmweb(OLD.user_name);
END;
$$
DELIMITER ;
