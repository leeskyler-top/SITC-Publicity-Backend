<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccessHistoryResource;
use App\Models\AccessHistory;
use Illuminate\Http\Request;

class ApiHistoryController extends Controller
{
    function list()
    {
        $histories = AccessHistory::orderBy('created_at', 'desc')->get();
        return $this->jsonRes(200, '列表获取完成', AccessHistoryResource::collection($histories));
    }
}
