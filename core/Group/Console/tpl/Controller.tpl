<?php

namespace src\Web\Controller\{{name}};

use Controller;

class {{name}}Controller extends Controller
{
    public function indexAction()
    {
        return $this -> render('Web/Views/{{name}}/index.html.twig');
    }

}

