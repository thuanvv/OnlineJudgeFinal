<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\DBHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContestController extends Controller
{
    /**
     * 修改竞赛的类别号
     * 注意order字段需要变动
     * （注意修改竞赛时，该方法会被调用！Controllers/Admin/ContestController.php）
     */
    public function update_cate_id($contest_id, $cate_id)
    {
        $contest = DB::table('contests')->find($contest_id);
        if ($contest->cate_id == $cate_id)
            return [
                'ok' => 0,
                'msg' => sprintf('竞赛[%d]的类别没有发生改变', $contest_id)
            ];
        // 原类别下的order需要缩减
        DB::table('contests')->where('cate_id', $contest->cate_id)->where('order', '>', $contest->order)->decrement('order');
        // 新类别下的order自然增长
        $max_order = DB::table('contests')->where('cate_id', $cate_id)->max('order');
        DB::table('contests')->where('id', $contest_id)->update([
            'cate_id' => $cate_id,
            'order' => $max_order + 1
        ]);
        return [
            'ok' => 1,
            'msg' => sprintf('竞赛[%d]已修改类别', $contest_id)
        ];
    }
    /**
     * 修改竞赛的顺序，即order字段
     * 注意！竞赛order是倒序展示的
     * 输入：
     *      id: 竞赛编号
     *      shift: 对order字段的偏移量，整数范围
     */
    public function update_order($id, $shift)
    {
        // 获取当前竞赛
        $contest = DB::table('contests')->find($id);
        $updated = DBHelper::shift_order('contests', ['cate_id' => $contest->cate_id], $contest->order, $shift);
        if ($updated > 0)
            return [
                'ok' => 1,
                'msg' => sprintf('%d items have been affected.', $updated)
            ];
        return [
            'ok' => 0,
            'msg' => 'Nothing has been affected.'
        ];
    }


    /*****************************  类别   ***********************************/

    /**
     * 添加类别
     * 输入：
     *      values: 数组，类别里的各个字段
     * */
    public function add_contest_cate(Request $request)
    {
        $values = $request->input('values');
        $max_order = DB::table('contest_cate')->where('parent_id', $values['parent_id'])->max('order');
        $values['order'] = $max_order + 1;
        $id = DB::table('contest_cate')->insertGetId($values);
        return [
            'ok' => 1,
            'msg' => '已添加新类别'
        ];
    }

    /**
     * 修改类别信息
     * 输入：
     *      values: 数组，要修改的字段
     */
    public function update_contest_cate(Request $request, $id)
    {
        $values = $request->input('values');
        if (isset($values['parent_id'])) //拦截非法的父级类别修改
        {
            $parent = DB::table('contest_cate')->find($values['parent_id']); // 欲指定的父类别
            if ($values['parent_id'] > 0 && !$parent) {
                return [
                    'ok' => 0,
                    'msg' => '指定的父级类别不存在！'
                ];
            }
            if ($values['parent_id'] == $id) {
                return [
                    'ok' => 0,
                    'msg' => '不能作为自身的子类别！'
                ];
            }
            if ($values['parent_id'] > 0 && $parent->parent_id > 0) {
                return [
                    'ok' => 0,
                    'msg' => '指定的父级类别必须是一级类别！请刷新页面后重试！'
                ];
            }
            if ($values['parent_id'] > 0 && DB::table('contest_cate')->where('parent_id', $id)->count() > 0) {
                return [
                    'ok' => 0,
                    'msg' => '当前类别下有二级类别，请先移走它们，再修改当前类别的父类别！'
                ];
            }
        }

        // 开始执行修改事务
        $cate = DB::table('contest_cate')->find($id);

        // 如果父类别发生了改变，则一定要矫正order字段
        if (isset($values['parent_id']) && $values['parent_id'] != $cate->parent_id) {
            DBHelper::shift_order('contest_cate', ['parent_id' => $cate->parent_id], $cate->order, PHP_INT_MAX);
            // 在新类别中order应当处于末尾
            $values['order'] = DB::raw(
                "(select temp.`next_order`
                    from (
                        select max(cc.`order`)+1 as next_order
                        from `contest_cate` as cc
                        where cc.`parent_id`=" . $values['parent_id'] . "
                        ) temp)"
            );
        }
        // 执行修改
        DB::table('contest_cate')->where('id', $cate->id)->update($values);

        return [
            'ok' => 1,
            'msg' => '已修改'
        ];
    }

    /**
     * 删除类别
     */
    public function delete_contest_cate($id)
    {
        if (DB::table('contest_cate')->where('parent_id', $id)->exists()) {
            return [
                'ok' => 0,
                'msg' => '一级分类下包含子类别，请先删除或移走所有子类别再删除当前类别'
            ];
        }
        $cate = DB::table('contest_cate')->find($id);
        // order字段矫正（比当前类别大的，-1）
        DB::table('contest_cate')
            ->where('parent_id', $cate->parent_id)
            ->where('order', '>', $cate->order)
            ->decrement('order');
        DB::table('contest_cate')->where('id', $id)->delete();
        return [
            'ok' => 1,
            'msg' => '已删除'
        ];
    }

    /**
     * 修改竞赛类别的顺序，即order字段
     * 输入：
     *      id: 类别编号
     *      shift: 偏移量，整数
     */
    public function update_contest_cate_order($id, $shift)
    {
        // 获取当前类别
        $cate = DB::table('contest_cate')->find($id);
        $updated = DBHelper::shift_order('contest_cate', ['parent_id' => $cate->parent_id], $cate->order, $shift);
        if ($updated > 0)
            return [
                'ok' => 1,
                'msg' => sprintf('%d items have been affected.', $updated)
            ];
        return [
            'ok' => 0,
            'msg' => 'Nothing has been affected.'
        ];
    }
}
