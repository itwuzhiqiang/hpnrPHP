module.exports = class {

	onDataLoad() {
		return {
			sendCode: this.dataQL('sendCode'),
			forgetPassword: this.dataQL('forgetPassword'),
			userInfo: this.fetchQL('userInfo'),
			time: 60,
			read: true,
		};
	}

	onLoaded() {

		let code = this.state.userInfo.data.code;
		if (code === -1) {
			padapp.navigate.linkTo({
				url: '/login',
				params: {
					popup: true,
				}
			})
		}
	}

	goBack() {
		padapp.navigate.pop();
	}

	setVal(e, type) {
		let val = e.target.value;
		this.state.forgetPassword.setParam(type, val);
	}

	goSendCode() {
		let duAccount = this.state.forgetPassword.params.duAccount ? this.state.forgetPassword.params.duAccount : 0;
		let time = 60;
		let that = this;

		if (duAccount && /^1\d{10}$/.test(duAccount)) {
			that.state.sendCode.setParam('isThereAre', false).setParam('phone', duAccount).fetch((data) => {
				if (data) {
					that.overlay.msg(data.msg);
					if (data.code === 1) {
						let times = this.setInterval(() => {
							if (time > 0) {
								--time;
							} else if (time <= 0) {
								that.clear(times);
								that.setState({
									time: 60
								});
								return;
							}
							that.setState({
								time: time
							});
						}, 1000)
					}
				}
			});
		} else {
			that.overlay.msg('请填写正确的手机号')
		}
	}

	goRegister() {
		if (!(/^1\d{10}$/.test(this.state.forgetPassword.params.duAccount))) {
			this.overlay.msg('请填写正确的手机号');
		} else if (!this.state.forgetPassword.params.password) {
			this.overlay.msg('请输入密码');
		} else if (!this.state.forgetPassword.params.verification) {
			this.overlay.msg('请输入验证码');
		} else {
			this.state.userInfo.fetch((json) => {
				if (json.code === -1) {
					padapp.navigate.linkTo({
						url: '/login',
						params: {
							popup: true,
						}
					})
				} else {
					this.state.forgetPassword.fetch((data) => {
						if (data) {
							this.overlay.msg('修改成功');
							padapp.navigate.pop();
						}
					})
				}
			})

		}
	}

}