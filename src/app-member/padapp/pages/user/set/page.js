module.exports = class {

	onDataLoad() {
		return {
			memberLogout: this.dataQL('memberLogout')
		};
	}

	goBack() {
		window.history.go(-1);
	}

	goUpdatePassword() {
		padapp.navigate.linkTo({
			url: '/user.pwd'
		})
	}

	goFeedback() {
		padapp.navigate.push({
			url: '/user/feedback'
		})
	}

	logout() {
		this.overlay.confirm('确认退出登录', (json) => {
			if (json) {
				this.state.memberLogout.fetch(() => {
					padapp.navigate.linkTo({
						url: '/login',
					})
				})
			}
		})
	}

}