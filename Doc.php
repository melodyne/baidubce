<?php
namespace common\components;

use common\extend\baidubce\BceApi;
use Yii;
use yii\base\Component;

use BaiduBce\Services\Bos\BosClient;


class Doc extends Component
{
	private static $_instance;
	private $config = [];
	private $credient = [];
    private $host = '';
    public $error = '';

	public static function instance()
	{
		if(!self::$_instance)
		{
			self::$_instance = Yii::createObject(self::className());
			self::$_instance->setConfig([
			    'host'=>'doc.bj.baidubce.com',
			    'access_key' => Yii::$app->params['baidu']['access_key'],
			    'secret_key' => Yii::$app->params['baidu']['secret_key'],
			]);
		}
		return self::$_instance;
	}

	public function setConfig($config)
	{
		$this->config = $config;
        $this->host = $config['host'];
        $this->credient = [
            "ak" => $config['access_key'],
            "sk" => $config['secret_key']
        ];
	}

	// ====================================下面是接口封装===================================

    /**
     * 上传doc
     */
    function upload($file,$title,$format='ppt'){
        $typeArr = ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'vsd', 'pot', 'pps', 'rtf', 'wps', 'et', 'dps', 'pdf', 'txt', 'epub'];
        if(!in_array($format,$typeArr)){
            $this->error = '不支持'.$format.'格式得文档！';
            return false;
        }
        $method = 'POST';
        $path = '/v2/document';
        $query=['register'=>''];
        $params = [
            'title'=>$title,// 文档标题，不超过50字符
            'format'=>$format,// 文档格式。有效值：doc, docx, ppt, pptx, xls, xlsx, vsd, pot, pps, rtf, wps, et, dps, pdf, txt, epub
            'targetType'=>'h5',
            'access'=>'PRIVATE'//文档权限。有效值：PUBLIC、PRIVATE，默认值：PUBLIC，表示公开文档，设为PRIVATE时表示私有文档
        ];

        // 第一步：注册文档
        $bd = new BceApi();
        $promise = $bd->request($this->host,$this->credient,$method,$path,$query,$params);
        $data = [];
        $promise->then(function ($response) use ($file,$title,$format,&$data) {
            $body = $response->getBody();
            $data=json_decode($body,true);

            // 第二步：上传到BOS
            $this->uploadToBos($file,$data['bucket'],$data['object']);

            // 第三步：发布文档
            $this->publish($data['documentId']);
        })->wait();

        return $data;
    }

    /**
     * 将文件上传到BOS
     */
    function uploadToBos($file,$bucketName,$objectKey,$fileName=''){

        if(!file_exists($file)){
            throw new \Exception('文件不存在！');
        }

        require_once dirname(__DIR__).'/extend/baidubce/bce-php-sdk-0.9.2/BaiduBce.phar';
        require_once dirname(__DIR__).'/extend/baidubce/bce-php-sdk-0.9.2/SampleConf.php';

        //调用配置文件中的参数
        //global $BOS_TEST_CONFIG;
        //新建BosClient
        $client = new BosClient($BOS_TEST_CONFIG);

        // 以数据流形式上传Object
        //$client->putObject($bucketName, $objectKey, $data);

        // 从字符串中上传Object
        //$client->putObjectFromString($bucketName, $objectKey, $string);

        // 从文件中直接上传Object
        return $client->putObjectFromFile($bucketName,$objectKey,$file);
    }

    /**
     * 根据BOS Object创建文档
     * 通过BOS Object路径，用从BOS导入的方法创建文档。
     */
    function createByBos($bucket,$object,$title,$format){
        $method = 'POST';
        $path = '/v2/document';
        $query = [
            'source'=>'bos'
        ];
        $params = [
            'bucket'=>$bucket,
            'object'=>$object,
            'title'=>$title,// 文档标题，不超过50字符
            'format'=>$format,//文档格式 默认值：BOS Object后缀名（当BOS Object有后缀时）
            'targetType'=>'h5',//转码结果类型
            'access'=>'PUBLIC'//PUBLIC、PRIVATE，默认值：PUBLIC，表示公开文档，设为PRIVATE时表示私有文档
        ];

        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query,$params);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }

    /**
     * 发布文档
     */
    function publish($documentId){
        $method = 'PUT';
        $path = '/v2/document/'.$documentId;
        $query = [
            'publish'=>''
        ];
        $params = [
            'documentId'=>$documentId,//文档的唯一标识
        ];
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query,$params);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }

    /**
     * 重新发布文档
     */
    function republish($documentId){
        $method = 'PUT';
        $path = '/v2/document/'.$documentId;
        $query = [
            'rerun'=>''
        ];
        $params = [];
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query,$params);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }

    /**
     * 文档列表
     */
    function pageList($status = 'PUBLISHED',$marker='',$maxSize=20){
        $method = 'GET';
        $path = '/v2/document/';
        $query = [
            'status'=>$status,// 文档状态。有效值：UPLOADING/PROCESSING/PUBLISHED/FAILE
            'marker'=>$marker,// 起始位置
            'maxSize'=>$maxSize// 本次请求的文档数目
        ];
        $params = [];
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query,$params);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }

    /**
     * 查询文档详情
     */
    function detail($documentId,$isHttps=false){
        $method = 'GET';
        $path = '/v2/document/'.$documentId;
        $query = [
            'https'=>false
        ];
        $params = [];
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query,$params);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }

    /**
     * 阅读文档
     */
    function read($documentId ){
        $method = 'GET';
        $path = '/v2/document/'.$documentId;
        $query = [
            'read'=>$documentId,
            'expireInSeconds'=>Yii::$app->params['baidu']['expire_time'],// 阅读Token有效期
        ];
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query);
        $data=[];
        $promis->then(function ($response)use (&$data)  {
            $body = $response->getBody();
            $data=json_decode($body,true);
        })->wait();
        return $data;
    }

    /**
     * 禁用阅读Token
     */
    function disableReadToken($documentId,$token){
        $method = 'PUT';
        $path = '/v2/document/'.$documentId;
        $query = [
            'disableReadToken'=>''
        ];
        $params = [
            'token'=>$token
        ];
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query,$params);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }

    /**
     * 下载文档
     */
    function download($documentId,$expireInSeconds=-1,$isHttps=false){
        $method = 'PUT';
        $path = '/v2/document/'.$documentId;
        $query = [
            'download'=>'',
            'expireInSeconds'=>$expireInSeconds,
            'https'=>$isHttps
        ];
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }

    /**
     * 查询文档转码结果图片列表
     */
    function getImages($documentId){
        $method = 'PUT';
        $path = '/v2/document/'.$documentId;
        $query = [
            'getImages'=>''
        ];
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path,$query);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }

    /**
     * 删除文档
     * 删除文档，仅对状态status不是PROCESSING时的文档有效，清除文档占用的存储空间。
     */
    function delete($documentId){
        $method = 'DELETE';
        $path = '/v2/document/'.$documentId;
        $bd = new BceApi();
        $promis = $bd->request($this->host,$this->credient,$method,$path);
        $data = [];
        $promis->then(function ($response) use(&$data) {
            $data = json_encode($response->getBody());
        })->wait();
        return $data;
    }


}