<?php

class SpamCleaner_Model_AggressiveCleaner extends XenForo_Model
{
	public function getUserIds()
	{
		return $this->_getDb()->fetchCol('
			SELECT user_id
			FROM gp_aggressive_cleaner_queue
		');
	}

	public function getUsers()
	{
		$userIds = $this->getUserIds();
		$fetchOptions = array(
			'order' => 'message_count',
			'direction' => 'asc'
		);
		return $this->_getUserModel()->getUsersByIds($userIds, $fetchOptions);
	}

	public function countUsers()
	{
		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM gp_aggressive_cleaner_queue
		');
	}

	public function hasUsers()
	{
		return $this->countUsers() > 0;
	}

	public function removeUser($userId)
	{
		$db = $this->_getDb();
		$db->delete('gp_aggressive_cleaner_queue',
			'user_id = ' . $db->quote($userId)
		);
	}

	public function addUsers(array $userIds)
	{
		$users = $this->_getUserModel()->getUsersByIds($userIds);
		$validUserIds = array_keys($users);
		$existingUserIds = $this->getUserIds();

		$validUserIds = array_diff($validUserIds, $existingUserIds);

		foreach ($validUserIds as $userId)
		{
			$this->_addUser($userId);
		}
	}

	protected function _addUser($userId)
	{
		$db = $this->_getDb();
		$db->insert('gp_aggressive_cleaner_queue',
			array('user_id' => $userId)
		);
	}

	protected function _cleanUser($userId, &$errorKey = '')
	{
		$count = $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM gp_aggressive_cleaner_queue
			WHERE user_id = ?
		', array($userId));

		if ($count != 1)
		{
			return false;
		}

		$user = $this->_getUserModel()->getUserById($userId);
		$actions = $this->_getDefaultActions();

		$result = $this->_getSpamCleanerModel()->cleanUp($user, $actions, $log, $errorKey);
		$this->removeUser($userId);

		return $result;
	}

	public function cleanUsers(array $users, &$errorKey = '')
	{
		foreach ($users as $user)
		{
			$userId = is_array($user) ? $user['user_id'] : $user;
			if (!$this->_cleanUser($userId, $errorKey))
			{
				return false;
			}
		}
		return true;
	}

	protected function _getDefaultActions()
	{
		return array(
			'action_threads'  => 1,
			'delete_messages' => 1,
			'ban_user'        => 1,
			'check_ips'       => 0,
			'email_user'      => 0,
		);
	}

	public function getNextBatch()
	{
		$candidates = $this->getUsers();

		if (empty($candidates))
		{
			return array();
		}

		$batch = array();
		$messageThreshold = XenForo_Application::getOptions()->get('aggressiveSpamThreshold');
		$messageCount = 0;

		foreach ($candidates as $user)
		{
			$messageCount += $user['message_count'];
			if ($messageCount > $messageThreshold)
			{
				break;
			}
			$batch[] = $user;
		}

		if (empty($batch))
		{
			$batch[] = array_shift($candidates);
		}

		return $batch;
	}

	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}

	/**
	 * @return XenForo_Model_SpamCleaner
	 */
	protected function _getSpamCleanerModel()
	{
		return $this->getModelFromCache('XenForo_Model_SpamCleaner');
	}
}
