module.exports = class {

	onDataLoad(props) {
		return {
			accidentDetail: this.dataQL('accidentDetail', {
				'rpaid': props.rpaid
			}),
			accident: [],
		};
	}

	onLoaded() {
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
				url: "/search.detail.evidences",
				params: {
					rpaid: this.state.accident.rpaId,
				}
			});
		} else {
			this.overlay.msg('暂无');
		}
	}

	goToParties() {
		if (this.state.accidentDetail.data.parties.length) {
			padapp.navigate.linkTo({
				url: "/search.detail.parties",
				params: {
					rpaid: this.state.accident.rpaId,
				}
			});
		} else {
			this.overlay.msg('暂无');
		}

	}

	goToRendingshu(url) {
		console.log(url);

		if (url === undefined) {
			this.overlay.msg('暂无')
		} else {
			window.location.href = 'http://nphrfs-kuaiyipei.oss-cn-hangzhou.aliyuncs.com/pdfjs-1.9.426-dist/web/viewer.html?file=' + url;
		}
	}

	goHistory() {
		window.history.go(-1);
	}


}