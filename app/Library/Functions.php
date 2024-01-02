<?php

namespace App\Library;

/**
 * 工具类，必须同时满足以下条件
 * 1、通用且与业务无关
 * 2、支持迁移（不能使用框架特性函数）
 */
class Functions
{
    /**
     * 判断变量是否为空
     * @param mixed $data 数据
     *
     * @return bool
     * @Author Johnson
     */
    public static function isEmpty($data): bool
    {
        return is_array($data) ? empty($data) : (is_null($data) || $data === '');
    }

    /**
     * K-V数据设置
     * @param array  $data
     * @param string $key
     * @param mixed  $value
     */
    public static function setVal(array &$data, string $key, $value)
    {
        $keys = explode('.', $key);
        $count = count($keys) - 1;
        foreach ($keys as $idx => $subkey) {
            // 赋值操作
            if ($subkey == '[]') {
                $data[] = $value;
                break;
            } elseif ($idx === $count) {
                $data[$subkey] = $value;
                break;
            }
            // 检查键名是否存在
            if (!key_exists($subkey, $data)) {
                $data[$subkey] = [];
            }
            // 移动游标
            $data = &$data[$subkey];
        }
    }

    /**
     * 将扁平化数据格式化为树
     * @param  array  $items 待格式化数据
     * @param  string $id    数据ID
     * @param  string $pid   数据父ID
     * @param  string $son   子节点名
     * @param  bool   $rmPid 是否删除Pid
     *
     * @return array
     * @author Johnson
     */
    public static function genTree(array $items, string $id = 'id', string $pid = 'pid', string $son = 'children', bool $rmPid = false): array
    {
        // 临时扁平数据
        $tempMap = [];
        foreach ($items as $item) {
            if ($rmPid) unset($item[$pid]);
            $tempMap[$item[$id]] = $item;
        }
        // 将扁平数据格式化为树
        $tree = [];
        foreach ($items as $item) {
            if (isset($tempMap[$item[$pid]])) {
                $tempMap[$item[$pid]][$son][] = &$tempMap[$item[$id]];
            } else {
                $tempMap[$item[$id]][$son] = $tempMap[$item[$id]][$son] ?? [];
                $tree[] = &$tempMap[$item[$id]];
            }
        }
        // 销毁临时变量，返回结果
        unset($tempMap);
        return $tree;
    }

    /**
     * @param $list
     * @param $
     * @param $select
     * @param $id
     * @param $pid
     * @param $children
     * @return array
     */
    public static function arrayTree($list, $select = [], $id = 'id', $pid = 'parent_id', $children = 'children')
    {
        list($tree, $map) = [[], []];
        foreach ($list as $item) {
            $item['is_selected']   = in_array($item['id'], $select) ? 1 : 0;
            $item['is_parent'] = 0;
            $map[$item[$id]] = $item;
        }
        foreach ($list as $item) {
            if (isset($item[$pid]) && isset($map[$item[$pid]])) {
                $map[$item[$pid]][$children][] = &$map[$item[$id]];
                $map[$item[$pid]]['is_parent'] = 1;
                $isSelected = in_array($item['id'], $select) ? 1 : 0;
                if ($isSelected) {
                    $map[$item[$pid]]['is_selected'] = $isSelected;
                }
            } else {
                $tree[] = &$map[$item[$id]];
            }
        }
        unset($map);

        return $tree;
    }

    /**
     * 根据父级id，获取所有子级id
     * @param array $items      待检索数据
     * @param int $pid          数据父ID值
     * @param string $idStr     数据ID名
     * @param string $pidStr    数据父ID名
     * @return array
     */
    public static function getChildrenIdsByPid(array $items, int $pid, string $idStr = 'id', string $pidStr = 'pid')
    {
        $childrenIds = [];
        foreach ($items as $v) {
            if ($v[$pidStr] == $pid) {
                $childrenIds[] = $v[$idStr];
                $childrenIds = array_merge($childrenIds, self::getChildrenIdsByPid($items, $v[$idStr]));
            }
        }
        return $childrenIds;
    }

    /**
     * 将字符串部分数据进行加星处理
     * @param string $str   原始字符串
     * @param int    $start 起始长度
     * @param int    $end   结尾长度
     * @param int    $len   星号长度
     *
     * @return string
     */
    public static function strcut(string $str, int $start = 1, int $end = 1, int $len = 1)
    {
        // 特殊处理Email
        if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
            $email = explode('@', $str);
            $prestr = strlen($email[0]) <= 2 ? $email[0] : substr($email[0], 0, 2);
            return "{$prestr}***@{$email[1]}";
        }

        // 其他字符串加星处理
        $length = mb_strlen($str, 'utf-8');
        if ($length > 2) {
            $first = mb_substr($str, 0, $start >= $length ? $length - 1 : $start, 'utf-8');
            $last  = $start+$end <= $length-1 ? mb_substr($str, -1*$end, $end, 'utf-8') : '';
            $len   = $len <= 3 ? $length - mb_strlen($first) - mb_strlen($last) : $len;
        } else {
            $first = $length <= 1 ? $str : mb_substr($str, 0, 1, 'utf-8');
            $last  = '';
            $len   = 1;
        }

        // 返回结果
        return $first . str_repeat('*', $len) . $last;
    }

    public static function uniqueValue($data, string $field)
    {
        $values = [];
        foreach ($data as $item) {
            $subData = is_object($item) ? $item->$field : $item[$field];
            foreach ($subData ?? [] as $value) {
                if (!in_array($value, $values)) {
                    $values[] = $value;
                }
            }
        }
        return $values;
    }

    /**
     * 根据子级id，获取对应所有父级，默认返回 id 字段值
     * 如果传入子级id为空，则返回所有
     * @param array $items
     * @param array $ids
     * @return array
     */
    public static function getParentsByIds(array $items, array $ids, string $returnField = 'id')
    {
        $result = [];
        $items = array_column($items, null, 'id');
        $map = array_combine(array_column($items, 'id'), array_column($items, 'pid'));

        if (empty($ids)) {
            $ids = array_column($items, 'id');
        }

        foreach ($ids as $id) {
            $temp = [];
            self::joinPid($map, $id, $temp);
            foreach ($temp as $value) {
                if (!isset($items[$value])) continue;
                $result[$id][] = $items[$value][$returnField];
            }
        }
        return $result;
    }

    public static function joinPid(&$map, $id, &$res){
        // 如果其pid不为0, 则继续查找
        if(isset($map[$id]) && $map[$id] != 0){
            self::joinPid($map, $map[$id], $res);
        }
        $res[] = $id;
    }

    /**
     * 根据多个父级id，获取所有子级id
     * @param array $items 待检索数据
     * @param array $pids   数据父ID值
     * @param string $idStr 数据ID名
     * @param string $pidStr 数据父ID名
     * @return array
     */
    public static function getChildrenIdsByPids(array $items, array $pids, string $idStr = 'id', string $pidStr = 'pid')
    {
        $childrenIds = [];
        foreach ($pids as $pid) {
            $childrenIds[] = $pid;
            $childrenIds = array_merge($childrenIds, self::getChildrenIdsByPid($items, $pid, $idStr, $pidStr));
        }
        return array_unique($childrenIds);
    }

    /**
     * 获取异常信息
     * @param \Throwable $e
     * @return string
     */
    public static function getError(\Throwable $e): string
    {
        return sprintf("%s in %s on line %d", $e->getMessage(), $e->getFile(), $e->getLine());
    }

    // 获取固定长度的随机字符串
    public static function getRandStr($length)
    {
        //字符组合
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
        $len = strlen($str) - 1;
        $randstr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }

    /**
     * 去除url中的查询字符串
     * @param string $url
     *
     * @return string
     */
    public static function trimUrlQueryStr(string $url): string
    {
        $parsedUrl = parse_url($url);
        $portStr   = isset($parsedUrl['port']) ? ":{$parsedUrl['port']}" : "";
        return "{$parsedUrl['scheme']}://{$parsedUrl['host']}{$portStr}{$parsedUrl['path']}";
    }

    /**
     * 将数组的值赋值给对象属性
     * @param array $array 数组
     * @param string $className 对象名
     * @return object 对象实例
     * @throws \ReflectionException
     * @author kim
     * @date 2023-09-25 15:19
     */
    public static function arrayToClass(array $array, string $className)
    {
        $ref = new \ReflectionClass($className);
        $ins = $ref->newInstance();
        foreach ($ref->getProperties() as $property) {
            $propertyName = $property->getName();
            $val = $array[$propertyName] ?? null;
            $ins->$propertyName = $val;
        }
        return $ins;
    }
}
