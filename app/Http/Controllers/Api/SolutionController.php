<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class SolutionController extends Controller
{
    // api：提交一份代码; Affect database
    public function submit_solution(Request $request)
    {
        //======================= 拦截非管理员的频繁提交 =================================
        /** @var \App\Models\User */
        $user = Auth::user();
        if (!$user->can('admin.solution.view')) {
            // 规定时间内，不允许多次提交
            $last_submit_time = DB::table('solutions')
                ->where('user_id', Auth::id())
                ->orderByDesc('submit_time')
                ->value('submit_time');
            if (time() - strtotime($last_submit_time) < intval(get_setting('submit_interval')))
                return [
                    'ok' => 0,
                    'msg' => trans('sentence.submit_frequently', ['sec' => get_setting('submit_interval')])
                ];

            // 编译错误，单独惩罚，规定时间内直接不允许提交！
            $last_ce_time = DB::table('solutions')
                ->where('user_id', Auth::id())
                ->where('result', 11) // 编译错误
                ->orderByDesc('submit_time')
                ->value('submit_time');
            if (time() - strtotime($last_ce_time) < intval(get_setting('compile_error_submit_interval')))
                return [
                    'ok' => 0,
                    'msg' => trans('sentence.submit_ce_frequently', [
                        'dt' => $last_ce_time,
                        'sec' => get_setting('compile_error_submit_interval')
                    ])
                ];
        }

        // ======================= 获取数据 =================================
        $data = $request->input('solution');  //获取前台提交的solution信息
        $problem = DB::table('problems')->find($data['pid']);  //找到题目

        // 判断提交的来源; 如果有cid，说明在竞赛中进行提交
        if (isset($data['cid'])) {
            $contest = DB::table("contests")->select('judge_type', 'allow_lang', 'end_time')->find($data['cid']);
            if ((($contest->allow_lang >> $data['language']) & 1) == 0) //使用了不允许的代码语言
                return [
                    'ok' => 0,
                    'msg' => 'Using a programming language that is not allowed!'
                ];
        } else { //else 从题库中进行提交，需要判断一下用户权限
            if ($problem->hidden == 1 && !$user->can('admin.solution.view'))
                return [
                    'ok' => 0,
                    'msg' => '该题目为私有题目，您没有权限提交'
                ];
        }

        // 如果是填空题，填充用户的答案
        if ($problem->type == 1) {
            $data['code'] = $problem->fill_in_blank;
            foreach ($request->input('filled') as $ans) {
                $data['code'] = preg_replace("/\?\?/", $ans, $data['code'], 1);
            }
        }

        //检测代码长度
        if (strlen($data['code']) < 3)
            return ['ok' => 0, 'msg' => '代码长度过短！'];
        if (strlen($data['code']) > 50000)
            return ['ok' => 0, 'msg' => '代码长度不能超过50000! 请适当缩减冗余代码。'];

        // 构造提交记录
        $solution = [
            'problem_id'    => $data['pid'],
            'contest_id'    => isset($data['cid']) ? $data['cid'] : -1,
            'user_id'       => Auth::id(),
            'result'        => 0,
            'language'      => ($data['language'] != null) ? $data['language'] : 0,
            'submit_time'   => date('Y-m-d H:i:s'),

            'judge_type'    => isset($contest->judge_type) ? $contest->judge_type : 'oi', //acm,oi

            'ip'            => ($guest_ip = get_client_real_ip()),
            'ip_loc'        => getIpAddress($guest_ip),
            'code_length'   => strlen($data['code']),
            'code'          => $data['code']
        ];

        //====================== 数据库初始化一条提交记录 ========================
        $solution['id'] = DB::table('solutions')->insertGetId($solution);

        // ===================== 使用任务队列 提交判题任务 =====================
        // dispatch(new Judger($solution['id']));

        // ===================== 给前台返回提交信息 ====================
        // if ($solution['contest_id'] > 0) //竞赛提交
        //     $redirect = route('contest.solutions', [$solution['contest_id'], 'user_id' => Auth::id(), 'group' => $request->input('group') ?? null]);
        // else
        //     $redirect = route('solutions', ['pid' => $solution['problem_id'], 'user_id' => Auth::id()]);

        return [
            'ok' => 1,
            'msg' => '您已提交代码，正在评测中...',
            'data' => [
                'solution_id' => $solution['id'],
                // 'redirect'  => $redirect,
                // 'judge0result' => $judge0result
            ]
        ];
    }

    // api 从数据库查询一条提交记录的判题结果; No update Database.
    public function solution_result($solution_id)
    {
        // $request 传入参数：
        // solution_id: solution id
        // return judge0result:{token1:{'result_id':, 'result_desc':, ...}, ...}
        // ==================== 根据solution id，查询结果 ====================
        $solution = DB::table('solutions')->find($solution_id);
        if (!$solution)
            return ['ok' => 0, 'msg' => '提交记录不存在'];

        /*
        // ==================== 读取判题结果 =========================
        $judge0result = json_decode($solution->judge0result, true) ?? [];
        // if (!$judge0result) // 无效的提交记录
        //     return [
        //         'ok' => 0,
        //         'msg' => '该提交记录找不到判题痕迹，请管理员检查测试数据是否缺失，并重判提交记录',
        //         'data' => $judge0result
        //     ];
        // ================= 给前台返回结果 =================
        foreach ($judge0result as &$item) {
            $item['result_desc'] = trans('result.' . config("oj.judge_result." . ($item["result_id"] ?? 0)));
            // unset($item['spj']); // spj没必要给用户看
        }
        */

        // 临时代替。后期开发，所有测试数据的结果
        $judge_result = [
            0 => [
                'result' => $solution->result,
                'result_desc' => trans('result.' . config("oj.judge_result." . $solution->result)),
                'time' => $solution->time,
                'memory' => $solution->memory,
            ]
        ];

        return [
            'ok' => 1,
            'msg' => 'OK',
            'data' => [
                'result' => $solution->result,
                'result_desc' => trans('result.' . config("oj.judge_result." . $solution->result)),
                'time' => $solution->time,
                'memory' => $solution->memory,
                'error_info' => $solution->error_info,
                'details' => array_values($judge_result) // 不给用户看到 key (judge0 token)
            ]
        ];
    }


    // ==================================== 以下废弃 ===================================

    // api 提交一条本地测试，仅运行返回结果。 No DB
    public function submit_local_test(Request $request)
    {
        //============================= 拦截非管理员的频繁提交 =================================
        /** @var \App\Models\User */
        $user = Auth::user();
        if (!$user->can('admin.solution.view')) {
            $last_submit_time = DB::table('solutions')
                ->where('user_id', Auth::id())
                ->orderByDesc('submit_time')
                ->value('submit_time');
            if (time() - strtotime($last_submit_time) < intval(get_setting('submit_interval')))
                return [
                    'ok' => 0,
                    'msg' => trans('sentence.submit_frequently', ['sec' => get_setting('submit_interval')])
                ];
        }

        //============================= 获取数据 =================================
        $data = $request->input('solution');        //获取前台提交的solution信息
        $problem = DB::table('problems')->find($data['pid']); //找到题目

        //如果是填空题，填充用户的答案
        if ($problem->type == 1) {
            $data['code'] = $problem->fill_in_blank;
            foreach ($request->input('filled') as $ans) {
                $data['code'] = preg_replace("/\?\?/", base64_decode($ans), $data['code'], 1);
            }
        }

        //============================== 使用judge0判题 ==========================
        $judge_response = $this->send_to_run_code(
            ($data['language'] != null) ? $data['language'] : 0, // 默认C
            $data['code'],
            $request->input('stdin'),
            $problem->time_limit,
            $problem->memory_limit,
            true
        );
        if ($judge_response[0] != 201)
            return ['ok' => 0, 'msg' => '[Run] Cannot connect to judge server: ' . $judge_response[0]];

        unset($judge_response[1]['token']); // 不给用户看到token
        return [
            'ok' => 1,
            'msg' => '运行完成',
            'data' => ['judge0result' => $judge_response[1]]
        ];
    }

    // 向jduge0发送一次运行请求；No Database
    private function send_to_run_code($lang_id, $code, $stdin, $time_limit_ms, $memory_limit_mb, $wait)
    {
        $data = [
            'language_id'     => config('oj.langJudge0Id.' . $lang_id),
            'source_code'     => base64_encode($code),
            'stdin'           => base64_encode($stdin),
            // 'expected_output' => base64_encode(file_get_contents($sample['out'])),
            'cpu_time_limit'  => $time_limit_ms / 1000.0, //convert to S
            'memory_limit'    => $memory_limit_mb * 1024, //convert to KB
            'max_file_size'   => 64, // 64KB
            'enable_network'  => false,
            // 'redirect_stderr_to_stdout' => true,
        ];
        if ($lang_id == 1) //C++
            $data['compiler_options'] = "-O2 -std=c++17";
        $url = config('app.JUDGE0_SERVER') . '/submissions/?' . http_build_query([
            'base64_encoded' => 'true',
            'wait' => $wait ? 'true' : 'false'
        ]);
        $res = null; // send_post($url, $data);
        $res[1] = json_decode($res[1], true);
        $res[1] = $this->decode_base64_judge0_submission($res[1]);
        return $res;
    }

    // 将judge0查询结果(base64)解码， 并汇总报错信息为error_info字段
    private function decode_base64_judge0_submission($s)
    {
        if (isset($s['message'])) $s['message'] = base64_decode($s['message']);
        if (isset($s['compile_output'])) $s['compile_output'] = base64_decode($s['compile_output']);
        if (isset($s['stderr'])) $s['stderr'] = base64_decode($s['stderr']);
        if (isset($s['stdout'])) $s['stdout'] = base64_decode($s['stdout']);
        if (isset($s['time'])) $s['time'] *= 1000;  // convert to MS for lduoj_web
        if (isset($s['memory'])) $s['memory'] = round($s['memory'] / 1024.0, 2); // convert to MB for lduoj_web
        $s['error_info'] = implode(PHP_EOL, array_filter([
            $s['message'] ?? null,
            $s['compile_output'] ?? null,
            $s['stderr'] ?? null,
        ]));
        unset($s['message']);
        unset($s['compile_output']);
        unset($s['stderr']);
        return $s;
    }
}
