<div id="upload_doc" class="form-group" style="margin-bottom: 35px">
    <input type="file" id="bac_doc_file"  accept=".ppt,.pptx,.xls,.doc,.pdf">
    <div id="bac_doc_upload_info" style="margin: 10px 0"></div>
    <button id="bac_doc_upload_btn" class="btn btn-success" style="display: none">开始上传</button>
</div>

<script>
    var uploader = null;
    var bacData = null;

    $('#bac_doc_file').click(function () {

        // 存在就不用再请求token了
        if(bacData){
            uploader = createUploader(bacData);// 为了防止第二次选择，会重复上传
            return true;
        }
        // 授权创建uploader
        $.ajax({
            url:'/main/bac-token',
            success:function (res,status) {
                if(res.code==0){
                    bacData = res.data;
                    $('#bac_doc_key').val(bacData.doc.documentId);
                    uploader = createUploader(bacData);
                }else {
                    alert(res.msg);
                    return false;
                }
            }
        });
        return true;
    });

    $('#bac_doc_file').change(function () {
        $('#bac_doc_upload_btn').show();
    });

    $('#bac_doc_upload_btn').click(function () {
        $('#bac_doc_upload_btn').hide();
        uploader.start();
        return false;
    });

    function createUploader(data) {
        return new baidubce.bos.Uploader({
            browse_button: '#bac_doc_file',
            bos_bucket: data.doc.bucket,
            bos_endpoint:data.doc.bosEndpoint,
            bos_ak: data.sts.accessKeyId,
            bos_sk: data.sts.secretAccessKey,
            uptoken: data.sts.sessionToken,
            max_file_size: '50M',
            bos_task_parallel:1,//队列中文件并行上传的个数
            multi_selection:false,//是否可以选择多个文件
            init: {
                PostInit: function () {
                    // uploader 初始化完毕之后，调用这个函数
                    console.log('初始化完毕');
                },
                Key: function (_, file) {
                    console.log('自定义bucket和object');
                    // 如果需要重命名 BOS 存储的文件名称，这个函数
                    // 返回新的文件名即可
                    // 如果这里需要执行异步的操作，可以返回 Promise 对象
                    // 如果需要自定义bucket和object，可以返回{bucket: string, key: string}
                    // 【为了兼容文件类型（后缀名），在这里再同步注册一次文档】------######这步很重要######---------
                    var object = null;
                    $.ajax({
                        url:'/main/bac-register?fileName='+file.name,// 注册文档
                        async:false,// 需要同步任务
                        success:function (res,status) {
                            if(res.code==0){
                                object = res.data.object;
                                $('#bac_doc_key').val(res.data.documentId);
                            }else {
                                alert(res.msg);
                                return false;
                            }
                        }
                    });
                    return {key:object}

                },
                FilesAdded: function (_, files) {
                    // 当文件被加入到队列里面，调用这个函数
                    console.log('文件被加入到队列里面');
                    console.log(files);
                },
                BeforeUpload: function (_, file) {
                    // 当某个文件开始上传的时候，调用这个函数
                    // 如果想组织这个文件的上传，请返回 false
                    var prog ='0%';
                    var html = file.name+'：'+prog;
                    $('#bac_doc_upload_info').html(html);
                    console.log('开始上传了！');
                },
                UploadProgress: function (_, file, progress, event) {
                    // 文件的上传进度
                    console.log('进度条！');
                    var prog = Number(progress*100).toFixed(0)+'%';
                    var html = file.name+'：'+prog;
                    $('#bac_doc_upload_info').html(html);
                },
                FileUploaded: function (_, file, info) {
                    console.log('上传成功！');
                    console.log(info);
                    // var bucket = info.body.bucket;
                    // var object = info.body.object;
                    // var url = data.doc.bosEndpoint + '/'+bucket + '/' + object;
                    // $(document.body).append($('<div><a href="' + url + '">' + url + '</a></div>'));
                    $('#bac_doc_upload_info').append('<font color="green"> 已完成</font>');
                    $('#bac_doc_id').html($('#bac_doc_key').val());
                },
                UploadComplete: function() {
                    console.log('上传结束');
                },
                UploadResumeError:function (_, file) {
                    console.log('断点续传错误！');
                },
                Error: function (_, error, file) {
                    // 如果上传的过程中出错了，调用这个函数
                    console.log('上传失败！');
                    console.log(error);
                },
            }
        });
    }
</script>
