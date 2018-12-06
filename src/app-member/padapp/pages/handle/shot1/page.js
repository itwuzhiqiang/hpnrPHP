module.exports = class {

	onDataLoad(props) {
		return {
			show: 0,
			aeFileLabel: '',
			title: '',
			dataServer: padapp.app.getConfig('rpapi'),
			handleSubmitevidence: this.dataQL('handleSubmitevidence'),
			userInfo: this.fetchQL('userInfo'),
			handleAccident: this.dataQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			rpaId: props.rpaId,
			handleType: props.type,
			type: '',
			otherImg: '',
			imgsLength: 0,
			number: 0,
			otherImgs: [],
			imgs: [],
			notThroughs: [],

		};
	}

	onLoad() {
	}

	onLoaded() {
		let code = this.state.userInfo.data.code;
		if (code === -1) {
			this.overlay.msg('登录失效');
			padapp.navigate.linkTo({
				url: '/login',
				params: {
					popup: true,
				}
			})
		}

		let handleType = parseInt(this.state.handleType);
		let number = this.state.number;
		if (handleType === 1) {
			number = 4
		}
		if (handleType === 2) {
			number = 5
		}
		if (handleType === 3) {
			number = 7
		}
		this.setState({
			number: number,
		});

		this.state.handleAccident.fetch((data) => {
			this.setImgs(data);
		});
	}

	setImgs(data) {
		let imgs = [];
		let notThroughs = [];
		let otherImgs = [];
		let videoImg = '';
		if (data && data.evidences) {
			_.map(data.evidences, (item) => {
				if (item.aeFileLabel == -1) {
					otherImgs.push(item.aeFileAddress);
				} else if (item.aeFileLabel == -2) {
					videoImg = item.aeFileThumbnail;
				} else {
					imgs[item.aeFileLabel] = {
						'state': item.aeInvalidState,
						'img': item.aeFileAddress,
					};
					if (item.aeInvalidState == 2) {
						notThroughs.push(item.aeFileLabel)
					}
				}
			});
			this.setState({
				imgs: imgs,
				otherImgs: otherImgs,
				videoImg: videoImg,
				notThroughs: notThroughs,
				imgsLength: this.countArray(imgs),
			})
		}
	}

	goShot(type) {
		let aeFileLabel = this.state.aeFileLabel;
		let title = this.state.title;
		let imgs = this.state.imgs;
		let bool = true;
		switch (type) {
			case 'front':
				title = '正前方';
				aeFileLabel = 0;
				break;
			case 'behind':
				title = '正后方';
				aeFileLabel = 1;
				break;
			case 'impact':
				title = '碰撞部位';
				aeFileLabel = 2;
				break;
			case 'impact1':
				title = '碰撞部位';
				aeFileLabel = 6;
				break;
			case 'first':
				title = '第一方机动车号牌';
				aeFileLabel = 3;
				break;
			case 'second':
				title = '第二方机动车号牌';
				aeFileLabel = 4;
				break;
			case 'third':
				title = '第三方机动车号牌';
				aeFileLabel = 5;
				break;
			case 'other':
				title = '补充照片拍摄';
				aeFileLabel = -1;
				break;
			default:
				this.overlay.msg('参数错误')
		}
		if (imgs) {
			if (imgs[aeFileLabel] && imgs[aeFileLabel].state === 1) {
				bool = false
			}
		}
		if (bool) {
			this.setState({
				type: type,
				show: 1,
				title: title,
				aeFileLabel: aeFileLabel,
			})
		}
	}

	setShow(show) {
		this.setState({
			show: show,
		})
	}

	countArray(o) {
		let t = typeof o;
		if (t === 'string') {
			return o.length;
		} else if (t === 'object') {
			let n = 0;
			for (let i in o) {
				n++;
			}
			return n;
		}
		return 0;
	}

	goFinish() {
		let imgsLength = this.state.imgsLength;
		let number = this.state.number;
		let notThroughs = this.state.notThroughs;
		console.log(notThroughs);
		if (imgsLength >= number) {
			if (!notThroughs.length) {
				this.state.handleSubmitevidence.setParam('rpaId', this.state.rpaId).fetch((json) => {
					if (json) {
						padapp.navigate.linkTo({
							url: '/handle.shot4',
							params: {
								rpaId: this.state.rpaId,
								type: this.state.handleType,
							}
						})
					}
				})
			} else {
				this.overlay.msg('请将不通过的照片逐一重新上传')
			}

		} else {
			this.overlay.msg('请依据示例将事故照片上传完全')
		}
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
		this.state.userInfo.fetch((json) => {
			if (json.code === -1) {
				padapp.navigate.linkTo({
					url: '/login',
					params: {
						popup: true,
					}
				})
			}
		});

		let that = this;
		let fileObj = '';
		let url = that.state.dataServer + '/accident/v1/app/accident/uploadevidencebylabel?accountToken=' + that.state.userInfo.data.account_token
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

					that.state.handleAccident.fetch((data) => {
						that.setImgs(data);
					});
					that.setShow(0);
					that.overlay.msg(res.msg);
					that.overlay.loadingClose(load);
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

				that.state.handleAccident.fetch((data) => {
					this.setImgs(data);
				});
				that.setShow(0);
				that.overlay.msg(res.msg);
				that.overlay.loadingClose(load);
			}).fail(function (res) {
				that.overlay.msg('上传失败');
				that.overlay.loadingClose(load);
			});
		}
	}

	uploadVideo(aeFileLabel) {
		this.state.userInfo.fetch((json) => {
			if (json.code === -1) {
				padapp.navigate.linkTo({
					url: '/login',
					params: {
						popup: true,
					}
				})
			}
		});

		let formData = new FormData();
		let that = this;

		let files = this.page.refs.video.files[0];

		if (files.size / 1024 > 1024 * 40) {
			that.overlay.msg('视频请勿超过40M');
			return;
		} else {
			formData.append('file', files);
		}

		let load = that.overlay.shadeLoading();

		$.ajax({
			url: that.state.dataServer + '/accident/v1/app/accident/uploadevidencebylabel?accountToken=' + that.state.userInfo.data.account_token
			+ '&aeFileLabel=' + aeFileLabel + '&rpaId=' + that.state.rpaId,
			type: 'POST',
			cache: false,
			data: formData,
			processData: false,
			contentType: false
		}).done((res) => {
			console.log(res);
			if (res.code == 1) {
				let arr = res.data.split(',');
				this.setState({
					videoImg: arr[1],
				})
			}
			that.overlay.msg(res.msg);
			that.overlay.loadingClose(load);
		}).fail(function (res) {
			that.overlay.msg('上传失败');
			that.overlay.loadingClose(load);
		});
	}

	goBack() {
		window.history.go(-1);
	}

}