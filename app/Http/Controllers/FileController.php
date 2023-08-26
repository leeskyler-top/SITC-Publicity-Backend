<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class FileController extends Controller
{
    function normal($type, $filename)
    {
        $types = ['assigned', 'damaged', 'returned', 'checkin'];
        if (!in_array($type, $types)) {
            return $this->jsonRes(404, '类型不存在');
        }
        $user = Auth::user();
        $image_validator = $user->equipmentRents()->where($type.'_url', 'LIKE', '%'.$filename.'%')->exists();
        if (!$image_validator) {
            return $this->jsonRes(404, '图片不存在');
        }
        $imagePath = public_path('files/images/' . $type . '/' . $filename);
        if (File::exists($imagePath)) {
            $mimeType = mime_content_type($imagePath);
            return response()->file($imagePath, ['Content-Type' => $mimeType]);
        }
        return $this->jsonRes(404, '图片不存在');
    }

    function admin($type, $filename)
    {
        $types = ['assigned', 'damaged', 'returned', 'checkin'];
        if (!in_array($type, $types)) {
            return $this->jsonRes(404, '类型不存在');
        }
        $imagePath = public_path('files/images/' . $type . '/' . $filename);
        if (File::exists($imagePath)) {
            $mimeType = mime_content_type($imagePath);
            return response()->file($imagePath, ['Content-Type' => $mimeType]);
        }
        return $this->jsonRes(404, '图片不存在');
    }
}
