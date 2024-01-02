@php
echo "
<?php
"
@endphp

namespace {{$namespace}};

/**
 {{$tableAnnotation}}
 {{$tableFieldAnnotation}}
 */
class {{$tableClassName}} extends BaseModel
{
    use SoftDeletes;

    public $table = '{{$tableName}}';

    protected $connection = '{{$mysqlConnectionName}}';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    const TIMESTAMP  = false;

    protected $dates = ['deleted_at'];

    public $fillable = [
    	'{!! $fieldList !!}'
    ];

    protected $casts = [

    ];


}