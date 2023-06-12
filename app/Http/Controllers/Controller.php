<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    function jsonRes($code = 200, $msg = null, $data = null)
    {
        if ($code === 200) {
            if (!$msg && !$data) {
                return response()->json(['msg' => "success"], $code);
            } else if (!$data && $msg) {
                return response()->json(['msg' => $msg], $code);
            } else if ($data && !$msg) {
                return response()->json(['msg' => "success", 'data' => $data], $code);
            } else {
                return response()->json(['msg' => $msg, 'data' => $data], $code);
            }
        }
        if ($code === 401) {
            if (!$msg) {
                return response()->json(['msg' => "unauthorized"], $code);
            } else {
                return response()->json(['msg' => $msg], $code);
            }
        }
        if ($code === 403) {
            if (!$msg) {
                return response()->json(['msg' => "forbidden"], $code);
            } else {
                return response()->json(['msg' => $msg], $code);
            }
        }
        if ($code === 404) {
            if (!$msg) {
                return response()->json(['msg' => "not found"], $code);
            } else {
                return response()->json(['msg' => $msg], $code);
            }
        }
        if ($code == 422) {
            if (!$msg) {
                return response()->json(['msg' => "data cannot be processed"], $code);
            } else {
                return response()->json(['msg' => $msg], $code);
            }
        }
    }
}
