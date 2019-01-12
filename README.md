# baidubce
Baidubce百度云DOC，YII2组件封装

百度云文档服务DOC（Document Service）是百度云 BCE (Baidu Cloud Engine) 提供的面向文档处理的 PaaS (Platform as a Service) 服务平台，为开发者提供Office、WPS等多种格式文档的存储、管理及在线浏览服务。您无需了解文档存储、转码、分发、在线浏览等技术细节，即可快速搭建安全可靠、高可定制的文档处理平台和应用，助力在线教育、企业网盘等业务的转型升级。

>终于把百度云的坑踩完了，大家都知道接入第三方平台接口，授权签名是特别麻烦的事情，任何一个步骤都不能出错。于是我抽空整理并上传到GitHub希望以后如有该需求，就可以直接拿来用了，也许能还能帮到其他人。

### 遇到的坑
百度云的其他服务基本都有PHP的SDK，目前为止就文档服务DOC只有java的SDK没有PHP的SDK，没办法只能自己来啃API了。
>坑一：文档没有清晰说明业务流程（让程序员猜去吧）

>坑二：api错误提示不友好（继续猜）

>坑三：文档表意不清楚（导致走很多弯路）

### 百度云文档服务DOC接入方式
这个地方官方说的不是很清楚，我也是摸索出来的。
>第一种：首先注册文档会返回BOS信息，接下来用BOS信息将文件传入BOS，最后发布文档

>第二种：直接从BOS导入，然后发布。这种方式对BOS有要求限制，见官方文档。

### 使用说明
由于时间关系，代码没有做完整的封装，核心代码都都封装好，稍微研究下直接拿来用即可。


### 目录文件说明
>bce_php_sdk-0.9.2  是直接从百度云下载的通用sdk，因为这里用到了文档是直接上传到BOS的，后面API接入步骤会讲到。

>bce-doc  中是对DOC的授权签名验证的封装，包括http请求

>Doc.php 是针对Yii2的封装的DOC组件

>配置 我这里用的Yii2 你可以根据自己的框加来替换成自己的配置读取

### 关于
有什么疑问可以加我QQ：295124540和我交流，由于时间关系，没有完善index.php运行dom。
