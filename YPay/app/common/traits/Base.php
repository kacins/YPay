<?php

namespace app\common\traits;

use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Request;
use think\facade\Route;
use think\Response;

/**
 * Trait Base
 * @package app\common\traits
 */
trait Base
{
    /**
     * 操作错误跳转的快捷方法
     * @param string      $msg
     * @param string|null $url
     * @param string      $data
     * @param int         $wait
     * @param array       $header
     * @return object
     */
    public function error (string $msg = '', string $url = null, $data = '', int $wait = 3, array $header = []): object
    {
        if (is_null($url)) {
            $url = Request::isAjax() ? '' : 'javascript:history.back(-1);';
        } else if ($url) {
            $url = (strpos($url, '://') || str_starts_with($url, '/')) ? $url : Route::buildUrl($url);
        }
        $result = ['code' => 0, 'msg' => $msg, 'data' => $data, 'url' => $url, 'wait' => $wait,];
        $type   = $this->getResponseType();

        if ('html' == strtolower($type)) {
            $response = Response::create(Config::get('app.error_tmpl'), 'view')->assign($result)->header($header);
        } else {
            $response = Response::create($result, $type)->header($header);
        }
        throw new HttpResponseException($response);
    }

    /**
     * 操作成功跳转的快捷方法
     * @param string      $msg
     * @param string|null $url
     * @param string      $data
     * @param int         $wait
     * @param array       $header
     * @return object
     */
    public function success ($msg = '', string $url = null, $data = '', int $wait = 3, array $header = []): object
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } else if ($url) {
            $url = (strpos($url, '://') || str_starts_with($url, '/')) ? $url : Route::buildUrl($url);
        }
        $result = ['code' => 1, 'msg' => $msg, 'data' => $data, 'url' => $url, 'wait' => $wait,];
        $type = $this->getResponseType();
        if ('html' == strtolower($type)) {
            $response = Response::create(Config::get('app.success_tmpl'), 'view')->assign($result)->header($header);
        } else {
            $response = Response::create($result, $type)->header($header);
        }
        throw new HttpResponseException($response);
    }

    /**
     * 渲染404页面
     * @param bool $json
     * @return object
     */
    public function error_404 (bool $json = false): object
    {
        $result = Config::get('app.error_404_tmpl');
        if ($json || Request::isJson() || Request::isAjax()) {
            $result = $this->json(99999);
        }
        $response = Response::create($result, 'view')->code(404)->header([]);
        throw new HttpResponseException($response);
    }

    /**
     * 格式化返回Json数据
     * @param string|null $msg
     * @param int         $code
     * @param array       $data
     * @param array       $extend
     * @param int         $httpCode
     * @return object
     */
    public function json (string $msg = null,int $code = 200,array $data = [],$extend = [],int $httpCode = 200): object
    {
        $result = [
            'msg' => $msg,
            'code'  => $code,
            'time' => time()
        ];
        if (!empty($data)) {
            $result['data'] = $data;
        }
        if (!empty($extend)) {
            foreach ($extend as $k => $v) {
                $result[$k] = $v;
            }
        }
        $response = Response::create($result, 'json', $httpCode);
        throw new HttpResponseException($response);
    }

    /**
     * 获取当前的Response 输出类型
     * @return string
     */
    protected function getResponseType (): string
    {
        return Request::isJson() || Request::isAjax() ? 'json' : 'html';
    }
    
    /**
     * 在数据列表中搜索
     * @param array $list
     * @param       $condition
     * @return array
     */
    public function list_search (array $list, array $condition): array
    {
        $resultSet = [];
        foreach ($list as $key => $data) {
            $find = false;
            foreach ($condition as $field => $value) {
                $value = (string) $value;
                if (isset($data[$field])) {
                    if (str_starts_with($value, '/')) {
                        $find = preg_match($value, $data[$field]);
                    } else if ($data[$field] == $value) {
                        $find = true;
                    }
                }
            }
            if ($find)
                $resultSet[] = &$list[$key];
        }
        return count($resultSet) === 1 ? current($resultSet) : $resultSet;
    }

    /**
     * 数组转换成Tree树结构
     * @param array  $list
     * @param string $pk
     * @param string $pid
     * @param string $child
     * @param int    $root
     * @return array
     */
    public function list_to_tree (array $list, string $pk = 'id', string $pid = 'pid', string $child = '_child', int $root = 0): array
    {
        $tree = $refer = [];
        if (is_array($list)) {
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent           =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 数组排序
     * @param        $list
     * @param        $field
     * @param string $sortby
     * @return array
     */
    public function list_sort_by (array $list, string $field, string $sortby = 'asc'): array
    {
        $refer = $resultSet = [];
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc':// 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }

    /**
     * 获取所有子ID
     * @param array  $list
     * @param string $child
     * @return array
     */
    public function children_id (array $list, string $child = '_child'): array
    {
        $tree = $refer = [];
        if (is_array($list)) {
            foreach ($list as $key => $data) {
                if (!isset($data[$child])) {
                    $tree[] = $list[$key]['id'];
                } else {
                    $tree = array_merge($tree, $this->children_id($data[$child], $child));
                }
            }
        }
        return $tree;
    }
}