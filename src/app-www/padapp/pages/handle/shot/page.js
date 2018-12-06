module.exports = class {

	onDataLoad(props) {
		return {
			dataServer: padapp.app.getConfig('rpapi'),
			show: false,
			rpaId: props.rpaId,
			type: props.type,
			imgs: [],
			policeInfo: this.fetchQL('policeInfo'),
			handleSubmitevidence: this.dataQL('handleSubmitevidence'),
			otherImg: '',
			videoImg: '',
			otherImgs: [],
			imgsLength: 0,
			number: 0,
			handleAccident: this.dataQL('handleAccident', {
				rpaId: props.rpaId,
			}),
		};
	}

	onLoad() {
		this.addListener('/handle.shot.detail', (data) => {
			console.log(data);
			if (data && data.data.data) {
				this.setState({
					data: data.data,
				});
				if (data) {
					console.log(data);
					if (data.aeFileLabel == -1) {
						let arr = this.state.otherImgs;
						arr.push(data.data.data);
						this.setState({
							otherImgs: arr,
						})
					} else {
						this.state.imgs[data.aeFileLabel] = data.data.data;
						this.setState({
							imgs: this.state.imgs,
						}, () => {
							this.setState({
								imgsLength: this.countArray(this.state.imgs),
							})
						})
					}
				}
			}
		});
	}

	onLoaded() {
		let type = this.state.type;
		let number = this.state.number;
		if (type == 1) {
			number = 4
		}
		if (type == 2) {
			number = 5
		}
		if (type == 3) {
			number = 7
		}
		this.setState({
			number: number,
		});

		this.state.handleAccident.fetch((data) => {
			let imgs = [];
			let otherImgs = [];
			let videoImg = '';
			if (data && data.evidences) {
				_.map(data.evidences, (item) => {
					if (item.aeFileLabel == -1) {
						otherImgs.push(item.aeFileAddress);
					} else if (item.aeFileLabel == -2) {
						videoImg = item.aeFileThumbnail;
					} else {
						imgs[item.aeFileLabel] = item.aeFileAddress;
					}
				});
				this.setState({
					imgs: imgs,
					otherImgs: otherImgs,
					videoImg: videoImg,
					imgsLength: this.countArray(imgs),
				})
			}
		});
        let that = this;
        let currenturl = window.location.href;
        $.ajax({
            url:that.state.dataServer + '/accident/v1/app/accident/history/addMHistoryRecord?html=' + currenturl + '&accountToken='  + that.state.policeInfo.data.sign,
            type:'POST',
            data: currenturl,
            cache: false,
            processData: false,
            contentType: false
        }).done((res)=>{
            console.log("success");
        }).fail(function (ress) {
            console.log("fail");

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


	goDetail(type) {
		padapp.navigate.push({
			url: '/handle/shot/detail',
			params: {
				type: type,
				rpaId: this.state.rpaId,
			}
		})
	}

	goFinish() {
		let imgsLength = this.state.imgsLength;
		let number = this.state.number;
		if (imgsLength >= number) {
			this.setState({
				show: true,
			})
		} else {
			this.overlay.msg('请依据示例将事故照片上传完全')
		}

	}

	goCancel() {
		this.setState({
			show: false,
		})
	}

	goRemind2() {
		this.state.handleSubmitevidence.setParam('rpaId', this.state.rpaId).fetch((json) => {
			if (json) {
				padapp.navigate.linkTo({
					url: '/handle.remind2',
					params: {
						rpaId: this.state.rpaId,
						type: this.state.type,
					}
				})
			}
		})
	}

	goHistory() {
		window.history.go(-1);
	}

	uploadFile(aeFileLabel) {
		let that = this;
		let formData = new FormData();
		let files = this.page.refs.file.files;
		for (let k = 0; k < files.length; k++) {
			formData.append('file', files[k]);
		}
		$.ajax({
			url: that.state.dataServer + '/accident/v1/app/accident/uploadevidencebylabel?accountToken=' + that.state.policeInfo.data.sign
			+ '&aeFileLabel=' + aeFileLabel + '&rpaId=' + that.state.rpaId,
			type: 'POST',
			cache: false,
			data: formData,
			processData: false,
			contentType: false
		}).done((res) => {
			that.overlay.msg(res.msg);
			if (res.data) {
				that.setState({
					otherImg: res.data,
				})
			}
		}).fail(function (res) {
			that.overlay.msg('上传失败')
		});
	}

	uploadVideo(aeFileLabel) {
		let that = this;
		let formData = new FormData();

		let files = this.page.refs.video.files[0];

		if (files.size / 1024 > 1024 * 40) {
			that.overlay.msg('视频请勿超过40M');
			return;
		} else {
			formData.append('file', files);
		}
		let load = that.overlay.shadeLoading();
		$.ajax({
			url: that.state.dataServer + '/accident/v1/app/accident/uploadevidencebylabel?accountToken=' + that.state.policeInfo.data.sign
			+ '&aeFileLabel=' + aeFileLabel + '&rpaId=' + that.state.rpaId,
			type: 'POST',
			cache: false,
			data: formData,
			processData: false,
			contentType: false
		}).done((res) => {
			if (res.code == 1) {
				let arr = res.data.split(',');
				this.setState({
					videoImg: arr[1],
				})
			}
			that.overlay.msg(res.msg);
			that.overlay.loadingClose(load);
		}).fail(function (res) {
			that.overlay.msg('上传失败')
			that.overlay.loadingClose(load);
		});
	}

}