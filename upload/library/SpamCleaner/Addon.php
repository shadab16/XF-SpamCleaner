<?php

class SpamCleaner_Addon
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');
		$db->query("
			CREATE TABLE gp_aggressive_cleaner_queue (
				user_id INT UNSIGNED NOT NULL,
				PRIMARY KEY (user_id)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
		");
	}

	public static function uninstall($existingAddOn)
	{
		$db = XenForo_Application::get('db');
		$db->query("
			DROP TABLE gp_aggressive_cleaner_queue
		");
	}
}
