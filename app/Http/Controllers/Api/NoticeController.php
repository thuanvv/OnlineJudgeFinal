<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeController extends Controller
{
    // 获取一条公告
    public function get_notice($id)
    {
        $notice = DB::table('notices')->select(['title', 'content', 'created_at', 'hidden'])->find($id);
        if (!$notice || (!privilege('admin.notice') && $notice->hidden))
            return ['ok' => 0, 'msg' => '不存在的公告'];
        return [
            'ok' => 1,
            'msg' => 'success',
            'data' => $notice
        ];
    }
}