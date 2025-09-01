<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Response;

class ApiCallsController extends CommonController
{

    public function indexAction(): Response
     {
         return new Response();
     }
}