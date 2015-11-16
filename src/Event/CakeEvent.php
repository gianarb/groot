<?php
namespace Penny\Event;

use Exception;
use Cake\Event\Event as BaseCakeEvent;
use Penny\Route\RouteInfoInterface;

class CakeEvent extends BaseCakeEvent implements PennyEventInterface
{
    /**
     * {@inheritDoc}
     */
    public function __construct($name, $subject = null, $data = null)
    {
        $this->setName($name);
        $this->data = $data;
        $this->_subject = $subject;
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * {@inheritDoc}
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * {@inheritDoc}
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * {@inheritDoc}
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteInfo()
    {
        return $this->routeInfo;
    }

    /**
     * {@inheritDoc}
     */
    public function setRouteInfo(RouteInfoInterface $routerInfo)
    {
        $this->routeInfo = $routerInfo;
    }

    /**
     * {@inheritDoc}
     */
    public function setException(Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * {@inheritDoc}
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * {@inheritDoc}
     */
    public function stopPropagation($flag = true)
    {
        $this->_stopped = $flag;
    }
}
