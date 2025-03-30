# Typecho-AutoWebP
一个 Typecho 插件，可以自动将上传的图片压缩并转换为 WebP 格式。

> 此项目自分叉以后已进行修改，原项目：[nkxingxh/Typecho-Up2WebP](https://github.com/nkxingxh/Typecho-Up2WebP)

## 安装
您需要在 Typecho 的插件目录 `/usr/plugins` 下新建一个名为 `AutoWebP` 的文件夹，  
然后下载仓库中的 [`Plugin.php`](AutoWebP/Plugin.php) 文件并添加进 `AutoWebP` 文件夹中，即可。

## 设置
您可以在插件设置页面中设置要处理的文件扩展名、压缩阈值、图片质量、文件命名方式，具体可参考每项设置的描述。

文件命名方式支持：
- 默认（CRC32）
- UUID
- SHA1 - 以上传的文件来计算
- MD5 - 以上传的文件来计算
- 秒级时间戳
- 毫秒级时间戳
- 8 位随机字符串 - MD5 的前截取值（较容易重复）
    当生成 100,000 个这样的字符串时，重复的概率大约是 23%
- 16 位随机字符串 - MD5 的前截取值

## 使用
在启用插件后，上传的附件的文件扩展名在设置列表中时，您应该就可以看见图片上传后已经转换成了 `.webp` 格式。

## 许可证
AutoWebP（原 Up2WebP）使用 AGPL-3.0 许可证，  
详情参见 [LICENSE](LICENSE)
