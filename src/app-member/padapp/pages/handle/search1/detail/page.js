module.exports = class {

	onDataLoad(props) {
		return {
			accidentDetail: this.dataQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			accident: [],
			ua: 'notWeixin',
		};
	}

	onLoaded() {
		let ua = navigator.userAgent.toLowerCase();
		if (ua.match(/MicroMessenger/i) == "micromessenger") {
			this.setState({
				ua: 'weixin',
			})
		} else {
			this.setState({
				ua: 'notWeixin',
			})
		}

		console.log('加载数据。。。');
		this.state.accidentDetail.fetch((json) => {
			// console.log(JSON.stringify(json));
			if (json) {
				this.setState({
					accident: json.accident,
				});
			}
		});
	}

	goToEvidence() {
		if (this.state.accidentDetail.data.evidences.length) {
			padapp.navigate.linkTo({
				url: "/handle.search1.detail.evidences",
				params: {
					rpaId: this.state.accident.rpaId,
				}
			});
		} else {
			this.overlay.msg('暂无');
		}
	}

	goToParties() {
		if (this.state.accidentDetail.data.parties.length) {
			padapp.navigate.linkTo({
				url: "/handle.search1.detail.parties",
				params: {
					rpaId: this.state.accident.rpaId,
				}
			});
		} else {
			this.overlay.msg('暂无');
		}

	}

	goToRendingshu(url) {
		if (url === undefined) {
			this.overlay.msg('暂无')
		} else {
			if (this.state.ua === 'weixin') {
				padapp.navigate.linkTo({
					url: "/append",
					params: {
						url: url,
					}
				});
			} else {
				window.location.href = 'http://nphrfs-kuaiyipei.oss-cn-hangzhou.aliyuncs.com/pdfjs-1.9.426-dist/web/viewer.html?file=' + url;
			}
		}
	}

	goHistory() {
		window.history.go(-1);
	}


}