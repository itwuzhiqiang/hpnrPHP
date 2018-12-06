module.exports = class {

	onDataLoad() {
		return {
			sendCode: this.dataQL('sendCode'),
			register: this.dataQL('register'),
			apiTest: this.dataQL('apiTest'),
			time: 60,
			read: true,
		};
	}

	onLoaded() {
	}

	goBack() {
		padapp.navigate.pop();
	}

	setVal(e, type) {
		let val = e.target.value;
		this.state.register.setParam(type, val);
	}

	goSendCode() {
		let phone = this.state.register.params.phone ? this.state.register.params.phone : 0;
		let time = 60;
		let that = this;
		if (phone && /^1\d{10}$/.test(phone)) {
			that.state.sendCode.setParam('phone', phone).fetch((data) => {
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
			this.overlay.msg('请填写正确的手机号')
		}
	}

	goRegister() {
		if (!(/^1\d{10}$/.test(this.state.register.params.phone))) {
			this.overlay.msg('请填写正确的手机号');
		} else if (!this.state.register.params.password) {
			this.overlay.msg('请输入密码');
		} else if (!this.state.register.params.code) {
			this.overlay.msg('请输入验证码');
		} else if (!this.state.read) {
			this.overlay.msg('请仔细阅读在线使用协议');
		} else {
			this.state.register.fetch((data) => {
				if (data) {
					this.overlay.msg(data.msg);
					padapp.navigate.pop();
				}
			})
		}
	}

	readAgreement() {
		this.setState({
			read: !this.state.read,
		})
	}

}