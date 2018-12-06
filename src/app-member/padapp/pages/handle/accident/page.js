module.exports = class {

	onDataLoad() {
		return {
			handleAccidentType: this.fetchQL('handleAccidentType'),
			userInfo: this.fetchQL('userInfo'),
		};
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
	}

	goDetail(item) {
		padapp.navigate.push({
			url: '/handle/shot2',
			params: {
				item: item,
			}
		})
	}

	goBack() {
		padapp.navigate.pop();
	}

	goChoose(item) {
		padapp.app.emit('/handle.duty.accident', {
			data: item,
		});
		padapp.navigate.pop();
	}

}