<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;

abstract class BaseApiController extends Controller
{
    use ApiResponse;
}
