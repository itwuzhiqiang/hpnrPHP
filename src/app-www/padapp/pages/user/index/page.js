module.exports = class {

	onDataLoad() {
		return {
			policeInfo: this.fetchQL('policeInfo'),
		};
	}

	onLoaded() {
		let arr = this.state.policeInfo.data;
		if (!arr) {
			this.overlay.msg('登录已失效 请重新登录')
			padapp.navigate.linkTo({
				url: '/login',
			})
		}else {
			console.log(this.state.policeInfo.data)
		}
	}


	goBeResponsibility() {
		this.overlay.alert('暂未开放')
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

	goFeedback() {
		padapp.navigate.linkTo({
			url: '/user.feedback',
		})
	}

	goUpdatepassword() {
		padapp.navigate.linkTo({
			url: '/user.updatepassword',
		})
	}

	goUse() {
		padapp.navigate.linkTo({
			url: '/user.use',
		})
	}

}