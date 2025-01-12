<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProblemController extends Controller
{
    function submit_problem_tag(Request $request)
    {
        $problem_id = $request->input('problem_id');
        $tag_names = $request->input('tag_names');
        $tag_names = array_unique($tag_names);
        $tag_marks = [];
        foreach ($tag_names as $tag_name) {
            if (!DB::table('tag_pool')->where('name', $tag_name)->exists())
                $tid = DB::table('tag_pool')->insertGetId(['name' => $tag_name]);
            else
                $tid = DB::table('tag_pool')->where('name', $tag_name)->first()->id;
            $tag_marks[] = ['problem_id' => $problem_id, 'user_id' => Auth::id(), 'tag_id' => $tid];
        }
        $inserted = DB::table('tag_marks')->insert($tag_marks);
        if ($inserted == 0)
            return [
                'ok' => 0,
                'msg' => '提交失败，请检查数据合法性'
            ];
        return [
            'ok' => 1,
            'msg' => sprintf("成功为题目%d添加%d个标签", $problem_id, $inserted)
        ];
    }
}
