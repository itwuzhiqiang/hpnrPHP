module.exports = class {

	onDataLoad() {
		return {
			userFeedback: this.fetchQL('userFeedback'),
		};
	}

	goBack() {
		padapp.navigate.pop();
	}

	setVal(e, type) {
		let val = e.target.value;
		this.state.userFeedback.setParam(type, val);
	}

	goChange() {
		if (!this.state.userFeedback.params.ufMsg) {
			this.overlay.msg('请输入您的反馈信息')
		} else {
			this.state.userFeedback.fetch(()=>{
				this.overlay.msg('提交成功,感谢您的反馈');
				padapp.navigate.pop();
			})
		}
	}

}