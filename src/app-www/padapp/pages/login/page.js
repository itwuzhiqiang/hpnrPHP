module.exports = class {

	onDataLoad() {
		return {
			memberLogin: this.dataQL('memberLogin'),
		};
	}

	onLoaded() {
	}

	goLogin() {
		this.state.memberLogin.fetch((json) => {
			console.log(json)
			if(json && json.data){
				this.overlay.msg('登录成功');
				padapp.navigate.linkTo({
					url: '/home'
				})
			}
		})
	}

	setVal(e, type) {
		let val = e.target.value
		this.state.memberLogin.setParam(type, val);
	}

	goBack() {
		window.history.go(-1);
	}

}