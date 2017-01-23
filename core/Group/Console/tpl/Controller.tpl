<?php

namespace src\{{group}}\Controller\{{name}};

use Controller;

class {{name}}Controller extends Controller
{
    public function indexAction()
    {
        return $this->render('{{group}}/Views/{{name}}/index.html.twig');
    }

}

