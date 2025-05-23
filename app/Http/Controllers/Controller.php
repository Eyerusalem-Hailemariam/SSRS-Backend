<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA; // Ensure this import is at the top

/**
 * @OA\Info(title="Your API Title", version="1.0.0")
 * @OA\Server(url="http://127.0.0.1:8000/api")
 * @OA\SecurityScheme(securityScheme="bearerAuth", type="http", scheme="bearer",name="Authorization", in="header")
 * */

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
