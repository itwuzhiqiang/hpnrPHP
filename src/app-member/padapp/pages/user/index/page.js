module.exports = class {

	onDataLoad() {
		return {
			userInfo: this.fetchQL('userInfo'),
			memberLogout: this.dataQL('memberLogout'),
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

	goHome() {
		padapp.navigate.linkTo({
			url: '/home',
		})
	}

	goInfo() {
		padapp.navigate.linkTo({
			url: '/user.info',
		})
	}

	goCarInfo() {
		padapp.navigate.linkTo({
			url: '/user.clmsg',
		})
	}

	goDriverInfo() {
		padapp.navigate.linkTo({
			url: '/user.driver',
		})
	}

	goMsg() {
		padapp.navigate.linkTo({
			url: '/user.msg.list',
		})
	}

	goSet() {
		padapp.navigate.linkTo({
			url: '/user.set',
		})
	}

	goSearch() {
		padapp.navigate.linkTo({
			url: '/handle.search1',
		})
	}

	goUse() {
		window.location.href = 'http://test.fastepay.cn/ahs/assets/InsuranceAgent_Insurance_agent.html';
		// padapp.navigate.linkTo({
		// 	url: '/user.use',
		// })
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