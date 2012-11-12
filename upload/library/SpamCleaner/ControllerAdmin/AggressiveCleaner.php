<?php

class SpamCleaner_ControllerAdmin_AggressiveCleaner extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('user');
	}

	public function actionIndex()
	{
		$model = $this->_getAggressiveCleanerModel();

		$viewParams = array(
			'users'      => $model->getUsers(),
			'totalUsers' => $model->countUsers(),
		);

		return $this->responseView('SpamCleaner_ViewAdmin_List',
			'aggressive_cleaner_list', $viewParams
		);
	}

	public function actionAdd()
	{
		$viewParams = array();

		return $this->responseView('SpamCleaner_ViewAdmin_Add',
			'aggressive_cleaner_add', $viewParams
		);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$userIds = $this->_input->filterSingle('user_ids', XenForo_Input::STRING);
		$userIds = explode("\n", $userIds);

		$model = $this->_getAggressiveCleanerModel();
		$model->addUsers($userIds);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('aggressive-cleaner')
		);
	}

	public function actionDelete()
	{
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$user = $this->_getUserOrError($userId);

		if ($this->isConfirmedPost())
		{
			$model = $this->_getAggressiveCleanerModel();
			$model->removeUser($user['user_id']);

			$redirectMessage = new XenForo_Phrase('deletion_successful');
			$redirectLink = XenForo_Link::buildAdminLink('aggressive-cleaner');

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirectLink, $redirectMessage
			);
		}

		$viewParams = array('user' => $user);

		return $this->responseView('SpamCleaner_ViewAdmin_Delete',
			'aggressive_cleaner_delete', $viewParams
		);
	}

	public function actionClean()
	{
		$model = $this->_getAggressiveCleanerModel();
		$users = $model->getNextBatch();

		if (empty($users))
		{
			return $this->responseError('Nothing to clean!');
		}

		if ($this->isConfirmedPost())
		{
			if (!$model->cleanUsers($users, $errorKey))
			{
				return $this->responseError(new XenForo_Phrase($errorKey));
			}

			$viewParams = array('users' => $users);

			return $this->responseView('SpamCleaner_ViewAdmin_Clean',
				'aggressive_cleaner_cleaned', $viewParams
			);
		}

		$viewParams = array('users' => $users);

		return $this->responseView('SpamCleaner_ViewAdmin_Clean',
			'aggressive_cleaner_confirm', $viewParams
		);
	}

	protected function _getUserOrError($userId)
	{
		$userModel = $this->getModelFromCache('XenForo_Model_User');

		return $this->getRecordOrError(
			$userId,
			$userModel,
			'getFullUserById',
			'requested_user_not_found'
		);
	}

	/**
	 * @return SpamCleaner_Model_AggressiveCleaner
	 */
	protected function _getAggressiveCleanerModel()
	{
		return $this->getModelFromCache('SpamCleaner_Model_AggressiveCleaner');
	}
}
