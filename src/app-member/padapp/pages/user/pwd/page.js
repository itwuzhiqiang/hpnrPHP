module.exports = class {

	onDataLoad() {
		return {
			updatePassword: this.dataQL('updatePassword'),
			memberLogout: this.dataQL('memberLogout'),
			userInfo: this.fetchQL('userInfo'),
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
		window.history.go(-1);
	}

	setVal(e, type) {
		let val = e.target.value;
		this.state.updatePassword.setParam(type, val);
	}

	goChange() {
		let password = this.state.updatePassword.params.password;
		let newPassword = this.state.updatePassword.params.newPassword;
		let rePassword = this.state.updatePassword.params.rePassword;
		let duAccount = this.state.userInfo.data.duAccount;
		if (!password || !newPassword || !rePassword) {
			this.overlay.msg('请填写完整不可为空');
		} else if (newPassword !== rePassword) {
			this.overlay.msg('新密码与确认密码不一致');
		} else {
			this.state.updatePassword.setParam('duAccount', duAccount).fetch((data) => {
				if (data) {
					this.overlay.msg('修改成功');
					this.state.memberLogout.fetch(() => {
						padapp.navigate.linkTo({
							url: '/login',
						})
					})
				}
			})
		}
	}

	goForget() {
		padapp.navigate.push({
			url: '/forget',
		})
	}

}