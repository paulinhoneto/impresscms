<?php

namespace ImpressCMS\Core\Middlewares;

use icms;
use icms_member_groupperm_Handler;
use ImpressCMS\Core\Models\GroupPermHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HasPermissionMiddleware implements MiddlewareInterface
{
	/**
	 * @var string
	 */
	private $permissionName;

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * HasRightMiddleware constructor.
	 *
	 * @param string $permissionName What permission should exist here to check if everything is ok?
	 * @param ContainerInterface $container Linked container
	 */
	public function __construct(string $permissionName, ContainerInterface $container)
	{
		$this->permissionName = $permissionName;
		$this->container = $container;
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$groups = (is_object(icms::$user)) ? icms::$user->getGroups() : ICMS_GROUP_ANONYMOUS;
		/**
		 * @var GroupPermHandler $permissionHandler
		 */
		$permissionHandler = icms::handler('icms_member_groupperm');

		$params = $request->getQueryParams();
		$mid = $this->container->get('module')->mid;
		$itemId = $params['id'] ?? 0;
		if ($permissionHandler->checkRight($this->permissionName, $itemId, $groups, $mid)) {
			return $handler->handle($request);
		}

		/**
		 * @var ResponseFactoryInterface $responseFactory
		 */
		$responseFactory = $this->container->get('response_factory');
		return $responseFactory->createResponse(403);
	}
}