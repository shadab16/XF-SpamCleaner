<?php

class SpamCleaner_Route_PrefixAdmin_AggressiveCleaner
	implements XenForo_Route_Interface, XenForo_Route_BuilderInterface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'user_id');
		return $router->getRouteMatch('SpamCleaner_ControllerAdmin_AggressiveCleaner', $action, 'tools');
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'user_id', 'username');
	}
}
