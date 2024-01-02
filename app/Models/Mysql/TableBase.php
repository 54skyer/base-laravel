<?php

namespace App\Models\Mysql;

/**
 * 基础模板表
 *
 * @property integer  id              主键ID
 * @property boolean  deleted         是否删除：1是0否
 * @property boolean  status          状态：0初始状态
 * @property string   description     记录描述
 * @property datetime created_at      创建记录时间
 * @property datetime updated_at      更新记录时间
 * @property integer  created_by      创建记录用户ID：0为系统
 * @property integer  updated_by      更新记录用户ID：0为系统
 */
class TableBase extends BaseModel
{
    use SoftDeletes;

    public $table = 'table_base';

    protected $connection = 'mysql';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    const TIMESTAMP  = false;

    protected $dates = ['deleted_at'];

    public $fillable = [
        'id',
        'deleted',
        'status',
        'description',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [

    ];

}