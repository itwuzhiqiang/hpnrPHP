module.exports = class {
	onDataLoad(props) {
		console.log(props)
		return {
			memberLogin: this.dataQL('memberLogin'),
			popup: props.popup || false
		};
	}

	onLoaded() {
		if (this.state.popup) {
			this.overlay.msg('登录失效');
		}
	}

	goRegister() {
		padapp.navigate.push({
			url: '/register',
		})
	}

	setVal(e, type) {
		let val = e.target.value;
		this.state.memberLogin.setParam(type, val);
	}

	goLogin() {
		this.state.memberLogin.fetch((data) => {
			if (data) {
				padapp.navigate.linkTo({
					url: '/home'
				})
			}
		})
	}

	goForget() {
		padapp.navigate.push({
			url: '/forget'
		})
	}

}