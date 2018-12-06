module.exports = class {

	onDataLoad(props) {
		return {
			dataServer: padapp.app.getConfig('rpapi'),
			policeInfo: this.fetchQL('policeInfo'),
			type: props.type,
			title: '',
			str: '从相册中选取',
			aeFileLabel: 0,
			rpaId: props.rpaId,
		};
	}

	onLoaded() {
		let type = this.state.type;
		let title = this.state.title;
		let aeFileLabel = this.state.aeFileLabel;
		if (type == 'front') {
			title = '正前方';
			aeFileLabel = 0;
		}
		if (type == 'behind') {
			title = '正后方';
			aeFileLabel = 1;
		}
		if (type == 'impact') {
			title = '碰撞部位';
			aeFileLabel = 2;
		}
		if (type == 'impact1') {
			title = '碰撞部位';
			aeFileLabel = 6;
		}
        if (type == 'vin') {
            title = '车辆识别码';
            aeFileLabel = 7;
        }
		if (type == 'first') {
			title = '第一方机动车号牌';
			aeFileLabel = 3;
		}
		if (type == 'second') {
			title = '第二方机动车号牌';
			aeFileLabel = 4;
		}
		if (type == 'third') {
			title = '第三方机动车号牌';
			aeFileLabel = 5;
		}
		if (type == 'other') {
			title = '补充照片拍摄';
			aeFileLabel = -1;
		}

		console.log(this.state.rpaId);

		this.setState({
			title: title,
			type: type,
			aeFileLabel: aeFileLabel,
		});

	}

	goBack() {
		padapp.navigate.pop()
	}


	/*
	 三个参数
	 file：一个是文件(类型是图片格式)，
	 w：一个是文件压缩的后宽度，宽度越小，字节越小
	 objDiv：一个是容器或者回调函数
	 photoCompress()
	 */

	canvasDataURL(path, obj, callback) {
		var img = new Image();
		img.src = path;
		img.onload = function () {
			var that = this;
			// 默认按比例压缩
			var w = that.width,
				h = that.height,
				scale = w / h;
			w = obj.width || w;
			h = obj.height || (w / scale);
			var quality = 0.7;  // 默认图片质量为0.7
			//生成canvas
			var canvas = document.createElement('canvas');
			var ctx = canvas.getContext('2d');
			// 创建属性节点
			var anw = document.createAttribute("width");
			anw.nodeValue = w;
			var anh = document.createAttribute("height");
			anh.nodeValue = h;
			canvas.setAttributeNode(anw);
			canvas.setAttributeNode(anh);
			ctx.drawImage(that, 0, 0, w, h);
			// 图像质量
			if (obj.quality && obj.quality <= 1 && obj.quality > 0) {
				quality = obj.quality;
			}
			// quality值越小，所绘制出的图像越模糊
			var base64 = canvas.toDataURL('image/jpeg', quality);
			// 回调函数返回base64的值
			callback(base64);
		}
	}

	photoCompress(file, w, objDiv) {
		let that = this;
		var ready = new FileReader();
		/*开始读取指定的Blob对象或File对象中的内容.
		 当读取操作完成时,readyState属性的值会成为DONE,如果设置了onloadend事件处理程序,则调用之.
		 同时,result属性中将包含一个data: URL格式的字符串以表示所读取文件的内容.*/
		ready.readAsDataURL(file);
		ready.onload = function () {
			var re = this.result;
			that.canvasDataURL(re, w, objDiv)
		}
	}

	/**
	 * 将以base64的图片url数据转换为Blob
	 * @param urlData
	 * 用url方式表示的base64图片数据
	 */
	convertBase64UrlToBlob(urlData) {
		var arr = urlData.split(','), mime = arr[0].match(/:(.*?);/)[1],
			bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
		while (n--) {
			u8arr[n] = bstr.charCodeAt(n);
		}
		return new Blob([u8arr], {type: mime});
	}

	uploadFile(type) {
		let that = this;
		let fileObj = '';
		let url = that.state.dataServer + '/accident/v1/app/accident/uploadevidencebylabel?accountToken=' + that.state.policeInfo.data.sign
			+ '&aeFileLabel=' + that.state.aeFileLabel + '&rpaId=' + that.state.rpaId; // 接收上传文件的后台地址

		var form = new FormData(); // FormData 对象
		if (type === 2) {
			fileObj = this.page.refs.file1.files[0];
		} else {
			fileObj = this.page.refs.file.files[0];
		}

		let load = that.overlay.shadeLoading();

		if (fileObj.size / 1024 > 1025) { //大于1M，进行压缩上传
			this.photoCompress(fileObj, {
				quality: 0.2
			}, function (base64Codes) {
				var bl = that.convertBase64UrlToBlob(base64Codes);
				form.append("file", bl, "file_" + Date.parse(new Date()) + ".jpg"); // 文件对象
				$.ajax({
					url: url,
					type: 'POST',
					cache: false,
					data: form,
					processData: false,
					contentType: false
				}).done((res) => {

					padapp.app.emit('/handle.shot.detail', {
						data: res,
						aeFileLabel: that.state.aeFileLabel,
					});
					that.overlay.msg(res.msg);
					that.overlay.loadingClose(load);
					padapp.navigate.pop();
				}).fail(function (res) {
					that.overlay.msg('上传失败');
					that.overlay.loadingClose(load);
				});
			});
		} else { //小于等于1M 原图上传
			form.append("file", fileObj); // 文件对象
			$.ajax({
				url: url,
				type: 'POST',
				cache: false,
				data: form,
				processData: false,
				contentType: false
			}).done((res) => {

				padapp.app.emit('/handle.shot.detail', {
					data: res,
					aeFileLabel: that.state.aeFileLabel,
				});
				that.overlay.msg(res.msg);
				that.overlay.loadingClose(load);
				padapp.navigate.pop();
			}).fail(function (res) {
				that.overlay.msg('上传失败');
				that.overlay.loadingClose(load);
			});
		}
	}
}