<?php

namespace App\Project\Controllers;
use App\Core\Controller;
	
class ErrorController extends Controller
{
	public function notFound() {
		echo 'Страница не найдена';
	}


}