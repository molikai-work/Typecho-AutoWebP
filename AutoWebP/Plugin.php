<?php
/**
 * 将上传的图片转换为 WebP 格式，修改自 NKXingXh 的 Up2WebP
 *
 * @package AutoWebP
 * @author molikai-work
 * @version 1.2.0
 * @link https://github.com/molikai-work/Typecho-AutoWebP
 * @license https://www.gnu.org/licenses/agpl-3.0.html
 */

if (!defined("__TYPECHO_ROOT_DIR__")) {
    exit();
}

class AutoWebP_Plugin extends Widget_Upload implements Typecho_Plugin_Interface {
    /**
     * 启用插件方法，如果启用失败，将抛出异常
     *
     * @static
     * @access public
     * @return void
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('AutoWebP_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('AutoWebP_Plugin', 'modifyHandle');
    }

    /**
     * 禁用插件方法，如果禁用失败，将抛出异常
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate() {}

    /**
     * 获取插件配置面板
     *
     * @static
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config($form) {
        $exts = new Typecho_Widget_Helper_Form_Element_Text(
            'exts',
            NULL,
            'jpg,jpeg,png,bmp,wbmp',
            _t('图片扩展名'),
            _t('扩展名在该列表内的文件才进行处理。不要使用大写, 扩展名之间用半角逗号隔开, 不要加空格<br><b>本插件需要 PHP 安装 GD 库才能正常运行</b>')
        );

        $min_size = new Typecho_Widget_Helper_Form_Element_Text(
            'min_size',
            NULL,
            '256',
            _t('压缩阈值'),
            _t('超过该大小的图片才进行压缩, 单位 KB')
        );

        $quality = new Typecho_Widget_Helper_Form_Element_Text(
            'quality',
            NULL,
            '85',
            _t('图片质量'),
            _t('压缩后的图片质量, 1~100')
        );

        $filename_type = new Typecho_Widget_Helper_Form_Element_Select(
            'filename_type',
            array(
                'crc32' => '默认（CRC32）',
                'uuid' => 'UUID',
                'sha1' => 'SHA1',
                'md5' => 'MD5',
                'timestamp' => '秒级时间戳',
                'millisecond' => '毫秒级时间戳',
                'random8' => '8 位随机字符串',
                'random16' => '16 位随机字符串'
            ),
            'crc32',
            _t('文件命名方式'),
            _t('选择上传图片转换为 WebP 时的文件命名方式')
        );

        $watermark_text = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_text',
            NULL,
            '',
            _t('水印文本'),
            _t('输入需要添加到图片的水印文本，默认不添加。')
        );

        $watermark_color = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_color',
            NULL,
            '#F4F4F4',
            _t('水印颜色'),
            _t('使用十六进制颜色代码，如 #F4F4F4')
        );

        $watermark_opacity = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_opacity',
            NULL,
            '0.6',
            _t('水印透明度'),
            _t('输入 0 到 1 之间的数字，例如 0.6 代表 60% 透明度')
        );

        $watermark_font_size = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_font_size',
            NULL,
            '5',
            _t('水印字体大小'),
            _t('输入水印字体大小的百分比值（相对于图片较小的一边）。例如输入 5 表示字体大小为图片最小边长的 5%。')
        );

        $watermark_position = new Typecho_Widget_Helper_Form_Element_Select(
            'watermark_position',
            array(
                'top-left' => '左上角',
                'top-right' => '右上角',
                'bottom-left' => '左下角',
                'bottom-right' => '右下角',
                'center' => '居中'
            ),
            'bottom-right',
            _t('水印位置'),
            _t('选择水印在图片中的位置')
        );

        $watermark_margin_top = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_margin_top',
            NULL,
            '20',
            _t('水印上边距'),
            _t('以像素为单位')
        );

        $watermark_margin_bottom = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_margin_bottom',
            NULL,
            '20',
            _t('水印下边距'),
            _t('以像素为单位')
        );

        $watermark_margin_left = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_margin_left',
            NULL,
            '20',
            _t('水印左边距'),
            _t('以像素为单位')
        );

        $watermark_margin_right = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_margin_right',
            NULL,
            '20',
            _t('水印右边距'),
            _t('以像素为单位')
        );

        $watermark_font_file = new Typecho_Widget_Helper_Form_Element_Text(
            'watermark_font_file',
            NULL,
            'SourceHanSansSC-Regular.otf',
            _t('水印字体文件'),
            _t('输入水印字体文件的名称，放到当前插件目录下的 font 文件夹中')
        );

        $form->addInput($exts);
        $form->addInput($min_size);
        $form->addInput($quality);
        $form->addInput($filename_type);
        $form->addInput($watermark_text);
        $form->addInput($watermark_color);
        $form->addInput($watermark_opacity);
        $form->addInput($watermark_font_size);
        $form->addInput($watermark_position);
        $form->addInput($watermark_margin_top);
        $form->addInput($watermark_margin_bottom);
        $form->addInput($watermark_margin_left);
        $form->addInput($watermark_margin_right);
        $form->addInput($watermark_font_file);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig($form) {}

    /**
     * 生成 UUID（版本 4）
     *
     * @access private
     * @static
     * @return string 返回生成的 UUID 字符串
     */
    private static function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * 根据不同的命名规则生成文件名
     * 
     * @access private
     * @static
     * @param string $filePath 文件的路径，用于生成哈希值
     * @param string $ext 文件的扩展名
     * @return string 返回生成的文件名
     */
    private static function generateFileName($filePath, $ext) {
        $filenameType = Typecho_Widget::widget('Widget_Options')->plugin('AutoWebP')->filename_type;

        $sha1Hash = sha1_file($filePath);
        $md5Hash = md5_file($filePath);

        switch ($filenameType) {
            case 'timestamp':
                return time() . '.' . $ext;
            case 'uuid':
                return self::generateUUID() . '.' . $ext;
            case 'sha1':
                return $sha1Hash . '.' . $ext;
            case 'md5':
                return $md5Hash . '.' . $ext;
            case 'millisecond':
                return round(microtime(true) * 1000) . '.' . $ext;
            case 'random8':
                return substr(md5(uniqid(mt_rand(), true)), 0, 8) . '.' . $ext;
            case 'random16':
                return substr(md5(uniqid(mt_rand(), true)), 0, 16) . '.' . $ext;
            case 'crc32':
            default:
                return sprintf('%u', crc32(uniqid())) . '.' . $ext;
        }
    }

    /**
     * 为图片添加水印
     * 
     * @access private
     * @static
     * @param resource $image 要添加水印的图片资源
     * @return resource 返回添加了水印的图片资源
     * 
     * @throws Typecho_Widget_Exception 如果字体文件不存在，将抛出异常
     */
    private static function addWatermark($image) {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('AutoWebP');
        if (empty($options->watermark_text)) {
            return $image;
        }

        $text = $options->watermark_text;
        $color = $options->watermark_color;
        $opacity = floatval($options->watermark_opacity);
        $fontSizePercentage = floatval($options->watermark_font_size) / 100;
        $position = $options->watermark_position;
        $marginTop = intval($options->watermark_margin_top);
        $marginBottom = intval($options->watermark_margin_bottom);
        $marginLeft = intval($options->watermark_margin_left);
        $marginRight = intval($options->watermark_margin_right);

        $fontFile = __DIR__ . '/font/' . $options->watermark_font_file;

        $width = imagesx($image);
        $height = imagesy($image);

        $fontSize = max(10, min($width, $height) * $fontSizePercentage);

        if (!file_exists($fontFile)) {
            throw new Typecho_Widget_Exception(_t('字体文件不存在'));        
        }

        $rgba = sscanf($color, "#%02x%02x%02x");
        $colorAllocated = imagecolorallocatealpha($image, $rgba[0], $rgba[1], $rgba[2], (1 - $opacity) * 127);

        $box = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = abs($box[4] - $box[0]);
        $textHeight = abs($box[5] - $box[1]);

        switch ($position) {
            case 'top-left':
                $x = $marginLeft;
                $y = $marginTop + $textHeight;
                break;
            case 'top-right':
                $x = $width - $textWidth - $marginRight;
                $y = $marginTop + $textHeight;
                break;
            case 'bottom-left':
                $x = $marginLeft;
                $y = $height - $marginBottom;
                break;
            case 'bottom-right':
                $x = $width - $textWidth - $marginRight;
                $y = $height - $marginBottom;
                break;
            case 'center':
                $x = ($width - $textWidth) / 2;
                $y = ($height + $textHeight) / 2;
                break;
        }

        imagettftext($image, $fontSize, 0, $x, $y, $colorAllocated, $fontFile, $text);

        return $image;
    }

    /**
     * 处理文件上传
     * 
     * @access public
     * @static
     * @param array $file 上传的文件数组，包含文件的临时路径、名称和字节等信息
     * @return array|false 返回一个包含文件信息的数组（文件名、路径、大小等），如果上传失败则返回 false
     */
    public static function uploadHandle(array $file) {
        $ext = self::getSafeName($file['name']);

        if (!self::checkFileType($ext)) {
            return false;
        }

        $date = new Typecho_Date();
        $path = Typecho_Common::url(
            defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__
        ) . '/' . $date->year . '/' . $date->month;

        // 创建上传目录
        if (!is_dir($path)) {
            if (!self::makeUploadDir($path)) {
                return false;
            }
        }

        $exts = explode(',', Typecho_Widget::widget('Widget_Options')->plugin('AutoWebP')->exts);
        if ($autowebp = in_array(strtolower($ext), $exts)) {
            // 获取文件名
//            $fileName_webp = sprintf('%u', crc32(uniqid())) . '.webp';
            $fileName_webp = self::generateFileName($file['tmp_name'], 'webp');
            $path_webp = $path . '/' . $fileName_webp;
        }
        unset($exts);

        // 获取文件名
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $path . '/' . $fileName;

        if (isset($file['tmp_name'])) {
            if ($autowebp) {
                $result = self::image2webp($file['tmp_name'], $path_webp, $ext);
                if ($result === false) return false;
                $autowebp = $result === true;
            }

            // 移动上传文件
            if (!$autowebp && !@move_uploaded_file($file['tmp_name'], $path)) {
                return false;
            }
        } elseif (isset($file['bytes'])) {
            // 直接写入文件
            if (!file_put_contents($path, $file['bytes'])) {
                return false;
            }

            if ($autowebp) {
                $result = self::image2webp($path, $path_webp, $ext);
                if ($result === false) return false;
                $autowebp = $result === true;

                if ($autowebp) {
                    // 成功压缩删除老文件
                    unlink($path);
                }
            }
        } elseif (isset($file['bits'])) {
            // 直接写入文件
            if (!file_put_contents($path, $file['bits'])) {
                return false;
            }

            if ($autowebp) {
                $result = self::image2webp($path, $path_webp, $ext);
                if ($result === false) return false;
                $autowebp = $result === true;

                if ($autowebp) {
                    // 成功压缩删除老文件
                    unlink($path);
                }
            }
        } else {
            return false;
        }

        if ($autowebp) {
            // 替换变量
            $fileName = $fileName_webp;
            $path = $path_webp;
            $ext = 'webp';
            $file['name'] = self::getNewName($file['name']);
            unset($file['size']);   // 等下重新计算
        }

        if (!isset($file['size'])) {
            $file['size'] = filesize($path);
            if ($file['size'] <= 0) {
                unlink($path);
                return false;
            }
        }

        // 返回相对存储路径
        return [
            'name' => $file['name'],
            'path' => (defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR)
                . '/' . $date->year . '/' . $date->month . '/' . $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => Typecho_Common::mimeContentType($path)
        ];
    }

    public static function modifyHandle(array $content, array $file) {
        $ext = self::getSafeName($file['name']);

        $exts = explode(',', Typecho_Widget::widget('Widget_Options')->plugin('AutoWebP')->exts);
        $autowebp = strtolower($content['attachment']->type) == 'webp' && in_array(strtolower($ext), $exts);
        unset($exts);

        if ($content['attachment']->type != $ext && !$autowebp) {
            return false;
        }

        $path = Typecho_Common::url(
            $content['attachment']->path,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__
        );
        $dir = dirname($path);


        // 创建上传目录
        if (!is_dir($dir)) {
            if (!self::makeUploadDir($dir)) {
                return false;
            }
        }

        if (isset($file['tmp_name'])) {
            @unlink($path);

            if ($autowebp) {
                $result = self::image2webp($file['tmp_name'], $path, $ext, -1);
                if (!$result) { // 这种情况失败了就返回
                    return false;
                }
                $autowebp = $result === true;
            }

            // 移动上传文件
            elseif (!$autowebp && !@move_uploaded_file($file['tmp_name'], $path)) {
                return false;
            }
        } elseif (isset($file['bytes'])) {
            @unlink($path);

            // 直接写入文件
            if (!file_put_contents($path, $file['bytes'])) {
                return false;
            }

            if ($autowebp) {
                $result = self::image2webp($path, $path, $ext);
                if (!$result) return false;
                $autowebp = $result === true;
                // 这里直接替换了，不存在“老文件”，不需要额外操作
            }
        } elseif (isset($file['bits'])) {
            @unlink($path);

            // 直接写入文件
            if (!file_put_contents($path, $file['bits'])) {
                return false;
            }

            if ($autowebp) {
                $result = self::image2webp($path, $path, $ext);
                if (!$result) return false;
                $autowebp = $result === true;
                // 这里直接替换了，不存在“老文件”，不需要额外操作
            }
        } else {
            return false;
        }

        if ($autowebp) {
            unset($file['size']);   // 等下重新计算
        }

        if (!isset($file['size'])) {
            $file['size'] = filesize($path);
        }

        // 返回相对存储路径
        return [
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        ];
    }

    private static function getNewName($name) {
        $info = pathinfo($name);
        return $info['filename'] . '.webp';
    }

    /**
     * 获取安全的文件名
     *
     * @param string $name
     * @return string
     */
    private static function getSafeName(string &$name): string {
        $name = str_replace(['"', '<', '>'], '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);

        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

    /**
     * 创建上传路径
     *
     * @param string $path 路径
     * @return boolean
     */
    private static function makeUploadDir(string $path): bool {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last, 0755)) {
            return false;
        }

        return self::makeUploadDir($path);
    }

    /**
     * 将图片转换为 webp 格式
     * 
     * @param string input 输入文件路径
     * @param string output 输出文件路径
     * @param string ext 文件扩展名 (为空将尝试判断输入文件)
     * @param int min_size 处理阈值 (传入复数无视一切限制, 包括阈值、处理后大小等) 单位: 字节
     * @param int quality 质量
     * 
     * @return bool|int true 成功, false 失败, 0 变大了, null 未达到阈值
     */
    private static function image2webp($input, $output, $ext = '', $min_size = null, $quality = null) {
        if (empty($min_size)) {
            $min_size = (int) Typecho_Widget::widget('Widget_Options')->plugin('AutoWebP')->min_size * 1024;
        }

        $fileSize = filesize($input);
        if ($min_size > 0 && $fileSize < $min_size) return null;

        if (empty($quality)) {
            $quality = (int) Typecho_Widget::widget('Widget_Options')->plugin('AutoWebP')->quality;
        }

        if (function_exists('exif_imagetype')) {
            $imageType = exif_imagetype($input);
        } else {
            if (empty($ext)) {
                $info = pathinfo($input);
                $ext = $info['extension'];
                unset($info);
            }
            if (empty($ext)) {
                throw new Typecho_Widget_Exception(_t('No exif lib found and the file extension name is empty! Unable to determine file type'));
                return false;
            }
            switch (strtolower($ext)) {
                case 'gif':
                    $imageType = IMAGETYPE_GIF;
                    break;
                case 'jpeg':
                case 'jpg':
                    $imageType = IMAGETYPE_JPEG;
                    break;
                case 'png':
                    $imageType = IMAGETYPE_PNG;
                    break;
                case 'bmp':
                    $imageType = IMAGETYPE_BMP;
                    break;
                case 'wbmp':
                    $imageType = IMAGETYPE_WBMP;
                    break;
                case 'webp':
                    $imageType = IMAGETYPE_WEBP;
                    break;
                default:
                    throw new Typecho_Widget_Exception(_t('No exif lib found and this extension type is not supported'));
                    return false;
            }
        }

        switch ($imageType) {
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($input);
                break;
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($input);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($input);
                break;
            case IMAGETYPE_BMP:
                $image = imagecreatefrombmp($input);
                break;
            case IMAGETYPE_WBMP:
                $image = imagecreatefromwbmp($input);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($input);
                break;
            default:
                throw new Typecho_Widget_Exception(_t('Unsupported image type'));
                return false;
        }

        if (empty($image)) {
            throw new Typecho_Widget_Exception(_t('Failed to read image! Maybe the image type is not supported'));
            return false;
        }

        $image = self::addWatermark($image);

        if (imagewebp($image, $output, $quality)) {
            $newFileSize = filesize($output);
            if ($newFileSize <= 0) {
                unlink($output);
                throw new Typecho_Widget_Exception(_t('File is empty'));
                return false;
            }
            if ($newFileSize > $fileSize) {
                unlink($output);
                return 0;
            }
            return true;
        } else {
            unlink($output);
            throw new Typecho_Widget_Exception(_t('imagewebp failed'));
            return false;
        }
    }    
}
