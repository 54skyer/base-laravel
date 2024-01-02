<?php

namespace App\Library\FileHandler;

use Exception;
use Imagick;
use ImagickException;

class PdfHandler
{

    /**
     * pdf转图片
     * @param string $pdf  待处理的PDF文件
     * @param string $path 待保存的图片路径
     * @param string $ext
     * @param int    $page 待导出的页面 -1为全部 0为第一页 1为第二页
     * @return array 保存好的图片路径和文件名 注：此处为坑 对于Imagick中的$pdf路径 和$path路径来说，   php版本为5+ 可以使用相对路径。php7+版本必须使用绝对路径。所以，建议大伙使用绝对路径。
     * @throws ImagickException
     * @throws Exception
     */
    static function pdfToImage(string $pdf, string $path, string $ext = 'png', int $page = -1)
    {
        if (!extension_loaded('imagick')) {
            throw new Exception("imagick 扩展未加载");
        }
        if (!file_exists($pdf)) {
            throw new Exception("{$pdf} 文件未找到");
        }
        if (!is_readable($pdf)) {
            throw new Exception("{$pdf} 文件读取失败");
        }
        $im = new Imagick();
        $im->setResolution(150, 150);
        $im->setCompressionQuality(100);

        if ($page == -1) {
            $im->readImage($pdf);
        } else {
            $im->readImage($pdf . "[" . $page . "]");
        }

        $return = [];
        foreach ($im as $key => $var) {
            $var->setImageFormat($ext);
            $filename = $path . md5($key . time()) . '.' . $ext;
            if ($var->writeImage($filename)) {
                $return[] = $filename;
            }
        }
        //返回转化图片数组，由于pdf可能多页，此处返回二维数组。
        return $return;
    }

    /**
     * splice
     * @param array  $images pdf转化png  路径
     * @param string $imagePath
     * @return string 将多个图片拼接为成图的路径
     * @throws Exception
     * @internal param string $path 待保存的图片路径
     */
    static function spliceImg(array $images = [], string $imagePath = '')
    {
        $width   = 500; //自定义宽度
        $height  = null;
        $picTall = 0;//获取总高度
        foreach ($images as $key => $value) {
            $arr     = getimagesize($value);
            $height  = $width / $arr[0] * $arr[1];
            $picTall += $height;
        }
        $picTall = intval($picTall);
        // 创建长图
        $targetImg = imagecreatetruecolor($width, $picTall);
        //分配一个白色底色
        $color = imagecolorAllocate($targetImg, 255, 255, 255);
        imagefill($targetImg, 0, 0, $color);

        $tmp  = 0;
        $tmpy = 0; //图片之间的间距
        $src  = null;
        $size = null;
        foreach ($images as $k => $v) {
            $src  = Imagecreatefrompng($v);
            $size = getimagesize($v);
            //5.进行缩放
            imagecopyresampled($targetImg, $src, $tmp, $tmpy, 0, 0, $width, $height, $size[0], $size[1]);
            //imagecopy($targetImg, $src, $tmp, $tmpy, 0, 0, $size[0],$size[1]);
            $tmpy = $tmpy + $height;
            //释放资源内存
            imagedestroy($src);
            unlink($v);
        }
        if (!file_exists($imagePath)) {
            if (!self::makeDir($imagePath)) {
                /* 创建目录失败 */
                throw new Exception("图片保存路径创建失败");
            }
        }
        $returnImgPath = $imagePath . '/convert.png';
        imagepng($targetImg, $returnImgPath);
        return $returnImgPath;
    }

    /**
     * makeDir
     * @param string $folder 生成目录地址
     *                       注：生成目录方法
     * @return bool
     */
    static function makeDir($folder)
    {
        $reval = false;
        if (!file_exists($folder)) {
            /* 如果目录不存在则尝试创建该目录 */
            @umask(0);
            /* 将目录路径拆分成数组 */
            preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);
            /* 如果第一个字符为/则当作物理路径处理 */
            $base = ($atmp[0][0] == '/') ? '/' : '';
            /* 遍历包含路径信息的数组 */
            foreach ($atmp[1] as $val) {
                if ('' != $val) {
                    $base .= $val;
                    if ('..' == $val || '.' == $val) {
                        /* 如果目录为.或者..则直接补/继续下一个循环 */
                        $base .= '/';
                        continue;
                    }
                } else {
                    continue;
                }
                $base .= '/';
                if (!file_exists($base)) {
                    /* 尝试创建目录，如果创建失败则继续循环 */
                    if (@mkdir(rtrim($base, '/'), 0777)) {
                        @chmod($base, 0777);
                        $reval = true;
                    }
                }
            }
        } else {
            /* 路径已经存在。返回该路径是不是一个目录 */
            $reval = is_dir($folder);
        }
        clearstatcache();
        return $reval;
    }

    public static function convert($pdf, $imageDir, $imageExt = 'png')
    {
        $imageArr = self::pdfToImage($pdf, $imageDir, $imageExt);

        return self::spliceImg($imageArr, $imageDir);
    }
}
