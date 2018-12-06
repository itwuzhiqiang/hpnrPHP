module.exports = class {

	onDataLoad() {
		return {
			updatePassword: this.dataQL('updatePassword'),
			password: '',
			newPassword: '',
			newPassword1: '',
		};
	}

	setVal(e, type) {
		let val = e.target.value;
		if (type === 'password') {
			this.setState({
				password: val,
			})
		}
		if (type === 'newPassword') {
			this.setState({
				newPassword: val,
			})
		}
		if (type === 'newPassword1') {
			this.setState({
				newPassword1: val,
			})
		}
	}

	goUpdate() {
		let password = this.state.password;
		let newPassword = this.state.newPassword;
		let newPassword1 = this.state.newPassword1;
		if (!password) {
			this.overlay.msg('请输入旧密码');
			return;
		} else if (!newPassword) {
			this.overlay.msg('请输入新密码');
			return;
		} else if (!newPassword1) {
			this.overlay.msg('请输入重复密码');
			return;
		} else if (newPassword1 != newPassword) {
			this.overlay.msg('重复密码不一致');
			return;
		} else {
			this.state.updatePassword.setParam('password', password).setParam('newPassword', newPassword).fetch((json) => {
				if (json) {
					padapp.navigate.linkTo({
						url: '/login'
					})
				}
			})
		}
	}

	goHistory() {
		window.history.go(-1);
	}

}