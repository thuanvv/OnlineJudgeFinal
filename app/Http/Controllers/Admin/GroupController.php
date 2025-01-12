<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    // get: 后台管理 group列表
    public function list()
    {
        $groups = DB::table('groups as c')
            ->leftJoin('users', 'users.id', '=', 'creator')
            ->select(['c.*', 'username'])
            ->when(isset($_GET['name']), function ($q) {
                return $q->where('c.name', 'like', '%' . $_GET['name'] . '%');
            })
            ->orderByDesc('id')
            ->paginate($_GET['perPage'] ?? 10);

        return view('admin.group.list', compact('groups'));
    }

    // get: 新建 group
    public function create(Request $request)
    {
        return view('admin.group.edit'); //提供界面
    }

    // get: 编辑已存在的 group
    public function edit(Request $request, $group_id)
    {
        if (!($group = DB::table('groups')->find($group_id)))
            return view('message', ['msg' => '群组不存在!']);
        return view('admin.group.edit', compact('group'));
    }
}
