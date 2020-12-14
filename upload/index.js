
require('fine-uploader');
var uploader = new qq.s3.FineUploader({
    request: {
        endpoint: 'flagship-video-project.s3.amazonaws.com'
        accessKey: 'AKIA5GGQGDXJ7N57DNHH'
    },
    signature: {
        endpoint: '/s3/signature'
    },
    uploadSuccess: {
        endpoint: '/s3/success'
    },
    iframeSupport: {
        localBlankPagePath: '/success.html'
    }
});
console.log('ALIVE');
