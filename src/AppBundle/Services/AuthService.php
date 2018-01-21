<?php
/**
 * Created by PhpStorm.
 * User: fabrice
 * Date: 20/01/2018
 * Time: 00:17
 */

namespace AppBundle\Services;


use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthService
{
    protected $auth_id;
    protected $auth_salt;
    protected $request_id;
    protected $request_secret;

    public  function __construct(Container $container, RequestStack $requestStack)
    {
        $this->auth_id = $container->getParameter('auth_id');
        $this->auth_salt = $container->getParameter('secret');
        if($requestStack->getCurrentRequest()) $this->request_id = $requestStack->getCurrentRequest()->server->get('HTTP_ID');
        if($requestStack->getCurrentRequest()) $this->request_secret = $requestStack->getCurrentRequest()->server->get('HTTP_SECRET');
    }

    private function getExpected(){
        return hash('sha256',crypt($this->auth_id,$this->auth_salt));
    }

    public function validate(){
        return ($this->request_id === $this->auth_id && hash_equals($this->getExpected(), $this->request_secret));
    }

    public function retrieveSecret(){
        return $this->getExpected();
    }

}